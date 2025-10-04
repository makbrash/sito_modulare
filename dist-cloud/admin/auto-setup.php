<?php
/**
 * Auto Setup Complete
 * Setup automatico completo del sistema modulare
 */

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>🚀 Auto Setup Completo</h2>";
    echo "<p>Questo script esegue automaticamente tutte le operazioni necessarie per il sistema modulare.</p>";
    
    // 1. Sync moduli
    echo "<h3>1️⃣ Sincronizzazione Moduli</h3>";
    
    $modulesPath = __DIR__ . '/../modules/';
    $modulesInFilesystem = [];
    if (is_dir($modulesPath)) {
        $directories = scandir($modulesPath);
        foreach ($directories as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($modulesPath . $dir)) {
                $moduleFile = $modulesPath . $dir . '/' . $dir . '.php';
                if (file_exists($moduleFile)) {
                    $modulesInFilesystem[] = $dir;
                }
            }
        }
    }
    
    $defaultConfigs = [
        'actionHero' => ['layout' => '2col', 'height' => '100vh'],
        'menu' => ['style' => 'horizontal', 'sticky' => true],
        'footer' => ['columns' => 4],
        'resultsTable' => ['limit' => 50, 'sortable' => true],
        'richText' => ['wrapper' => 'article'],
        'gallery' => ['columns' => 3, 'lightbox' => true],
        'raceCards' => ['layout' => 'vertical'],
        'button' => ['variant' => 'primary', 'size' => 'medium'],
        'text' => ['wrapper' => 'article']
    ];
    
    $cssClasses = [
        'actionHero' => 'hero-module',
        'menu' => 'main-menu',
        'footer' => 'site-footer',
        'resultsTable' => 'results-table',
        'richText' => 'rich-text',
        'gallery' => 'image-gallery',
        'raceCards' => 'race-cards-module',
        'button' => 'btn',
        'text' => 'rich-text'
    ];
    
    foreach ($modulesInFilesystem as $moduleName) {
        $componentPath = $moduleName . '/' . $moduleName . '.php';
        $defaultConfig = $defaultConfigs[$moduleName] ?? [];
        $cssClass = $cssClasses[$moduleName] ?? $moduleName . '-module';
        
        $stmt = $db->prepare("SELECT id FROM modules_registry WHERE name = ?");
        $stmt->execute([$moduleName]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            $stmt = $db->prepare("UPDATE modules_registry SET 
                component_path = ?, 
                css_class = ?, 
                default_config = ? 
                WHERE name = ?");
            $stmt->execute([
                $componentPath,
                $cssClass,
                json_encode($defaultConfig),
                $moduleName
            ]);
            echo "<p>✅ <strong>{$moduleName}</strong> - Aggiornato</p>";
        } else {
            $stmt = $db->prepare("INSERT INTO modules_registry (name, component_path, css_class, default_config) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $moduleName,
                $componentPath,
                $cssClass,
                json_encode($defaultConfig)
            ]);
            echo "<p>🆕 <strong>{$moduleName}</strong> - Aggiunto</p>";
        }
    }
    
    // 2. Aggiorna schema.sql
    echo "<h3>2️⃣ Aggiornamento Schema.sql</h3>";
    
    $schemaPath = __DIR__ . '/../database/schema.sql';
    $schemaContent = file_get_contents($schemaPath);
    
    $stmt = $db->query("SELECT name, component_path, css_class, default_config FROM modules_registry WHERE is_active = 1 ORDER BY name");
    $modules = $stmt->fetchAll();
    
    $insertStatements = [];
    foreach ($modules as $module) {
        $name = $module['name'];
        $path = $module['component_path'];
        $cssClass = $module['css_class'];
        $config = $module['default_config'];
        
        $insertStatements[] = "('{$name}', '{$path}', '{$cssClass}', '{$config}')";
    }
    
    $newInsert = "INSERT INTO modules_registry (name, component_path, css_class, default_config) VALUES\n" . 
                 implode(",\n", $insertStatements) . ";";
    
    $pattern = '/INSERT INTO modules_registry \(name, component_path, css_class, default_config\) VALUES.*?;/s';
    
    if (preg_match($pattern, $schemaContent)) {
        $newSchema = preg_replace($pattern, $newInsert, $schemaContent);
    } else {
        $lastInsertPos = strrpos($schemaContent, 'INSERT INTO');
        if ($lastInsertPos !== false) {
            $beforeLastInsert = substr($schemaContent, 0, $lastInsertPos);
            $afterLastInsert = substr($schemaContent, $lastInsertPos);
            $newSchema = $beforeLastInsert . $newInsert . "\n\n" . $afterLastInsert;
        } else {
            $newSchema = $schemaContent . "\n\n" . $newInsert;
        }
    }
    
    // Backup e salva
    $backupPath = __DIR__ . '/../database/schema.sql.backup.' . date('Y-m-d-H-i-s');
    file_put_contents($backupPath, $schemaContent);
    file_put_contents($schemaPath, $newSchema);
    
    echo "<p>✅ Schema.sql aggiornato e backup creato</p>";
    
    // 3. Verifica finale
    echo "<h3>3️⃣ Verifica Finale</h3>";
    
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
    
    // 4. Test del sistema
    echo "<h3>4️⃣ Test Sistema</h3>";
    
    require_once '../core/ModuleRenderer.php';
    $renderer = new ModuleRenderer($db);
    
    try {
        $pageData = $renderer->renderPage('home');
        echo "<p>✅ <strong>Rendering homepage:</strong> OK</p>";
        
        $modules = $pageData['modules'];
        echo "<p>✅ <strong>Moduli caricati:</strong> " . count($modules) . "</p>";
        
        foreach ($modules as $module) {
            echo "<p>• <strong>{$module['module_name']}</strong> - OK</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ <strong>Errore test:</strong> " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>🎉 Setup Completato!</h3>";
    echo "<p><strong>Il sistema modulare è pronto per l'uso.</strong></p>";
    
    echo "<p><a href='../index.php'>🔗 Vai alla Homepage</a></p>";
    echo "<p><a href='auto-setup.php'>🔄 Riavvia Setup</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Errore: " . $e->getMessage() . "</p>";
}
?>
