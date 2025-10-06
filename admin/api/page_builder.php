<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/ModuleRenderer.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Connessione al database non disponibile'
    ]);
    exit;
}

$renderer = new ModuleRenderer($db);

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$action = $_GET['action'] ?? null;

if ($method === 'POST' && $action === null) {
    $body = getRequestBody();
    $action = $body['action'] ?? null;
}

try {
    switch (sprintf('%s:%s', $method, $action)) {
        case 'GET:pages':
            respond(['success' => true, 'pages' => getPages($db)]);
            break;

        case 'GET:modules':
            respond(['success' => true, 'modules' => getModules($db, $renderer)]);
            break;

        case 'GET:page':
            $pageId = (int)($_GET['page_id'] ?? 0);
            if ($pageId <= 0) {
                throw new InvalidArgumentException('ID pagina non valido');
            }
            respond(['success' => true] + getPageData($db, $renderer, $pageId));
            break;

        case 'POST:save-instance':
            $payload = getRequestBody();
            respond(saveInstance($db, $renderer, $payload));
            break;

        case 'POST:update-order':
            $payload = getRequestBody();
            respond(updateOrder($db, $payload));
            break;

        case 'POST:delete-instance':
            $payload = getRequestBody();
            respond(deleteInstance($db, $payload));
            break;

        case 'POST:get-module-config':
            $payload = getRequestBody();
            respond(getModuleConfig($db, $renderer, $payload));
            break;

        case 'POST:preview-module':
            $payload = getRequestBody();
            respond(previewModule($renderer, $payload));
            break;

        case 'POST:preview-instance':
            $payload = getRequestBody();
            respond(previewInstance($db, $renderer, $payload));
            break;

        default:
            throw new InvalidArgumentException('Azione non supportata');
    }
} catch (InvalidArgumentException $exception) {
    respondError($exception->getMessage(), 400);
} catch (Throwable $throwable) {
    respondError($throwable->getMessage(), 500);
}

function getPages(PDO $db): array
{
    $stmt = $db->query('SELECT id, title, slug FROM pages ORDER BY title');
    return $stmt->fetchAll() ?: [];
}

function getModules(PDO $db, ModuleRenderer $renderer): array
{
    $stmt = $db->query('SELECT name FROM modules_registry WHERE is_active = 1 ORDER BY name');
    $modules = [];

    while ($row = $stmt->fetch()) {
        $manifest = $renderer->getModuleManifest($row['name']) ?? [];
        $modules[] = [
            'name' => $row['name'],
            'label' => $manifest['name'] ?? $row['name'],
            'description' => $manifest['description'] ?? null,
            'category' => $manifest['category'] ?? null,
            'has_ui' => !empty($manifest['ui_schema']),
            'tags' => $manifest['tags'] ?? [],
        ];
    }

    return $modules;
}

function getPageData(PDO $db, ModuleRenderer $renderer, int $pageId): array
{
    $pageStmt = $db->prepare('SELECT id, title, slug FROM pages WHERE id = ?');
    $pageStmt->execute([$pageId]);
    $page = $pageStmt->fetch();

    if (!$page) {
        throw new InvalidArgumentException('Pagina non trovata');
    }

    $modulesStmt = $db->prepare('SELECT id, module_name, instance_name, config, order_index, is_active
        FROM module_instances
        WHERE page_id = ?
        ORDER BY order_index');
    $modulesStmt->execute([$pageId]);
    $instances = [];

    while ($row = $modulesStmt->fetch()) {
        if ((int)$row['is_active'] !== 1) {
            continue;
        }

        $config = json_decode($row['config'], true) ?? [];
        $mergedConfig = $renderer->mergeConfigWithDefaults($row['module_name'], $config);
        $html = renderModuleSafe($renderer, $row['module_name'], $mergedConfig);

        $instances[] = [
            'id' => (int)$row['id'],
            'module' => $row['module_name'],
            'instance_name' => $row['instance_name'],
            'order_index' => (int)$row['order_index'],
            'html' => $html,
        ];
    }

    return [
        'page' => $page,
        'instances' => $instances,
    ];
}

function saveInstance(PDO $db, ModuleRenderer $renderer, array $payload): array
{
    $pageId = isset($payload['page_id']) ? (int)$payload['page_id'] : 0;
    $moduleName = isset($payload['module_name']) ? trim((string)$payload['module_name']) : '';
    $instanceName = isset($payload['instance_name']) ? trim((string)$payload['instance_name']) : '';
    $orderIndex = isset($payload['order_index']) ? (int)$payload['order_index'] : null;

    if ($pageId <= 0 || $moduleName === '' || $instanceName === '') {
        throw new InvalidArgumentException('Dati modulo non validi');
    }

    $config = extractConfigPayload($payload['config'] ?? []);
    $currentId = null;

    if (isset($payload['instance_id'])) {
        $instanceIdValue = $payload['instance_id'];
        // Se è 'temp', non impostare currentId (nuova istanza)
        if ($instanceIdValue !== 'temp') {
            $currentId = (int)$instanceIdValue;
        }
    } elseif (isset($payload['current_instance_id'])) {
        $currentInstanceIdValue = $payload['current_instance_id'];
        // Se è 'temp', non impostare currentId (nuova istanza)
        if ($currentInstanceIdValue !== 'temp') {
            $currentId = (int)$currentInstanceIdValue;
        }
    }

    $db->beginTransaction();

    try {
        $duplicateStmt = $db->prepare('SELECT id FROM module_instances WHERE page_id = ? AND instance_name = ? AND id != ?');
        $duplicateStmt->execute([$pageId, $instanceName, $currentId ?? 0]);
        if ($duplicateStmt->fetch()) {
            throw new InvalidArgumentException('Esiste già un\'istanza con questo nome nella pagina selezionata');
        }

        if ($currentId) {
            $updateStmt = $db->prepare('UPDATE module_instances SET module_name = ?, instance_name = ?, config = ?, order_index = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $updateStmt->execute([
                $moduleName,
                $instanceName,
                json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $orderIndex ?? 0,
                $currentId,
            ]);
            $instanceId = $currentId;
        } else {
            if ($orderIndex === null) {
                $orderStmt = $db->prepare('SELECT COALESCE(MAX(order_index), -1) + 1 AS next_order FROM module_instances WHERE page_id = ?');
                $orderStmt->execute([$pageId]);
                $orderIndex = (int)($orderStmt->fetchColumn() ?? 0);
            }

            $insertStmt = $db->prepare('INSERT INTO module_instances (page_id, module_name, instance_name, config, order_index) VALUES (?, ?, ?, ?, ?)');
            $insertStmt->execute([
                $pageId,
                $moduleName,
                $instanceName,
                json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $orderIndex,
            ]);
            $instanceId = (int)$db->lastInsertId();
        }

        $db->commit();
    } catch (Throwable $throwable) {
        $db->rollBack();
        throw $throwable;
    }

    $mergedConfig = $renderer->mergeConfigWithDefaults($moduleName, $config);
    $html = renderModuleSafe($renderer, $moduleName, $mergedConfig);

    return [
        'success' => true,
        'instance' => [
            'id' => $instanceId,
            'module' => $moduleName,
            'instance_name' => $instanceName,
            'order_index' => $orderIndex ?? 0,
            'html' => $html,
            'config' => $mergedConfig,
        ],
    ];
}

