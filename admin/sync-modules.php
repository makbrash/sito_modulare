<?php
/**
 * Sync Modules - Auto-registrazione moduli da filesystem
 * Legge i module.json e sincronizza con il database
 */

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Percorso moduli
$modulesPath = __DIR__ . '/../modules/';

// Moduli registrati
$registered = [];
$errors = [];

// Scansiona cartelle moduli
$moduleDirs = array_filter(glob($modulesPath . '*'), 'is_dir');

foreach ($moduleDirs as $moduleDir) {
    $moduleName = basename($moduleDir);
    $manifestPath = $moduleDir . '/module.json';
    
    if (!file_exists($manifestPath)) {
        continue;
    }
    
    // Leggi manifest
    $manifest = json_decode(file_get_contents($manifestPath), true);
    
    if (!$manifest) {
        $errors[] = "Errore lettura manifest: $moduleName";
        continue;
    }
    
    // Prepara dati per database
    $name = $manifest['name'] ?? $moduleName;
    $componentPath = $manifest['component_path'] ?? "$moduleName/$moduleName.php";
    $cssClass = $manifest['slug'] ?? $moduleName;
    $defaultConfig = json_encode($manifest['default_config'] ?? []);
    $isActive = $manifest['is_active'] ?? true;
    
    try {
        // Inserisci o aggiorna
        $stmt = $db->prepare("
            INSERT INTO modules_registry (name, component_path, css_class, default_config, is_active) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                component_path = VALUES(component_path),
                css_class = VALUES(css_class),
                default_config = VALUES(default_config),
                is_active = VALUES(is_active)
        ");
        
        $stmt->execute([$name, $componentPath, $cssClass, $defaultConfig, $isActive ? 1 : 0]);
        
        $registered[] = $name;
        
    } catch (Exception $e) {
        $errors[] = "Errore registrazione $name: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Moduli - Bologna Marathon</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
        
        .module-list {
            list-style: none;
            margin-top: 1rem;
        }
        
        .module-list li {
            padding: 0.75rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .module-list li:last-child {
            border-bottom: none;
        }
        
        .module-list i {
            color: #28a745;
            font-size: 1.25rem;
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
            margin-top: 1rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-sync"></i> Sincronizzazione Moduli</h1>
        <p class="subtitle">Auto-registrazione da filesystem</p>
        
        <?php if (!empty($registered)): ?>
            <div class="success">
                <strong><i class="fas fa-check-circle"></i> Sincronizzazione completata!</strong>
                <p style="margin-top: 0.5rem;">Moduli registrati: <?= count($registered) ?></p>
                <ul class="module-list">
                    <?php foreach ($registered as $mod): ?>
                        <li><i class="fas fa-cube"></i> <?= htmlspecialchars($mod) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <strong><i class="fas fa-exclamation-triangle"></i> Errori durante la sincronizzazione:</strong>
                <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <a href="page-builder.php" class="btn">
            <i class="fas fa-edit"></i> Vai al Page Builder
        </a>
    </div>
</body>
</html>
