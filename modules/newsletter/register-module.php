<?php
/**
 * Script di Registrazione Modulo Newsletter
 * Registra il modulo newsletter nel database
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
    $stmt = $db->prepare("SELECT id FROM modules_registry WHERE slug = ?");
    $stmt->execute([$manifest['slug']]);
    $existingModule = $stmt->fetch();
    
    if ($existingModule) {
        // Aggiorna modulo esistente
        $stmt = $db->prepare("
            UPDATE modules_registry 
            SET 
                name = ?,
                description = ?,
                version = ?,
                component_path = ?,
                default_config = ?,
                ui_schema = ?,
                is_active = ?,
                updated_at = NOW()
            WHERE slug = ?
        ");
        
        $stmt->execute([
            $manifest['name'],
            $manifest['description'],
            $manifest['version'],
            $manifest['component_path'],
            json_encode($manifest['default_config']),
            json_encode($manifest['ui_schema']),
            $manifest['is_active'] ? 1 : 0,
            $manifest['slug']
        ]);
        
        echo "✅ Modulo '{$manifest['name']}' aggiornato con successo!\n";
        
    } else {
        // Inserisci nuovo modulo
        $stmt = $db->prepare("
            INSERT INTO modules_registry 
            (name, slug, description, version, component_path, default_config, ui_schema, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $manifest['name'],
            $manifest['slug'],
            $manifest['description'],
            $manifest['version'],
            $manifest['component_path'],
            json_encode($manifest['default_config']),
            json_encode($manifest['ui_schema']),
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

