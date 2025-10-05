<?php
declare(strict_types=1);

/**
 * Sincronizza i moduli dal filesystem al database e restituisce un resoconto.
 */
function admin_sync_modules(\PDO $db): array
{
    $modulesPath = __DIR__ . '/../../modules/';
    $modulesInFilesystem = [];
    $manifests = [];

    if (is_dir($modulesPath)) {
        foreach (scandir($modulesPath) as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $moduleDir = $modulesPath . $dir;
            if (!is_dir($moduleDir)) {
                continue;
            }

            $componentFile = $moduleDir . '/' . $dir . '.php';
            if (!file_exists($componentFile)) {
                continue;
            }

            $modulesInFilesystem[] = $dir;

            $manifestPath = $moduleDir . '/module.json';
            if (file_exists($manifestPath)) {
                $content = file_get_contents($manifestPath);
                $json = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    $manifests[$dir] = $json;
                }
            }
        }
    }

    $stmt = $db->query('SELECT name, component_path FROM modules_registry');
    $modulesInDb = $stmt->fetchAll() ?: [];

    $updates = [];

    foreach ($modulesInFilesystem as $moduleName) {
        $componentPath = $moduleName . '/' . $moduleName . '.php';
        $cssClass = $moduleName . '-module';
        $defaultConfig = [];
        $registryName = $moduleName;

        if (isset($manifests[$moduleName])) {
            $manifest = $manifests[$moduleName];
            $componentPath = $manifest['component_path'] ?? $componentPath;
            $cssClass = $manifest['css_class'] ?? $cssClass;
            $defaultConfig = isset($manifest['default_config']) && is_array($manifest['default_config']) ? $manifest['default_config'] : [];
            $registryName = $manifest['slug'] ?? $registryName;
        }

        $stmt = $db->prepare('SELECT id FROM modules_registry WHERE name = ?');
        $stmt->execute([$registryName]);
        $exists = $stmt->fetch();

        if ($exists) {
            $updateStmt = $db->prepare('UPDATE modules_registry SET component_path = ?, css_class = ?, default_config = ? WHERE name = ?');
            $updateStmt->execute([
                $componentPath,
                $cssClass,
                json_encode($defaultConfig, JSON_UNESCAPED_UNICODE),
                $registryName,
            ]);
            $updates[] = ['type' => 'updated', 'module' => $registryName];
        } else {
            $insertStmt = $db->prepare('INSERT INTO modules_registry (name, component_path, css_class, default_config) VALUES (?, ?, ?, ?)');
            $insertStmt->execute([
                $registryName,
                $componentPath,
                $cssClass,
                json_encode($defaultConfig, JSON_UNESCAPED_UNICODE),
            ]);
            $updates[] = ['type' => 'created', 'module' => $registryName];
        }
    }

    foreach ($modulesInDb as $dbModule) {
        if (!in_array($dbModule['name'], $modulesInFilesystem, true)) {
            $deactivateStmt = $db->prepare('UPDATE modules_registry SET is_active = 0 WHERE name = ?');
            $deactivateStmt->execute([$dbModule['name']]);
            $updates[] = ['type' => 'deactivated', 'module' => $dbModule['name']];
        }
    }

    return [
        'filesystem' => $modulesInFilesystem,
        'database' => array_map(static function (array $row): string {
            return $row['name'];
        }, $modulesInDb),
        'updates' => $updates,
    ];
}
