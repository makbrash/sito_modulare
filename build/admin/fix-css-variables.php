<?php
/**
 * Fix CSS Variables - Bologna Marathon
 * Rimuove variabili CSS hardcoded e conflittuali dal database
 */

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>🔧 Fix CSS Variables</h2>";
    
    // Controlla pagine esistenti
    $stmt = $db->query("SELECT id, slug, title, css_variables FROM pages");
    $pages = $stmt->fetchAll();
    
    echo "<h3>📄 Pagine trovate:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Slug</th><th>Titolo</th><th>CSS Variables</th><th>Azioni</th></tr>";
    
    foreach ($pages as $page) {
        $cssVars = json_decode($page['css_variables'], true) ?? [];
        $hasHardcodedColors = false;
        
        // Controlla se ci sono colori hardcoded
        $hardcodedColors = ['--primary-color', '--secondary-color', '--accent-color'];
        foreach ($hardcodedColors as $color) {
            if (isset($cssVars[$color])) {
                $hasHardcodedColors = true;
                break;
            }
        }
        
        echo "<tr>";
        echo "<td>{$page['id']}</td>";
        echo "<td>{$page['slug']}</td>";
        echo "<td>{$page['title']}</td>";
        echo "<td>" . htmlspecialchars($page['css_variables']) . "</td>";
        
        if ($hasHardcodedColors) {
            echo "<td><span style='color: #dc3545;'>⚠️ Color hardcoded rilevati</span></td>";
        } else {
            echo "<td><span style='color: #28a745;'>✅ OK</span></td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Rimuovi colori hardcoded
    echo "<h3>🔧 Correzione colori hardcoded:</h3>";
    
    $updated = 0;
    foreach ($pages as $page) {
        $cssVars = json_decode($page['css_variables'], true) ?? [];
        $originalVars = $cssVars;
        
        // Rimuovi colori hardcoded
        unset($cssVars['--primary-color']);
        unset($cssVars['--secondary-color']);
        unset($cssVars['--accent-color']);
        
        // Se ci sono state modifiche, aggiorna il database
        if ($cssVars !== $originalVars) {
            $stmt = $db->prepare("UPDATE pages SET css_variables = ? WHERE id = ?");
            $stmt->execute([json_encode($cssVars), $page['id']]);
            $updated++;
            
            echo "<p>✅ Pagina '{$page['slug']}' aggiornata - rimossi colori hardcoded</p>";
        }
    }
    
    if ($updated === 0) {
        echo "<p>✅ Nessuna pagina richiede aggiornamenti</p>";
    }
    
    // Mostra variabili CSS ufficiali
    echo "<h3>🎨 Variabili CSS ufficiali del sistema:</h3>";
    echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 4px; font-family: monospace;'>";
    echo "<strong>Colori principali:</strong><br>";
    echo "• --primary: #23a8eb (Blu principale)<br>";
    echo "• --secondary: var(--portici-pink) (#dc335e)<br>";
    echo "• --accent-cyan: #5DADE2<br>";
    echo "• --accent-blue: #00a8ff<br>";
    echo "• --warning: #F39C12<br>";
    echo "• --error: #E74C3C<br><br>";
    
    echo "<strong>Colori specifici gara:</strong><br>";
    echo "• --portici-pink: #dc335e<br>";
    echo "• --runtune-green: #cbdf44<br>";
    echo "• --hover-blue: #0080cc<br><br>";
    
    echo "<strong>Per personalizzare:</strong><br>";
    echo "Usa il campo css_variables nel database con le variabili ufficiali<br>";
    echo "Esempio: {\"--primary\": \"#custom-color\"}";
    echo "</div>";
    
    echo "<h3>🎉 Fix completato!</h3>";
    echo "<p><strong>Sistema ora usa solo le variabili CSS ufficiali.</strong></p>";
    
    echo "<p><a href='page-builder.php' class='btn'>🚀 Apri Page Builder</a></p>";
    echo "<p><a href='../index.php' target='_blank' class='btn'>👀 Vai al Sito</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Errore: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 800px; margin: 2rem auto; padding: 1rem; }
    .btn { display: inline-block; background: #007bff; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin: 0.5rem 0.5rem 0.5rem 0; }
    .btn:hover { background: #0056b3; }
    table { margin: 1rem 0; }
    th, td { padding: 0.5rem; text-align: left; }
    th { background: #f8f9fa; }
    h2, h3 { color: #2c3e50; }
    p { margin: 1rem 0; }
</style>
