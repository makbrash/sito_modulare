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
    // Reset moduli annidati per nuova pagina
    $renderer->resetNestedModules();
    
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
$moduleTree = $pageData['module_tree'] ?? [];

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

/**
 * Render ricorsivo delle istanze modulo con supporto annidamento
 */
function renderModuleInstanceNode(ModuleRenderer $renderer, array $node): void
{
    $config = json_decode($node['config'] ?? '[]', true) ?? [];
    $hasInlineHtml = isset($config['__inline_html']) && $config['__inline_html'] !== '';

    echo '<div class="module-wrapper" data-module="' . htmlspecialchars($node['module_name']) . '"'
        . ' data-instance="' . htmlspecialchars($node['instance_name']) . '"'
        . ' data-instance-id="' . (int)$node['id'] . '"';

    if (!empty($node['parent_instance_id'])) {
        echo ' data-parent-id="' . (int)$node['parent_instance_id'] . '"';
    }

    echo '>';

    if ($hasInlineHtml) {
        echo $config['__inline_html'];
    } else {
        echo $renderer->renderModule($node['module_name'], $config);
    }

    if (!empty($node['children'])) {
        echo '<div class="module-children">';
        foreach ($node['children'] as $childNode) {
            renderModuleInstanceNode($renderer, $childNode);
        }
        echo '</div>';
    }

    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="it" class="<?= htmlspecialchars($bodyClass) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page['description']) ?>">
    
    <!-- CSS DEV/PROD -->
    <?php
        // DEV se NON stiamo eseguendo dalla cartella build
        $isDev = (strpos(__DIR__, DIRECTORY_SEPARATOR . 'build') === false);
        if ($isDev) {
            // DEV: carica core + CSS moduli deduplicati
            $coreCss = [
                'assets/css/core/variables.css',
                'assets/css/core/colors.css',
                'assets/css/core/reset.css',
                'assets/css/core/typography.css',
                'assets/css/core/layout.css',
                'assets/css/core/fonts.css',
                'assets/css/core/scrollbar.css',
            ];
            foreach ($coreCss as $href) {
                if (file_exists(__DIR__ . '/' . $href)) {
                    echo '<link rel="stylesheet" href="' . htmlspecialchars($href) . '">';
                }
            }
            // Vendor CSS
            $vendorAssets = $renderer->collectVendorAssets($modules);
            if (!empty($vendorAssets['css'])) {
                foreach ($vendorAssets['css'] as $href) {
                    echo '<link rel="stylesheet" href="' . htmlspecialchars($href) . '">';
                }
            }

            // Pre-rendering per tracciare moduli annidati
            ob_start();
            foreach ($modules as $module) {
                $moduleName = $module['module_name'];
                if ($moduleName !== 'menu' && $moduleName !== 'splash-logo' && $moduleName !== 'splashLogo') {
                    $config = json_decode($module['config'], true) ?? [];
                    $renderer->renderModule($moduleName, $config);
                }
            }
            ob_end_clean();
            
            // Ora raccogli asset con moduli annidati tracciati
            $moduleAssets = $renderer->collectModuleAssets($modules);
            if (!empty($moduleAssets['css'])) {
                foreach ($moduleAssets['css'] as $href) {
                    echo '<link rel="stylesheet" href="' . htmlspecialchars($href) . '">';
                }
            }
        } else {
            // PROD: un solo bundle
            echo '<link rel="stylesheet" href="build/assets/css/main.min.css">';
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
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
</head>
<body class="page-<?= $page['template'] ?> production">
    
    <?php
    // Renderizza Splash Logo se presente (deve essere fuori dal main per overlay fullscreen)
    $splashModules = array_filter($modules, function($module) {
        if (is_array($module) && isset($module['module_name'])) {
            return $module['module_name'] === 'splash-logo' || $module['module_name'] === 'splashLogo';
        }
        return false;
    });
    
    if (!empty($splashModules)) {
        $splashModule = reset($splashModules);
        $config = json_decode($splashModule['config'], true) ?? [];
        echo $renderer->renderModule('splashLogo', $config);
    }
    ?>
    
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
            <!-- Renderizza istanze di moduli (escluso menu e splash-logo) -->
            <?php foreach ($moduleTree as $node): ?>
                <?php 
                $moduleName = $node['module_name'] ?? '';
                if ($moduleName === 'menu' || $moduleName === 'splash-logo' || $moduleName === 'splashLogo') { 
                    continue; 
                } 
                ?>
                <?php renderModuleInstanceNode($renderer, $node); ?>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Renderizza moduli tradizionali (escluso menu e splash-logo) -->
            <?php foreach ($modules as $module): ?>
                <?php 
                $moduleName = $module['module_name'];
                if ($moduleName !== 'menu' && $moduleName !== 'splash-logo' && $moduleName !== 'splashLogo'): 
                ?>
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
    
    
    <!-- JavaScript DEV/PROD -->
    <?php if ($isDev): ?>
        <?php
            // Vendor JS
            $vendorAssets = $vendorAssets ?? $renderer->collectVendorAssets($modules);
            if (!empty($vendorAssets['js'])) {
                foreach ($vendorAssets['js'] as $src) {
                    echo '<script src="' . htmlspecialchars($src) . '"></script>';
                }
            }

            $moduleAssets = $moduleAssets ?? $renderer->collectModuleAssets($modules);
            // Core JS (se esiste)
            $coreJs = [
                'assets/js/core/app.js',
                'assets/js/core/image-3d.js'
            ];
            foreach ($coreJs as $src) {
                if (file_exists(__DIR__ . '/' . $src)) {
                    echo '<script src="' . htmlspecialchars($src) . '"></script>';
                }
            }
            
            // JS moduli deduplicati
            if (!empty($moduleAssets['js'])) {
                foreach ($moduleAssets['js'] as $src) {
                    echo '<script src="' . htmlspecialchars($src) . '"></script>';
                }
            }
        ?>
    <?php else: ?>
        <script src="build/assets/js/app.min.js"></script>
    <?php endif; ?>
</body>
</html>
