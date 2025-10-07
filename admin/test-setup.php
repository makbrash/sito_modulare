<?php
/**
 * Test Setup - Bologna Marathon
 * Pagina semplice per setup database di test
 */

// Configurazione database
$host = 'localhost';
$dbname = 'bologna_marathon';
$username = 'root';
$password = '';

// Prima prova a connettersi al database esistente
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $success = true;
    $dbExists = true;
} catch(PDOException $e) {
    // Se il database non esiste, prova a crearlo
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        try {
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE $dbname");
            $pdo->exec("USE $dbname");
            $success = true;
            $dbExists = false;
        } catch(PDOException $e2) {
            $error = "Errore creazione database: " . $e2->getMessage();
            $success = false;
        }
    } else {
        $error = "Errore connessione: " . $e->getMessage();
        $success = false;
    }
}

// Azioni
$action = $_GET['action'] ?? '';

if ($action === 'setup' && $success) {
    try {
        // Controlla se le tabelle esistono già
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $hasTables = count($tables) > 0;
        
        if (!$hasTables) {
            // Leggi e esegui schema solo se non ci sono tabelle
            $schemaPath = __DIR__ . '/../database/schema.sql';
            if (file_exists($schemaPath)) {
                $schema = file_get_contents($schemaPath);
                $pdo->exec($schema);
            } else {
                throw new Exception("File schema.sql non trovato: $schemaPath");
            }
        }
        
        // Leggi e esegui dati test (sempre, anche se le tabelle esistono)
        $testDataPath = __DIR__ . '/../database/test_data.sql';
        if (file_exists($testDataPath)) {
            $testData = file_get_contents($testDataPath);
            
            // Esegui solo gli INSERT, non i CREATE TABLE
            $lines = explode("\n", $testData);
            $insertStatements = [];
            $currentStatement = '';
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '--') === 0) continue;
                
                $currentStatement .= $line . ' ';
                
                if (strpos($line, ';') !== false) {
                    if (stripos($currentStatement, 'INSERT INTO') !== false) {
                        $insertStatements[] = trim($currentStatement);
                    }
                    $currentStatement = '';
                }
            }
            
            // Esegui solo gli INSERT
            foreach ($insertStatements as $statement) {
                try {
                    $pdo->exec($statement);
                } catch(PDOException $e) {
                    // Ignora errori di duplicati
                    if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                        throw $e;
                    }
                }
            }
        } else {
            throw new Exception("File test_data.sql non trovato: $testDataPath");
        }
        
        // Leggi e esegui temi
        $themesPath = __DIR__ . '/../database/theme_identities.sql';
        if (file_exists($themesPath)) {
            $themes = file_get_contents($themesPath);
            $pdo->exec($themes);
        }
        
        $setupSuccess = true;
        $setupMessage = $hasTables ? "Dati aggiornati (tabelle già esistenti)" : "Database e dati creati con successo";
        
    } catch(Exception $e) {
        $setupError = "Errore setup: " . $e->getMessage();
    }
}

