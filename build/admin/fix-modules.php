<?php
/**
 * Fix Modules - Correzione moduli duplicati e incoerenti
 */

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>🔧 Fix Moduli Duplicati e Incoerenti</h2>";
    
    // 1. Prima di tutto, disattiva tutti i moduli
    echo "<h3>1️⃣ Disattivazione moduli esistenti</h3>";
    $db->exec("UPDATE modules_registry SET is_active = 0");
    echo "<p>✅ Tutti i moduli disattivati</p>";
    
    // 2. Rimuovi moduli duplicati/obsoleti
    echo "<h3>2️⃣ Rimozione moduli duplicati</h3>";
    
    // Lista moduli corretti da mantenere
    $correctModules = [
        'actionHero' => [
            'component_path' => 'hero/hero.php',
            'css_class' => 'hero-module',
            'default_config' => ['layout' => '2col', 'height' => '100vh']
        ],
        'button' => [
            'component_path' => 'button/button.php',
            'css_class' => 'btn',
            'default_config' => ['variant' => 'primary', 'size' => 'medium']
        ],
        'footer' => [
            'component_path' => 'footer/footer.php',
            'css_class' => 'site-footer',
            'default_config' => ['columns' => 4]
        ],
        'menu' => [
            'component_path' => 'menu/menu.php',
            'css_class' => 'main-menu',
            'default_config' => ['style' => 'horizontal', 'sticky' => true]
        ],
        'raceCards' => [
            'component_path' => 'race-cards/race-cards.php',
            'css_class' => 'race-cards-module',
            'default_config' => ['layout' => 'vertical']
        ],
        'resultsTable' => [
            'component_path' => 'results/results.php',
            'css_class' => 'results-table',
            'default_config' => ['limit' => 50, 'sortable' => true]
        ],
        'richText' => [
            'component_path' => 'text/text.php',
            'css_class' => 'rich-text',
            'default_config' => ['wrapper' => 'article']
        ]
    ];
    
    // Rimuovi moduli obsoleti
    $obsoleteModules = ['hero', 'results', 'text', 'gallery', 'race-cards'];
    foreach ($obsoleteModules as $obsolete) {
        $stmt = $db->prepare("DELETE FROM modules_registry WHERE name = ?");
        $stmt->execute([$obsolete]);
        echo "<p>🗑️ <strong>{$obsolete}</strong> - Rimosso (obsoleto)</p>";
    }
    
    // 3. Attiva solo i moduli corretti
    echo "<h3>3️⃣ Attivazione moduli corretti</h3>";
    
    foreach ($correctModules as $name => $config) {
        // Verifica se esiste
        $stmt = $db->prepare("SELECT id FROM modules_registry WHERE name = ?");
        $stmt->execute([$name]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Aggiorna e attiva
            $stmt = $db->prepare("UPDATE modules_registry SET 
                component_path = ?, 
                css_class = ?, 
                default_config = ?,
                is_active = 1
                WHERE name = ?");
            $stmt->execute([
                $config['component_path'],
                $config['css_class'],
                json_encode($config['default_config']),
                $name
            ]);
            echo "<p>✅ <strong>{$name}</strong> - Aggiornato e attivato</p>";
        } else {
            // Inserisci nuovo
            $stmt = $db->prepare("INSERT INTO modules_registry (name, component_path, css_class, default_config, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([
                $name,
                $config['component_path'],
                $config['css_class'],
                json_encode($config['default_config'])
            ]);
            echo "<p>🆕 <strong>{$name}</strong> - Aggiunto e attivato</p>";
        }
    }
    
    // 4. Verifica stato finale
    echo "<h3>4️⃣ Stato finale moduli</h3>";
    
    $stmt = $db->query("SELECT name, component_path, is_active FROM modules_registry ORDER BY name");
    $modules = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Modulo</th><th>Percorso</th><th>Stato</th></tr>";
    foreach ($modules as $module) {
        $status = $module['is_active'] ? '✅ Attivo' : '❌ Disattivo';
        $color = $module['is_active'] ? '#d4edda' : '#f8d7da';
        echo "<tr style='background-color: {$color}'>";
        echo "<td><strong>{$module['name']}</strong></td>";
        echo "<td>{$module['component_path']}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. Verifica moduli nella pagina home
    echo "<h3>5️⃣ Verifica moduli pagina home</h3>";
    
    $stmt = $db->prepare("SELECT pm.module_name, pm.is_active, mr.is_active as module_registry_active 
                         FROM page_modules pm 
                         LEFT JOIN modules_registry mr ON pm.module_name = mr.name 
                         WHERE pm.page_id = 1 
                         ORDER BY pm.order_index");
    $stmt->execute();
    $pageModules = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Modulo Pagina</th><th>Attivo Pagina</th><th>Attivo Registry</th><th>Stato</th></tr>";
    foreach ($pageModules as $pm) {
        $pageActive = $pm['is_active'] ? '✅' : '❌';
        $registryActive = $pm['module_registry_active'] ? '✅' : '❌';
        $status = ($pm['is_active'] && $pm['module_registry_active']) ? '✅ OK' : '❌ Problema';
        $color = ($pm['is_active'] && $pm['module_registry_active']) ? '#d4edda' : '#f8d7da';
        
        echo "<tr style='background-color: {$color}'>";
        echo "<td><strong>{$pm['module_name']}</strong></td>";
        echo "<td>{$pageActive}</td>";
        echo "<td>{$registryActive}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 6. Test rendering
    echo "<h3>6️⃣ Test rendering homepage</h3>";
    
    require_once '../core/ModuleRenderer.php';
    $renderer = new ModuleRenderer($db);
    
    try {
        $pageData = $renderer->renderPage('home');
        $modules = $pageData['modules'];
        
        echo "<p>✅ <strong>Rendering homepage:</strong> OK</p>";
        echo "<p>✅ <strong>Moduli caricati:</strong> " . count($modules) . "</p>";
        
        echo "<ul>";
        foreach ($modules as $module) {
            echo "<li><strong>{$module['module_name']}</strong> - OK</li>";
        }
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p>❌ <strong>Errore rendering:</strong> " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>🎉 Fix Completato!</h3>";
    echo "<p><strong>I moduli dovrebbero ora funzionare correttamente.</strong></p>";
    
    echo "<p><a href='../index.php'>🔗 Vai alla Homepage</a></p>";
    echo "<p><a href='fix-modules.php'>🔄 Riavvia Fix</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Errore: " . $e->getMessage() . "</p>";
}
?>
