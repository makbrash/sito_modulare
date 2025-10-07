<?php
/**
 * Bologna Marathon - Homepage PROD
 * Sistema modulare SSR - Versione Produzione
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
    
    // Ottieni tema della pagina
    $pageTheme = $renderer->getPageTheme($page['id']);
    // Evita duplicazione del prefisso "race-"
    if (strpos($pageTheme, 'race-') === 0) {
        $bodyClass = $pageTheme;
    } else {
        $bodyClass = "race-{$pageTheme}";
    }
    
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
    
    <!-- CSS PROD -->
    <link rel="stylesheet" href="assets/css/main.min.css">
    
    <!-- Splash Logo CSS (sempre incluso) -->
    <link rel="stylesheet" href="modules/splash-logo/splash-logo.css">
    
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
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
</head>
<body class="page-<?= $page['template'] ?> <?= htmlspecialchars($bodyClass) ?>">
    <!-- Splash Logo Module -->
    <?php echo $renderer->renderModule('splashLogo', []); ?>
    
    <!-- Skip Link per accessibilità -->
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
    
    <!-- JavaScript PROD - Bundle unico -->
    <script src="assets/js/app.min.js"></script>
    
    <!-- Splash Logo JS (sempre incluso) -->
    <script src="modules/splash-logo/splash-logo.js"></script>
</body>
</html>
