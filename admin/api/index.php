<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/PageService.php';
require_once __DIR__ . '/../includes/ModuleService.php';
require_once __DIR__ . '/../includes/ContentService.php';
require_once __DIR__ . '/../includes/RaceService.php';
require_once __DIR__ . '/../../core/ModuleRenderer.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$resource = $_GET['resource'] ?? trim($_SERVER['PATH_INFO'] ?? '', '/');
$resource = $resource !== '' ? $resource : 'status';

// Basic pre-flight support for API calls.
if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

try {
    $db = admin_db();
    $pageService = new PageService($db);
    $moduleService = new ModuleService($db);
    $contentService = new ContentService($db);
    $raceService = new RaceService($db);
    $renderer = new ModuleRenderer($db);

    switch ($resource) {
        case 'status':
            admin_json([
                'status' => 'ok',
                'timestamp' => date(DATE_ATOM),
                'execution_time' => round((microtime(true) - ADMIN_APP_START) * 1000, 2) . 'ms',
            ]);
            return;

        case 'pages':
            handlePages($method, $pageService);
            return;

        case 'modules':
            handleModules($method, $moduleService);
            return;

        case 'module-instances':
            handleModuleInstances($method, $moduleService);
            return;

        case 'module-manifest':
            handleModuleManifest($moduleService);
            return;

        case 'module-preview':
            handleModulePreview($renderer);
            return;

        case 'content':
            handleContent($method, $contentService);
            return;

        case 'results':
            handleResults($method, $raceService);
            return;

        case 'races':
            admin_json(['data' => $raceService->listRaces()]);
            return;

        default:
            admin_json(['error' => 'Risorsa non trovata'], 404);
    }
} catch (Throwable $exception) {
    admin_json([
        'error' => $exception->getMessage(),
    ], 400);
}

function handlePages(string $method, PageService $service): void
{
    switch ($method) {
        case 'GET':
            admin_json(['data' => $service->list()]);
            return;
        case 'POST':
            $payload = admin_read_json();
            admin_json(['data' => $service->create($payload)], 201);
            return;
        case 'PUT':
            $payload = admin_read_json();
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            admin_json(['data' => $service->update($id, $payload)]);
            return;
        case 'DELETE':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $service->delete($id);
            admin_json(['status' => 'deleted']);
            return;
    }

    admin_json(['error' => 'Metodo non supportato per pages'], 405);
}

function handleModules(string $method, ModuleService $service): void
{
    switch ($method) {
        case 'GET':
            admin_json(['data' => $service->listRegistry()]);
            return;
        case 'PATCH':
            $payload = admin_read_json();
            admin_require($payload, ['id', 'is_active']);
            $service->toggleRegistry((int) $payload['id'], (bool) $payload['is_active']);
            admin_json(['status' => 'updated']);
            return;
    }

    admin_json(['error' => 'Metodo non supportato per modules'], 405);
}

function handleModuleInstances(string $method, ModuleService $service): void
{
    switch ($method) {
        case 'GET':
            $pageId = isset($_GET['page_id']) ? (int) $_GET['page_id'] : 0;
            admin_json(['data' => $service->listInstancesForPage($pageId)]);
            return;
        case 'POST':
            $payload = admin_read_json();
            admin_json(['data' => $service->saveInstance($payload)]);
            return;
        case 'PATCH':
            $payload = admin_read_json();
            admin_require($payload, ['page_id', 'order']);
            $service->reorderInstances((int) $payload['page_id'], $payload['order']);
            admin_json(['status' => 'reordered']);
            return;
        case 'DELETE':
            $pageId = isset($_GET['page_id']) ? (int) $_GET['page_id'] : 0;
            $instanceId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $service->deleteInstance($pageId, $instanceId);
            admin_json(['status' => 'deleted']);
            return;
    }

    admin_json(['error' => 'Metodo non supportato per module-instances'], 405);
}

function handleModuleManifest(ModuleService $service): void
{
    $module = $_GET['module'] ?? '';
    admin_json(['data' => $service->getManifest($module)]);
}

function handleModulePreview(ModuleRenderer $renderer): void
{
    $payload = admin_read_json();
    admin_require($payload, ['module_name']);
    $config = $payload['config'] ?? [];
    $html = $renderer->renderModule($payload['module_name'], $config);
    admin_json(['html' => $html]);
}

function handleContent(string $method, ContentService $service): void
{
    switch ($method) {
        case 'GET':
            admin_json(['data' => $service->list()]);
            return;
        case 'POST':
            $payload = admin_read_json();
            admin_json(['data' => $service->create($payload)], 201);
            return;
        case 'PUT':
            $payload = admin_read_json();
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            admin_json(['data' => $service->update($id, $payload)]);
            return;
        case 'DELETE':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $service->delete($id);
            admin_json(['status' => 'deleted']);
            return;
    }

    admin_json(['error' => 'Metodo non supportato per content'], 405);
}

function handleResults(string $method, RaceService $service): void
{
    switch ($method) {
        case 'GET':
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
            admin_json(['data' => $service->listResults($limit)]);
            return;
        case 'POST':
            $payload = admin_read_json();
            $service->createResult($payload);
            admin_json(['status' => 'created'], 201);
            return;
    }

    admin_json(['error' => 'Metodo non supportato per results'], 405);
}