function updateOrder(PDO $db, array $payload): array
{
    $updates = $payload['updates'] ?? [];
    if (!is_array($updates) || empty($updates)) {
        throw new InvalidArgumentException('Nessun aggiornamento fornito');
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare('UPDATE module_instances SET order_index = ? WHERE id = ?');
        foreach ($updates as $update) {
            if (!isset($update['id'])) {
                continue;
            }
            $stmt->execute([(int)$update['order_index'], (int)$update['id']]);
        }
        $db->commit();
    } catch (Throwable $throwable) {
        $db->rollBack();
        throw $throwable;
    }

    return ['success' => true];
}

function deleteInstance(PDO $db, array $payload): array
{
    $instanceId = isset($payload['instance_id']) ? (int)$payload['instance_id'] : 0;
    if ($instanceId <= 0) {
        throw new InvalidArgumentException('ID istanza non valido');
    }

    $stmt = $db->prepare('DELETE FROM module_instances WHERE id = ?');
    $stmt->execute([$instanceId]);

    return ['success' => true];
}

function getModuleConfig(PDO $db, ModuleRenderer $renderer, array $payload): array
{
    $moduleName = isset($payload['module_name']) ? trim((string)$payload['module_name']) : '';
    if ($moduleName === '') {
        throw new InvalidArgumentException('Nome modulo mancante');
    }

    $config = [];
    $instanceId = isset($payload['instance_id']) ? (int)$payload['instance_id'] : 0;

    if ($instanceId > 0) {
        $stmt = $db->prepare('SELECT config FROM module_instances WHERE id = ?');
        $stmt->execute([$instanceId]);
        $row = $stmt->fetch();
        if ($row) {
            $config = json_decode($row['config'], true) ?? [];
        }
    }

    $merged = $renderer->mergeConfigWithDefaults($moduleName, $config);
    $manifest = $renderer->getModuleManifest($moduleName) ?? [];

    return [
        'success' => true,
        'config' => $merged,
        'manifest' => $manifest,
    ];
}

function previewModule(ModuleRenderer $renderer, array $payload): array
{
    $moduleName = isset($payload['module_name']) ? trim((string)$payload['module_name']) : '';
    if ($moduleName === '') {
        throw new InvalidArgumentException('Nome modulo mancante');
    }

    $config = extractConfigPayload($payload['config'] ?? []);
    $mergedConfig = $renderer->mergeConfigWithDefaults($moduleName, $config);
    $html = renderModuleSafe($renderer, $moduleName, $mergedConfig);

    return [
        'success' => true,
        'html' => $html,
        'config' => $mergedConfig,
    ];
}

function previewInstance(PDO $db, ModuleRenderer $renderer, array $payload): array
{
    $instanceId = isset($payload['instance_id']) ? (int)$payload['instance_id'] : 0;
    if ($instanceId <= 0) {
        throw new InvalidArgumentException('ID istanza non valido');
    }

    $stmt = $db->prepare('SELECT module_name, config FROM module_instances WHERE id = ?');
    $stmt->execute([$instanceId]);
    $row = $stmt->fetch();

    if (!$row) {
        throw new InvalidArgumentException('Istanza non trovata');
    }

    $config = json_decode($row['config'], true) ?? [];
    $mergedConfig = $renderer->mergeConfigWithDefaults($row['module_name'], $config);
    $html = renderModuleSafe($renderer, $row['module_name'], $mergedConfig);

    return [
        'success' => true,
        'html' => $html,
        'config' => $mergedConfig,
    ];
}

function renderModuleSafe(ModuleRenderer $renderer, string $moduleName, array $config): string
{
    try {
        return $renderer->renderModule($moduleName, $config);
    } catch (Throwable $throwable) {
        return '<div class="pb-module-error">' . htmlspecialchars($throwable->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
    }
}

function extractConfigPayload($config): array
{
    if (is_array($config)) {
        return $config;
    }

    if (is_string($config) && $config !== '') {
        $decoded = json_decode($config, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return [];
}

function getRequestBody(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST ?: [];
}

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function respondError(string $message, int $status = 400): void
{
    respond([
        'success' => false,
        'error' => $message,
    ], $status);
}
