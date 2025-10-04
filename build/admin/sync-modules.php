<?php
/**
 * Sync Modules Utility
 * Sincronizza automaticamente i moduli dal filesystem al database
 */

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>🔄 Sync Moduli Automatico</h2>";
    
    // Directory moduli
    $modulesPath = __DIR__ . '/../modules/';
    
    // Moduli esistenti nel filesystem (con manifest)
    $modulesInFilesystem = [];
    $manifests = [];
    if (is_dir($modulesPath)) {
        $directories = scandir($modulesPath);
        foreach ($directories as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($modulesPath . $dir)) {
                $moduleFile = $modulesPath . $dir . '/' . $dir . '.php';
                $manifestPath = $modulesPath . $dir . '/module.json';
                if (file_exists($moduleFile)) {
                    $modulesInFilesystem[] = $dir;
                    if (file_exists($manifestPath)) {
                        $content = file_get_contents($manifestPath);
                        $json = json_decode($content, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                            $manifests[$dir] = $json;
                        }
                    }
                }
            }
        }
    }
    
    echo "<h3>📁 Moduli trovati nel filesystem:</h3>";
    echo "<ul>";
    foreach ($modulesInFilesystem as $module) {
        echo "<li><strong>{$module}</strong></li>";
    }
    echo "</ul>";
    
    // Moduli esistenti nel database
    $stmt = $db->query("SELECT name, component_path, css_class, default_config FROM modules_registry WHERE is_active = 1");
    $modulesInDB = $stmt->fetchAll();
    
    echo "<h3>🗄️ Moduli nel database:</h3>";
    echo "<ul>";
    foreach ($modulesInDB as $module) {
        echo "<li><strong>{$module['name']}</strong> - {$module['component_path']}</li>";
    }
    echo "</ul>";
    
    // Niente hardcoding: usa manifest se presente, altrimenti fallback convenzionale
    
    // Sincronizza moduli
    echo "<h3>⚡ Sincronizzazione:</h3>";
    
    foreach ($modulesInFilesystem as $moduleName) {
        $componentPath = $moduleName . '/' . $moduleName . '.php';
        $defaultConfig = [];
        $cssClass = $moduleName . '-module';
        $registryName = $moduleName; // slug
        if (isset($manifests[$moduleName])) {
            $m = $manifests[$moduleName];
            $componentPath = isset($m['component_path']) ? $m['component_path'] : $componentPath;
            $cssClass = isset($m['css_class']) ? $m['css_class'] : $cssClass;
            $defaultConfig = isset($m['default_config']) && is_array($m['default_config']) ? $m['default_config'] : [];
            $registryName = isset($m['slug']) ? $m['slug'] : $registryName;
        }
        
        // Verifica se il modulo esiste già
        $stmt = $db->prepare("SELECT id FROM modules_registry WHERE name = ?");
        $stmt->execute([$registryName]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Aggiorna configurazione se necessario
            $stmt = $db->prepare("UPDATE modules_registry SET 
                component_path = ?, 
                css_class = ?, 
                default_config = ? 
                WHERE name = ?");
            $stmt->execute([
                $componentPath,
                $cssClass,
                json_encode($defaultConfig),
                $registryName
            ]);
            echo "<p>✅ <strong>{$registryName}</strong> - Aggiornato</p>";
        } else {
            // Inserisci nuovo modulo
            $stmt = $db->prepare("INSERT INTO modules_registry (name, component_path, css_class, default_config) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $registryName,
                $componentPath,
                $cssClass,
                json_encode($defaultConfig)
            ]);
            echo "<p>🆕 <strong>{$registryName}</strong> - Aggiunto</p>";
        }
    }
    
    // Rimuovi moduli non più esistenti nel filesystem
    echo "<h3>🗑️ Pulizia moduli obsoleti:</h3>";
    foreach ($modulesInDB as $dbModule) {
        // Disattiva se nessuna cartella con slug corrispondente
        if (!in_array($dbModule['name'], $modulesInFilesystem)) {
            $stmt = $db->prepare("UPDATE modules_registry SET is_active = 0 WHERE name = ?");
            $stmt->execute([$dbModule['name']]);
            echo "<p>❌ <strong>{$dbModule['name']}</strong> - Disattivato (non trovato nel filesystem)</p>";
        }
    }
    
    // Verifica finale
    echo "<h3>✅ Stato finale:</h3>";
    $stmt = $db->query("SELECT name, component_path, is_active FROM modules_registry ORDER BY name");
    $finalModules = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Modulo</th><th>Percorso</th><th>Stato</th></tr>";
    foreach ($finalModules as $module) {
        $status = $module['is_active'] ? '✅ Attivo' : '❌ Disattivo';
        echo "<tr>";
        echo "<td><strong>{$module['name']}</strong></td>";
        echo "<td>{$module['component_path']}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><a href='../index.php'>🔗 Vai alla Homepage</a></p>";
    echo "<p><a href='sync-modules.php'>🔄 Riavvia Sync</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Errore: " . $e->getMessage() . "</p>";
}
?>
