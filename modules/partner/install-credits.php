<?php
/**
 * Script di Installazione Credits Sponsor
 * Installa automaticamente i loghi credits nel database
 */

require_once __DIR__ . '/../../config/database.php';

// Header
echo "========================================\n";
echo "INSTALLAZIONE CREDITS SPONSOR\n";
echo "Bologna Marathon\n";
echo "========================================\n\n";

try {
    // Connessione database
    $database = new Database();
    $db = $database->getConnection();
    
    echo "✅ Connessione al database riuscita\n\n";
    
    // Step 1: Modifica ENUM
    echo "📝 Step 1: Aggiornamento ENUM group_type...\n";
    $alterSql = "ALTER TABLE sponsors 
                 MODIFY COLUMN group_type ENUM('main', 'official', 'technical', 'sponsor', 'credits') NOT NULL";
    
    try {
        $db->exec($alterSql);
        echo "✅ ENUM aggiornato con successo\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already') !== false) {
            echo "ℹ️  ENUM già aggiornato, procedo...\n\n";
        } else {
            throw $e;
        }
    }
    
    // Step 2: Inserimento credits
    echo "📝 Step 2: Inserimento loghi credits...\n";
    
    $credits = [
        ['FIDAL', 'credits', 'assets/images/sponsor/credits/LogoFidal.jpg', 101],
        ['CONI Emilia Romagna', 'credits', 'assets/images/sponsor/credits/CONI_EMILIA_ROMAGNA.png', 102],
        ['Comune di Bologna', 'credits', 'assets/images/sponsor/credits/06-comune-di-bologna-bn-rgb (8).png', 103],
        ['Sport Valley Emilia Romagna', 'credits', 'assets/images/sponsor/credits/SPORT-VALLEY-ER-COLOR.png', 104],
        ['Bologna per lo Sport', 'credits', 'assets/images/sponsor/credits/BO-per-lo-sport_LOGO_yellow.png', 105],
        ['CSI Bologna', 'credits', 'assets/images/sponsor/credits/Logo CSI Bologna.png', 106],
        ['Run Tune Up', 'credits', 'assets/images/sponsor/credits/Logo RTU-01.png', 107],
        ['Bologna Marathon - Termal', 'credits', 'assets/images/sponsor/credits/Logo BM-Termal.png', 108],
        ['30km dei Portici', 'credits', 'assets/images/sponsor/credits/30km.png', 109],
        ['5km Bologna City Run', 'credits', 'assets/images/sponsor/credits/5km_Bologna City Run.png', 110]
    ];
    
    $insertSql = "INSERT INTO sponsors (name, category, group_type, image_path, is_active, sort_order) 
                  VALUES (?, ?, ?, ?, 1, ?)
                  ON DUPLICATE KEY UPDATE
                    image_path = VALUES(image_path),
                    is_active = VALUES(is_active),
                    sort_order = VALUES(sort_order)";
    
    $stmt = $db->prepare($insertSql);
    $inserted = 0;
    
    foreach ($credits as $credit) {
        list($name, $category, $imagePath, $sortOrder) = $credit;
        
        // Verifica che il file esista
        $fullPath = __DIR__ . '/../../' . $imagePath;
        if (!file_exists($fullPath)) {
            echo "⚠️  File non trovato: {$imagePath}\n";
            continue;
        }
        
        try {
            $stmt->execute([$name, $category, 'credits', $imagePath, $sortOrder]);
            echo "   ✅ {$name}\n";
            $inserted++;
        } catch (PDOException $e) {
            echo "   ❌ Errore con {$name}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Inseriti/aggiornati {$inserted} loghi credits\n\n";
    
    // Step 3: Verifica
    echo "📝 Step 3: Verifica installazione...\n";
    
    $countSql = "SELECT COUNT(*) as total FROM sponsors WHERE group_type = 'credits'";
    $stmt = $db->query($countSql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   Totale credits nel database: {$result['total']}\n";
    
    // Mostra tutti i credits
    $listSql = "SELECT id, name, image_path, is_active 
                FROM sponsors 
                WHERE group_type = 'credits' 
                ORDER BY sort_order ASC";
    $stmt = $db->query($listSql);
    $allCredits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📊 Credits installati:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-5s %-40s %-10s\n", "ID", "Nome", "Attivo");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($allCredits as $credit) {
        printf("%-5s %-40s %-10s\n", 
            $credit['id'], 
            substr($credit['name'], 0, 40),
            $credit['is_active'] ? '✅ Sì' : '❌ No'
        );
    }
    
    echo str_repeat("-", 80) . "\n\n";
    
    echo "========================================\n";
    echo "🎉 INSTALLAZIONE COMPLETATA!\n";
    echo "========================================\n\n";
    
    echo "📋 Prossimi passi:\n";
    echo "   1. Apri il page builder: admin/page-builder.php\n";
    echo "   2. Aggiungi/modifica il modulo Partner\n";
    echo "   3. Abilita 'Mostra Credits' (show_group4)\n";
    echo "   4. Salva e verifica la pagina\n\n";
    
    echo "🔗 Test visivo:\n";
    echo "   http://localhost/sito_modulare/test-partner-db.html\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERRORE DATABASE:\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ ERRORE:\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "✅ Script completato con successo!\n";
?>


