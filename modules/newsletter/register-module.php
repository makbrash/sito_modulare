<?php
/**
 * Script di Registrazione Modulo Newsletter
 * Registra il modulo newsletter nel database
 * 
 * STRUTTURA TABELLA modules_registry:
 * - id (INT, PK, AUTO_INCREMENT)
 * - name (VARCHAR(100), UNIQUE, NOT NULL)
 * - component_path (VARCHAR(200), NOT NULL)
 * - css_class (VARCHAR(100), NULLABLE)
 * - default_config (JSON, NULLABLE)
 * - is_active (BOOLEAN, DEFAULT TRUE)
 * - created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
 * 
 * ⚠️ ATTENZIONE: Le colonne description, ui_schema, slug, version, updated_at NON esistono!
 */

require_once __DIR__ . '/../../config/database.php';

try {
    // Leggi il manifest
    $manifestPath = __DIR__ . '/module.json';
    if (!file_exists($manifestPath)) {
        throw new Exception("Manifest module.json non trovato");
    }
    
    $manifest = json_decode(file_get_contents($manifestPath), true);
    if (!$manifest) {
        throw new Exception("Errore nel parsing del manifest");
    }
    
    // Verifica se il modulo esiste già
    $stmt = $db->prepare("SELECT id FROM modules_registry WHERE name = ?");
    $stmt->execute([$manifest['name']]);
    $existingModule = $stmt->fetch();
    
    if ($existingModule) {
        // Aggiorna modulo esistente
        $stmt = $db->prepare("
            UPDATE modules_registry 
            SET 
                component_path = ?,
                css_class = ?,
                default_config = ?,
                is_active = ?
            WHERE name = ?
        ");
        
        $stmt->execute([
            $manifest['component_path'],
            $manifest['name'], // usa name come css_class
            json_encode($manifest['default_config']),
            $manifest['is_active'] ? 1 : 0,
            $manifest['name']
        ]);
        
        echo "✅ Modulo '{$manifest['name']}' aggiornato con successo!\n";
        
    } else {
        // Inserisci nuovo modulo
        $stmt = $db->prepare("
            INSERT INTO modules_registry 
            (name, component_path, css_class, default_config, is_active) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $manifest['name'],
            $manifest['component_path'],
            $manifest['name'], // usa name come css_class
            json_encode($manifest['default_config']),
            $manifest['is_active'] ? 1 : 0
        ]);
        
        echo "✅ Modulo '{$manifest['name']}' registrato con successo!\n";
    }
    
    // Crea tabella newsletter_subscribers se non esiste
    $installSqlPath = __DIR__ . '/install.sql';
    if (file_exists($installSqlPath)) {
        echo "\n📊 Esecuzione script SQL...\n";
        $sql = file_get_contents($installSqlPath);
        
        // Esegui script SQL
        try {
            $db->exec($sql);
            echo "✅ Tabella newsletter_subscribers creata con successo!\n";
        } catch (PDOException $e) {
            echo "⚠️  Attenzione SQL: " . $e->getMessage() . "\n";
            echo "   (Potrebbe essere già esistente)\n";
        }
    }
    
    echo "\n🎉 Installazione completata!\n";
    echo "\n📋 Prossimi passi:\n";
    echo "   1. Apri il page builder: admin/page-builder.php\n";
    echo "   2. Aggiungi il modulo 'newsletter' a una pagina\n";
    echo "   3. Configura il tipo di registrazione (classic/whatsapp/channel)\n";
    echo "   4. Test: http://localhost/sito_modulare/test-newsletter.html\n";
    
} catch (PDOException $e) {
    echo "❌ Errore database: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}