if ($action === 'clear' && $success) {
    try {
        $pdo->exec("DROP DATABASE IF EXISTS $dbname");
        $pdo->exec("CREATE DATABASE $dbname");
        $clearSuccess = true;
    } catch(PDOException $e) {
        $clearError = "Errore clear: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Setup - Bologna Marathon</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #D81E05;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 8px 8px 8px 0;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: #B71C1C;
        }
        .btn-secondary {
            background: #6C757D;
        }
        .btn-secondary:hover {
            background: #5A6268;
        }
        .success {
            background: #D4EDDA;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin: 1rem 0;
        }
        .error {
            background: #F8D7DA;
            color: #721C24;
            padding: 12px;
            border-radius: 6px;
            margin: 1rem 0;
        }
        .info {
            background: #D1ECF1;
            color: #0C5460;
            padding: 12px;
            border-radius: 6px;
            margin: 1rem 0;
        }
        h1 {
            color: #D81E05;
            margin-bottom: 2rem;
        }
        h2 {
            color: #2C3E50;
            margin-top: 2rem;
        }
        .status {
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }
        .status.success {
            background: #D4EDDA;
            color: #155724;
        }
        .status.error {
            background: #F8D7DA;
            color: #721C24;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>🏃‍♂️ Bologna Marathon - Test Setup</h1>
        
        <?php if (isset($setupSuccess)): ?>
            <div class="success">
                ✅ <strong>Setup completato!</strong> <?= isset($setupMessage) ? $setupMessage : 'Database e dati di test creati con successo.' ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($dbExists) && !$dbExists): ?>
            <div class="info">
                ℹ️ <strong>Database creato!</strong> Il database 'bologna_marathon' è stato creato automaticamente.
            </div>
        <?php endif; ?>
        
        <?php if (isset($setupError)): ?>
            <div class="error">
                ❌ <strong>Errore setup:</strong> <?= htmlspecialchars($setupError) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($clearSuccess)): ?>
            <div class="success">
                ✅ <strong>Database pulito!</strong> Database ricreato da zero.
            </div>
        <?php endif; ?>
        
        <?php if (isset($clearError)): ?>
            <div class="error">
                ❌ <strong>Errore clear:</strong> <?= htmlspecialchars($clearError) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
            <div class="error">
                ❌ <strong>Errore connessione:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>📁 File di Setup</h2>
        <?php
        $schemaPath = __DIR__ . '/../database/schema.sql';
        $testDataPath = __DIR__ . '/../database/test_data.sql';
        ?>
        <p><strong>Schema SQL:</strong> 
            <?php if (file_exists($schemaPath)): ?>
                <span class="status success">✅ Trovato</span> <?= htmlspecialchars($schemaPath) ?>
            <?php else: ?>
                <span class="status error">❌ Non trovato</span> <?= htmlspecialchars($schemaPath) ?>
            <?php endif; ?>
        </p>
        <p><strong>Test Data SQL:</strong> 
            <?php if (file_exists($testDataPath)): ?>
                <span class="status success">✅ Trovato</span> <?= htmlspecialchars($testDataPath) ?>
            <?php else: ?>
                <span class="status error">❌ Non trovato</span> <?= htmlspecialchars($testDataPath) ?>
            <?php endif; ?>
        </p>
    </div>
    
    <div class="card">
        <h2>📊 Stato Database</h2>
        
        <?php if ($success): ?>
            <p><span class="status success">✅ Connesso</span> Database: <strong><?= $dbname ?></strong></p>
            <?php if (isset($dbExists) && !$dbExists): ?>
                <p><span class="status success">🆕 Creato automaticamente</span></p>
            <?php endif; ?>
            
            <?php
            try {
                // Conta tabelle
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                $tableCount = count($tables);
                
                // Conta record
                $pageCount = $pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn();
                $moduleCount = $pdo->query("SELECT COUNT(*) FROM page_modules")->fetchColumn();
                $resultCount = $pdo->query("SELECT COUNT(*) FROM race_results")->fetchColumn();
                
                echo "<p><strong>Tabelle:</strong> $tableCount</p>";
                echo "<p><strong>Pagine:</strong> $pageCount</p>";
                echo "<p><strong>Moduli:</strong> $moduleCount</p>";
                echo "<p><strong>Risultati:</strong> $resultCount</p>";
                
            } catch(PDOException $e) {
                echo "<p class='error'>Database vuoto o non configurato</p>";
            }
            ?>
        <?php else: ?>
            <p><span class="status error">❌ Non connesso</span></p>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>🛠️ Azioni</h2>
        
        <?php if ($success): ?>
            <div class="info">
                <strong>Setup Database:</strong> Crea tutte le tabelle e inserisce dati di test
            </div>
            <a href="?action=setup" class="btn">🚀 Setup Database</a>
            
            <div class="info">
                <strong>Clear Database:</strong> Elimina e ricrea il database (ATTENZIONE: cancella tutto!)
            </div>
            <a href="?action=clear" class="btn btn-secondary" onclick="return confirm('Sei sicuro? Questo cancellerà tutto il database!')">🗑️ Clear Database</a>
            
            <div class="info">
                <strong>Test Pagina:</strong> Vai alla pagina principale per vedere il risultato
            </div>
            <a href="../index.php" class="btn">👀 Vai alla Pagina</a>
            <a href="../test.php" class="btn">🧪 Pagina Test</a>
            
        <?php else: ?>
            <div class="error">
                <strong>Impossibile procedere:</strong> Verifica la connessione al database
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>📋 Dati di Test Inclusi</h2>
        <ul>
            <li><strong>2 Gare:</strong> Marathon e Half Marathon 2025</li>
            <li><strong>15 Risultati:</strong> 10 marathon + 5 half marathon</li>
            <li><strong>3 Contenuti:</strong> News e sponsor</li>
            <li><strong>1 Pagina Home:</strong> Con 4 moduli configurati</li>
            <li><strong>6 Moduli:</strong> Hero, Results, Menu, Footer, RichText, Gallery</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>🎨 Personalizzazione</h2>
        <p>Dopo il setup, puoi personalizzare:</p>
        <ul>
            <li><strong>Colori:</strong> Modifica <code>assets/css/core/variables.css</code></li>
            <li><strong>Contenuti:</strong> Modifica direttamente nel database</li>
            <li><strong>Moduli:</strong> Aggiungi nuovi moduli in <code>modules/</code></li>
        </ul>
    </div>
</body>
</html>
