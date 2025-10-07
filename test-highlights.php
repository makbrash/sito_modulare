<?php
/**
 * Test Modulo Highlights con Sistema Modulare
 */

require_once 'config/database.php';
require_once 'core/ModuleRenderer.php';

$database = new Database();
$db = $database->getConnection();
$renderer = new ModuleRenderer($db);

// Simula moduli per la pagina
$modules = [
    [
        'module_name' => 'highlights',
        'config' => json_encode([
            'title' => 'TEST HIGHLIGHTS - Ultime NEWS'
        ])
    ]
];

// Raccogli vendor assets
$vendorAssets = $renderer->collectVendorAssets($modules);
$moduleAssets = $renderer->collectModuleAssets($modules);

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Modulo Highlights - Sistema PHP</title>
    
    <!-- CSS Core -->
    <link rel="stylesheet" href="assets/css/core/variables.css">
    <link rel="stylesheet" href="assets/css/core/colors.css">
    <link rel="stylesheet" href="assets/css/core/reset.css">
    <link rel="stylesheet" href="assets/css/core/typography.css">
    <link rel="stylesheet" href="assets/css/core/layout.css">
    <link rel="stylesheet" href="assets/css/core/fonts.css">
    
    <!-- Vendor CSS -->
    <?php if (!empty($vendorAssets['css'])): ?>
        <?php foreach ($vendorAssets['css'] as $href): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($href) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Module CSS -->
    <?php if (!empty($moduleAssets['css'])): ?>
        <?php foreach ($moduleAssets['css'] as $href): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($href) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: #f5f5f5;
            padding: 20px;
        }
        .test-info {
            background: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-info h1 {
            margin: 0 0 10px 0;
            color: #23a8eb;
        }
        .test-info pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }
        .asset-list {
            margin: 10px 0;
        }
        .asset-item {
            padding: 5px;
            background: #e9ecef;
            margin: 3px 0;
            border-radius: 3px;
            font-family: monospace;
            font-size: 12px;
        }
        .asset-item.success {
            background: #d4edda;
        }
        .asset-item.error {
            background: #f8d7da;
        }
    </style>
</head>
<body>
    
    <div class="test-info">
        <h1>🧪 Test Modulo Highlights - Sistema PHP</h1>
        
        <h3>📦 Vendor CSS Assets (<?= count($vendorAssets['css']) ?>)</h3>
        <div class="asset-list">
            <?php if (empty($vendorAssets['css'])): ?>
                <div class="asset-item error">❌ NESSUN VENDOR CSS CARICATO!</div>
            <?php else: ?>
                <?php foreach ($vendorAssets['css'] as $href): ?>
                    <div class="asset-item success">✅ <?= htmlspecialchars($href) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <h3>📦 Vendor JS Assets (<?= count($vendorAssets['js']) ?>)</h3>
        <div class="asset-list">
            <?php if (empty($vendorAssets['js'])): ?>
                <div class="asset-item error">❌ NESSUN VENDOR JS CARICATO!</div>
            <?php else: ?>
                <?php foreach ($vendorAssets['js'] as $src): ?>
                    <div class="asset-item success">✅ <?= htmlspecialchars($src) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <h3>🎨 Module CSS Assets (<?= count($moduleAssets['css']) ?>)</h3>
        <div class="asset-list">
            <?php if (empty($moduleAssets['css'])): ?>
                <div class="asset-item error">❌ NESSUN MODULE CSS CARICATO!</div>
            <?php else: ?>
                <?php foreach ($moduleAssets['css'] as $href): ?>
                    <div class="asset-item success">✅ <?= htmlspecialchars($href) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <h3>⚡ Module JS Assets (<?= count($moduleAssets['js']) ?>)</h3>
        <div class="asset-list">
            <?php if (empty($moduleAssets['js'])): ?>
                <div class="asset-item error">❌ NESSUN MODULE JS CARICATO!</div>
            <?php else: ?>
                <?php foreach ($moduleAssets['js'] as $src): ?>
                    <div class="asset-item success">✅ <?= htmlspecialchars($src) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <h3>📋 module.json Debug</h3>
        <pre><?php
            $manifestPath = __DIR__ . '/modules/highlights/module.json';
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                echo json_encode($manifest['assets'] ?? [], JSON_PRETTY_PRINT);
            } else {
                echo "❌ module.json non trovato!";
            }
        ?></pre>
    </div>

    <!-- RENDER MODULO -->
    <?php
    $config = [
        'title' => 'TEST HIGHLIGHTS - Ultime NEWS',
        'highlights' => [
            ['image' => 'assets/images/marathon-start.jpg', 'title' => 'News 1', 'url' => '#'],
            ['image' => 'assets/images/marathon-start.jpg', 'title' => 'News 2', 'url' => '#'],
            ['image' => 'assets/images/marathon-start.jpg', 'title' => 'News 3', 'url' => '#'],
            ['image' => 'assets/images/marathon-start.jpg', 'title' => 'News 4', 'url' => '#'],
            ['image' => 'assets/images/marathon-start.jpg', 'title' => 'News 5', 'url' => '#'],
            ['image' => 'assets/images/marathon-start.jpg', 'title' => 'News 6', 'url' => '#'],
            ['image' => 'assets/images/marathon-start.jpg', 'title' => 'News 7', 'url' => '#'],
            ['image' => 'assets/images/marathon-start.jpg', 'title' => 'News 8', 'url' => '#'],
        ]
    ];
    
    echo $renderer->renderModule('highlights', $config);
    ?>

    <!-- JavaScript -->
    
    <!-- Vendor JS -->
    <?php if (!empty($vendorAssets['js'])): ?>
        <?php foreach ($vendorAssets['js'] as $src): ?>
            <script src="<?= htmlspecialchars($src) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Module JS -->
    <?php if (!empty($moduleAssets['js'])): ?>
        <?php foreach ($moduleAssets['js'] as $src): ?>
            <script src="<?= htmlspecialchars($src) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Test Script -->
    <script>
        console.log('=== DEBUG HIGHLIGHTS ===');
        console.log('Swiper disponibile:', typeof Swiper !== 'undefined');
        console.log('Highlights class disponibile:', typeof window.Highlights !== 'undefined');
        console.log('Elemento .highlights:', document.querySelector('.highlights'));
        console.log('Swiper container:', document.querySelector('.highlights_swiper'));
        
        setTimeout(() => {
            const swiperEl = document.querySelector('.highlights_swiper');
            if (swiperEl && swiperEl.swiper) {
                console.log('✅ Swiper inizializzato!');
                console.log('SlidesPerView:', swiperEl.swiper.params.slidesPerView);
                console.log('Totale slide:', swiperEl.swiper.slides.length);
                console.log('Space between:', swiperEl.swiper.params.spaceBetween);
                console.log('Breakpoints:', swiperEl.swiper.params.breakpoints);
            } else {
                console.error('❌ Swiper NON inizializzato!');
            }
        }, 1000);
    </script>
</body>
</html>

