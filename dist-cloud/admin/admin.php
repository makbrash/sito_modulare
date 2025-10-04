<?php
/**
 * Admin Panel - Bologna Marathon
 * Pagina semplice per gestione dati
 */

require_once '../config/database.php';

// Inizializza connessione database
$database = new Database();
$db = $database->getConnection();

// Gestione azioni
$action = $_GET['action'] ?? 'dashboard';
$message = '';
$error = '';

// Inserimento nuovo risultato
if ($_POST['action'] ?? '' === 'add_result') {
    try {
        $sql = "INSERT INTO race_results (race_id, position, bib_number, runner_name, category, time_result) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_POST['race_id'],
            $_POST['position'],
            $_POST['bib_number'],
            $_POST['runner_name'],
            $_POST['category'],
            $_POST['time_result']
        ]);
        $message = "Risultato aggiunto con successo!";
    } catch (Exception $e) {
        $error = "Errore: " . $e->getMessage();
    }
}

// Inserimento nuovo contenuto
if ($_POST['action'] ?? '' === 'add_content') {
    try {
        $sql = "INSERT INTO dynamic_content (content_type, title, content, metadata) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_POST['content_type'],
            $_POST['title'],
            $_POST['content'],
            json_encode(['featured' => $_POST['featured'] ?? false])
        ]);
        $message = "Contenuto aggiunto con successo!";
    } catch (Exception $e) {
        $error = "Errore: " . $e->getMessage();
    }
}

// Aggiornamento pagina
if ($_POST['action'] ?? '' === 'update_page') {
    try {
        $sql = "UPDATE pages SET title = ?, description = ?, css_variables = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['css_variables'],
            $_POST['page_id']
        ]);
        $message = "Pagina aggiornata con successo!";
    } catch (Exception $e) {
        $error = "Errore: " . $e->getMessage();
    }
}

// Installazione modulo (esegue install.sql del manifest)
if ($action === 'modules' && isset($_GET['install'])) {
    $slug = $_GET['install'];
    $manifestPath = __DIR__ . '/../modules/' . $slug . '/module.json';
    if (file_exists($manifestPath)) {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (json_last_error() === JSON_ERROR_NONE && isset($manifest['install'])) {
            $sqlPath = __DIR__ . '/../modules/' . $manifest['install'];
            if (file_exists($sqlPath)) {
                $sql = file_get_contents($sqlPath);
                try {
                    $db->exec($sql);
                    $message = "Modulo '" . htmlspecialchars($slug) . "' installato con successo.";
                } catch (Exception $e) {
                    $error = "Errore installazione: " . $e->getMessage();
                }
            } else {
                $error = "File install.sql non trovato per '" . htmlspecialchars($slug) . "'.";
            }
        } else {
            $error = "Manifest non valido per '" . htmlspecialchars($slug) . "'.";
        }
    } else {
        $error = "Manifest non trovato per '" . htmlspecialchars($slug) . "'.";
    }
}

