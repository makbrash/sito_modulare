<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installazione Sistema Modelli</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            padding: 3rem;
        }
        
        h1 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.125rem;
        }
        
        .step {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
        }
        
        .step h2 {
            color: #667eea;
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
        }
        
        .step p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1.125rem;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            display: inline-block;
            text-decoration: none;
            font-weight: 600;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #28a745;
            margin-bottom: 1.5rem;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
            margin-bottom: 1.5rem;
        }
        
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #17a2b8;
            margin-bottom: 1.5rem;
        }
        
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.875rem;
            line-height: 1.4;
        }
        
        .status-list {
            list-style: none;
            margin-top: 1rem;
        }
        
        .status-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .status-list li:last-child {
            border-bottom: none;
        }
        
        .status-ok {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-missing {
            color: #dc3545;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎨 Sistema Modelli Moduli Globali</h1>
        <p class="subtitle">Installazione e Verifica Database</p>
        
        <?php
        require_once __DIR__ . '/../config/database.php';
        
        // Inizializza connessione database
        $database = new Database();
        $db = $database->getConnection();
        
        $status = [];
        $hasErrors = false;
        
        // Verifica connessione database
        try {
            $db->query('SELECT 1');
            $status[] = ['✓ Connessione database', 'ok'];
        } catch (Exception $e) {
            $status[] = ['✗ Connessione database: ' . $e->getMessage(), 'error'];
            $hasErrors = true;
        }
        
        // Verifica tabella module_instances
        try {
            $db->query('SELECT 1 FROM module_instances LIMIT 1');
            $status[] = ['✓ Tabella module_instances presente', 'ok'];
        } catch (Exception $e) {
            $status[] = ['✗ Tabella module_instances non trovata', 'error'];
            $hasErrors = true;
        }
        
        // Verifica colonne template
        $columnsToCheck = ['is_template', 'template_name', 'template_instance_id'];
        $existingColumns = [];
        $pageIdNullable = false;
        
        try {
            $stmt = $db->query("SHOW COLUMNS FROM module_instances");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($columns as $col) {
                $colName = $col['Field'];
                
                // Verifica se page_id accetta NULL
                if ($colName === 'page_id') {
                    $pageIdNullable = ($col['Null'] === 'YES');
                    if ($pageIdNullable) {
                        $status[] = ["✓ Colonna 'page_id' accetta NULL", 'ok'];
                    } else {
                        $status[] = ["✗ Colonna 'page_id' non accetta NULL (necessario per template)", 'missing'];
                    }
                }
                
                // Verifica colonne template
                if (in_array($colName, $columnsToCheck)) {
                    $existingColumns[] = $colName;
                    $status[] = ["✓ Colonna '$colName' presente", 'ok'];
                }
            }
            
            // Verifica colonne mancanti
            foreach ($columnsToCheck as $col) {
                if (!in_array($col, $existingColumns)) {
                    $status[] = ["✗ Colonna '$col' mancante", 'missing'];
                }
            }
        } catch (Exception $e) {
            $status[] = ['✗ Errore verifica colonne: ' . $e->getMessage(), 'error'];
            $hasErrors = true;
        }
        
        // Se ci sono colonne mancanti o page_id non nullable, mostra pulsante installazione
        $missingColumns = array_diff($columnsToCheck, $existingColumns);
        $needsInstall = !empty($missingColumns) || !$pageIdNullable;
        
        if ($needsInstall) {
            echo '<div class="info">';
            echo '<strong>⚠️ Installazione/Fix necessaria</strong><br>';
            if (!empty($missingColumns)) {
                echo 'Alcune colonne del sistema modelli non sono presenti.<br>';
            }
            if (!$pageIdNullable) {
                echo 'La colonna page_id deve permettere valori NULL per i template master.<br>';
            }
            echo 'Clicca sul pulsante per applicare le modifiche necessarie.';
            echo '</div>';
        } else {
            echo '<div class="success">';
            echo '<strong>✓ Sistema già installato</strong><br>';
            echo 'Tutte le colonne necessarie sono presenti e configurate correttamente. Il sistema è pronto all\'uso!';
            echo '</div>';
        }
        
        // Se richiesta installazione
        if (isset($_POST['install'])) {
            echo '<div class="step">';
            echo '<h2>📦 Installazione in corso...</h2>';
            
            try {
                // Aggiungi colonne se non esistono
                $sqlStatements = [];
                
                // IMPORTANTE: Permetti page_id NULL per template master
                $sqlStatements[] = "ALTER TABLE module_instances MODIFY COLUMN page_id INT DEFAULT NULL";
                
                if (!in_array('is_template', $existingColumns)) {
                    $sqlStatements[] = "ALTER TABLE module_instances ADD COLUMN is_template BOOLEAN DEFAULT FALSE COMMENT 'Indica se questa istanza è un modello master'";
                    $sqlStatements[] = "ALTER TABLE module_instances ADD INDEX idx_is_template (is_template)";
                }
                
                if (!in_array('template_name', $existingColumns)) {
                    $sqlStatements[] = "ALTER TABLE module_instances ADD COLUMN template_name VARCHAR(200) DEFAULT NULL COMMENT 'Nome del modello (solo per is_template=1)'";
                }
                
                if (!in_array('template_instance_id', $existingColumns)) {
                    $sqlStatements[] = "ALTER TABLE module_instances ADD COLUMN template_instance_id INT DEFAULT NULL COMMENT 'ID istanza master se usa un template'";
                    $sqlStatements[] = "ALTER TABLE module_instances ADD INDEX idx_template_instance (template_instance_id)";
                }
                
                // Esegui statements
                foreach ($sqlStatements as $sql) {
                    $db->exec($sql);
                    echo '<p>✓ ' . substr($sql, 0, 60) . '...</p>';
                }
                
                // Aggiungi foreign key se non esiste
                try {
                    $stmt = $db->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                                       WHERE TABLE_SCHEMA = DATABASE() 
                                       AND TABLE_NAME = 'module_instances' 
                                       AND CONSTRAINT_NAME = 'fk_template_instance'");
                    $fkExists = $stmt->fetch();
                    
                    if (!$fkExists && !in_array('template_instance_id', $existingColumns)) {
                        $db->exec("ALTER TABLE module_instances 
                                  ADD CONSTRAINT fk_template_instance 
                                  FOREIGN KEY (template_instance_id) 
                                  REFERENCES module_instances(id) 
                                  ON DELETE SET NULL");
                        echo '<p>✓ Foreign key aggiunta</p>';
                    }
                } catch (Exception $e) {
                    echo '<p>⚠️ Foreign key: ' . $e->getMessage() . '</p>';
                }
                
                echo '<div class="success" style="margin-top: 1rem;">';
                echo '<strong>✓ Installazione completata!</strong><br>';
                echo 'Il sistema modelli è ora attivo e funzionante. Ricaricamento in corso...';
                echo '</div>';
                
                // Redirect dopo 2 secondi per mostrare status aggiornato
                echo '<script>setTimeout(function() { window.location.href = "install-templates.php"; }, 2000);</script>';
                
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<strong>✗ Errore durante l\'installazione</strong><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        // Mostra status
        echo '<div class="step">';
        echo '<h2>📊 Status Sistema</h2>';
        echo '<ul class="status-list">';
        foreach ($status as $item) {
            $class = $item[1] === 'ok' ? 'status-ok' : ($item[1] === 'missing' ? 'status-missing' : 'error');
            echo '<li class="' . $class . '">' . $item[0] . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        
        // Pulsante installazione se necessario
        if ($needsInstall && !isset($_POST['install'])) {
            echo '<form method="POST">';
            echo '<button type="submit" name="install" class="btn">🚀 Installa/Ripara Sistema Modelli</button>';
            echo '</form>';
        }
        
        // Statistiche se sistema installato
        if (!$needsInstall) {
            echo '<div class="step">';
            echo '<h2>📈 Statistiche</h2>';
            
            try {
                // Conta template esistenti
                $stmt = $db->query("SELECT COUNT(*) FROM module_instances WHERE is_template = TRUE");
                $templateCount = $stmt->fetchColumn();
                
                // Conta istanze che usano template
                $stmt = $db->query("SELECT COUNT(*) FROM module_instances WHERE template_instance_id IS NOT NULL");
                $usingTemplateCount = $stmt->fetchColumn();
                
                echo '<p><strong>Modelli creati:</strong> ' . $templateCount . '</p>';
                echo '<p><strong>Istanze che usano modelli:</strong> ' . $usingTemplateCount . '</p>';
                
                // Mostra template esistenti
                $stmt = $db->query("SELECT id, module_name, template_name, created_at 
                                   FROM module_instances 
                                   WHERE is_template = TRUE 
                                   ORDER BY created_at DESC");
                $templates = $stmt->fetchAll();
                
                if (!empty($templates)) {
                    echo '<h3 style="margin-top: 1.5rem;">Modelli Disponibili:</h3>';
                    echo '<ul class="status-list">';
                    foreach ($templates as $tpl) {
                        echo '<li><strong>' . htmlspecialchars($tpl['template_name']) . '</strong> ';
                        echo '(ID: ' . $tpl['id'] . ', Tipo: ' . $tpl['module_name'] . ')</li>';
                    }
                    echo '</ul>';
                }
                
            } catch (Exception $e) {
                echo '<p>⚠️ Errore nel caricamento statistiche</p>';
            }
            
            echo '</div>';
            
            echo '<div class="step">';
            echo '<h2>📚 Prossimi Passi</h2>';
            echo '<ol>';
            echo '<li>Vai al <strong>Page Builder</strong></li>';
            echo '<li>Seleziona un modulo (es. menu)</li>';
            echo '<li>Configura il modulo</li>';
            echo '<li>Click su <strong>"Salva come Modello Globale"</strong></li>';
            echo '<li>Usa il template in altre pagine!</li>';
            echo '</ol>';
            echo '<p style="margin-top: 1rem;"><a href="page-builder.php" class="btn">Vai al Page Builder</a></p>';
            echo '</div>';
            
            echo '<div class="info">';
            echo '<strong>📖 Documentazione</strong><br>';
            echo 'Consulta <code>MODULE-TEMPLATES-GUIDE.md</code> per la guida completa.';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>

