<?php
/**
 * Bologna Marathon - Homepage
 * Sistema modulare SSR
 */

require_once 'config/database.php';
require_once 'core/ModuleRenderer.php';

// Inizializza connessione database
$database = new Database();
$db = $database->getConnection();

// Inizializza renderer moduli
$renderer = new ModuleRenderer($db);

try {
    // Controlla se è richiesta una pagina specifica per ID
    $pageId = isset($_GET['id_pagina']) ? (int)$_GET['id_pagina'] : null;
    
    if ($pageId) {
        // Renderizza pagina specifica con istanze di moduli
        $pageData = $renderer->renderPageById($pageId);
    } else {
        // Renderizza pagina home tradizionale
        $pageData = $renderer->renderPage('home');
    }
    
    $page = $pageData['page'];
    $modules = $pageData['modules'];
    $cssVariables = $pageData['css_variables'];
    $useInstances = $pageData['use_instances'] ?? false;
    
} catch (Exception $e) {
    die("Errore: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page['description']) ?>">
    
    <!-- CSS Build -->
    <link rel="stylesheet" href="assets/dist/css/main.min.css">
    <?php
        // Includi CSS vendor dai manifest dei moduli presenti nella pagina
        $vendorAssets = $renderer->collectVendorAssets($modules);
        if (!empty($vendorAssets['css'])) {
            foreach ($vendorAssets['css'] as $href) {
                echo '<link rel="stylesheet" href="' . htmlspecialchars($href) . '">';
            }
        }
    ?>
    
    <!-- CSS Variables dinamiche -->
    <?php if (!empty($cssVariables)): ?>
    <style>
        :root {
            <?php foreach ($cssVariables as $property => $value): ?>
                <?= $property ?>: <?= $value ?>;
            <?php endforeach; ?>
        }
    </style>
    <?php endif; ?>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;800&family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body class="page-<?= $page['template'] ?>">
    <!-- Skip Link -->
    <a href="#main-content" class="skip-link">Salta al contenuto principale</a>
    
    <?php
    // Renderizza menu se presente (deve essere fuori dal main per sticky)
    $menuModules = array_filter($modules, function($module) {
        if (is_array($module) && isset($module['module_name'])) {
            return $module['module_name'] === 'menu';
        }
        return false;
    });
    
    if (!empty($menuModules)) {
        $menuModule = reset($menuModules);
        $config = json_decode($menuModule['config'], true) ?? [];
        echo $renderer->renderModule('menu', $config);
    }
    ?>
    
    <!-- Main Content -->
    <main id="main-content" class="site-main">
        <?php if ($useInstances): ?>
            <!-- Renderizza istanze di moduli (escluso menu) -->
            <?php foreach ($modules as $instance): ?>
                <?php if ($instance['module_name'] !== 'menu'): ?>
                <div class="module-wrapper" data-module="<?= htmlspecialchars($instance['module_name']) ?>" 
                     data-instance="<?= htmlspecialchars($instance['instance_name']) ?>">
                    <?php
                    $config = json_decode($instance['config'], true) ?? [];
                    echo $renderer->renderModule($instance['module_name'], $config);
                    ?>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Renderizza moduli tradizionali (escluso menu) -->
            <?php foreach ($modules as $module): ?>
                <?php if ($module['module_name'] !== 'menu'): ?>
                <div class="module-wrapper" data-module="<?= htmlspecialchars($module['module_name']) ?>">
                    <?php
                    $config = json_decode($module['config'], true) ?? [];
                    echo $renderer->renderModule($module['module_name'], $config);
                    ?>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    
    
    
    <!-- JavaScript Build -->
    <script src="assets/dist/js/app.min.js"></script>
    <?php
        // Includi JS vendor dai manifest dei moduli presenti nella pagina
        if (!empty($vendorAssets['js'])) {
            foreach ($vendorAssets['js'] as $src) {
                echo '<script src="' . htmlspecialchars($src) . '"></script>';
            }
        }
    ?>
</body>
</html>