// Ottieni dati per visualizzazione
$pages = $db->query("SELECT * FROM pages")->fetchAll();
$results = $db->query("SELECT * FROM race_results ORDER BY race_id, position LIMIT 20")->fetchAll();
$contents = $db->query("SELECT * FROM dynamic_content ORDER BY created_at DESC LIMIT 10")->fetchAll();
$modules = $db->query("SELECT * FROM modules_registry")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Bologna Marathon</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .admin-header {
            background: #2C3E50;
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .admin-nav {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .admin-nav a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .admin-nav a.active {
            background: #D81E05;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .admin-section {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .admin-section h2 {
            color: #2C3E50;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #D81E05;
            padding-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .btn {
            background: #D81E05;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
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
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }
        
        .alert-error {
            background: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #D81E05;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            color: #666;
            font-size: 14px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .admin-nav {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🏃‍♂️ Admin Panel - Bologna Marathon</h1>
        <div class="admin-nav">
            <a href="?action=dashboard" class="<?= $action === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="?action=results" class="<?= $action === 'results' ? 'active' : '' ?>">Risultati</a>
            <a href="?action=content" class="<?= $action === 'content' ? 'active' : '' ?>">Contenuti</a>
            <a href="?action=pages" class="<?= $action === 'pages' ? 'active' : '' ?>">Pagine</a>
            <a href="?action=modules" class="<?= $action === 'modules' ? 'active' : '' ?>">Moduli</a>
            <a href="page-builder.php" class="<?= $action === 'page-builder' ? 'active' : '' ?>">Page Builder</a>
            <a href="../index.php" target="_blank">👀 Vai al Sito</a>
        </div>
    </div>
    
    <div class="admin-container">
        <?php if ($message): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($action === 'dashboard'): ?>
            <div class="admin-section">
                <h2>📊 Dashboard</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?= count($pages) ?></h3>
                        <p>Pagine</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= count($results) ?></h3>
                        <p>Risultati</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= count($contents) ?></h3>
                        <p>Contenuti</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= count($modules) ?></h3>
                        <p>Moduli</p>
                    </div>
                </div>
                
                <h3>🔗 Link Utili</h3>
                <p>
                    <a href="../index.php" target="_blank" class="btn">👀 Vai al Sito</a>
                    <a href="test-setup.php" class="btn btn-secondary">🔧 Setup Database</a>
                    <a href="../debug.php" class="btn btn-secondary">🐛 Debug</a>
                </p>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'results'): ?>
            <div class="admin-section">
                <h2>🏃‍♂️ Gestione Risultati</h2>
                
                <h3>➕ Aggiungi Nuovo Risultato</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add_result">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Gara ID</label>
                            <select name="race_id" required>
                                <option value="1">Marathon 2025</option>
                                <option value="2">Half Marathon 2025</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Posizione</label>
                            <input type="number" name="position" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pettorale</label>
                            <input type="text" name="bib_number" required>
                        </div>
                        <div class="form-group">
                            <label>Categoria</label>
                            <select name="category" required>
                                <option value="M">Maschile</option>
                                <option value="F">Femminile</option>
                                <option value="M40">M40</option>
                                <option value="F40">F40</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nome Runner</label>
                            <input type="text" name="runner_name" required>
                        </div>
                        <div class="form-group">
                            <label>Tempo (HH:MM:SS)</label>
                            <input type="time" name="time_result" step="1" required>
                        </div>
                    </div>
                    <button type="submit" class="btn">➕ Aggiungi Risultato</button>
                </form>
                
                <h3>📋 Ultimi Risultati</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Pettorale</th>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Tempo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                        <tr>
                            <td><?= $result['position'] ?></td>
                            <td><?= htmlspecialchars($result['bib_number']) ?></td>
                            <td><?= htmlspecialchars($result['runner_name']) ?></td>
                            <td><?= htmlspecialchars($result['category']) ?></td>
                            <td><?= $result['time_result'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'content'): ?>
            <div class="admin-section">
                <h2>📝 Gestione Contenuti</h2>
                
                <h3>➕ Aggiungi Nuovo Contenuto</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add_content">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tipo Contenuto</label>
                            <select name="content_type" required>
                                <option value="news">News</option>
                                <option value="sponsor">Sponsor</option>
                                <option value="info">Info</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Titolo</label>
                            <input type="text" name="title" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Contenuto</label>
                        <textarea name="content" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="featured" value="1">
                            Contenuto in evidenza
                        </label>
                    </div>
                    <button type="submit" class="btn">➕ Aggiungi Contenuto</button>
                </form>
                
                <h3>📋 Ultimi Contenuti</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Titolo</th>
                            <th>Contenuto</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contents as $content): ?>
                        <tr>
                            <td><?= htmlspecialchars($content['content_type']) ?></td>
                            <td><?= htmlspecialchars($content['title']) ?></td>
                            <td><?= htmlspecialchars(substr($content['content'], 0, 50)) ?>...</td>
                            <td><?= date('d/m/Y', strtotime($content['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'modules'): ?>
            <div class="admin-section">
                <h2>🧩 Moduli</h2>
                <p>Elenco dei moduli registrati nel sistema. Clicca su Installa per eseguire l'installazione del modulo (se il manifest contiene install.sql).</p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Percorso</th>
                            <th>Classe CSS</th>
                            <th>Attivo</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $module): ?>
                        <tr>
                            <td><?= htmlspecialchars($module['name']) ?></td>
                            <td><?= htmlspecialchars($module['component_path']) ?></td>
                            <td><?= htmlspecialchars($module['css_class']) ?></td>
                            <td><?= $module['is_active'] ? '✅' : '❌' ?></td>
                            <td>
                                <?php $slug = htmlspecialchars($module['name']); ?>
                                <a class="btn btn-secondary" href="?action=modules&install=<?= $slug ?>">Installa</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top: 1rem;">
                    <a href="sync-modules.php" class="btn">🔄 Sincronizza dal filesystem</a>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($action === 'pages'): ?>
            <div class="admin-section">
                <h2>📄 Gestione Pagine</h2>
                
                <h3>✏️ Modifica Pagina Home</h3>
                <?php $homePage = $pages[0] ?? null; ?>
                <?php if ($homePage): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="update_page">
                    <input type="hidden" name="page_id" value="<?= $homePage['id'] ?>">
                    <div class="form-group">
                        <label>Titolo</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($homePage['title']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Descrizione</label>
                        <textarea name="description" required><?= htmlspecialchars($homePage['description']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>CSS Variables (JSON)</label>
                        <textarea name="css_variables" required><?= htmlspecialchars($homePage['css_variables']) ?></textarea>
                    </div>
                    <button type="submit" class="btn">💾 Salva Modifiche</button>
                </form>
                <?php endif; ?>
                
                <h3>📋 Tutte le Pagine</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Slug</th>
                            <th>Titolo</th>
                            <th>Status</th>
                            <th>Template</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                        <tr>
                            <td><?= $page['id'] ?></td>
                            <td><?= htmlspecialchars($page['slug']) ?></td>
                            <td><?= htmlspecialchars($page['title']) ?></td>
                            <td><?= $page['status'] ?></td>
                            <td><?= htmlspecialchars($page['template']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
