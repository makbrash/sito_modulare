<?php
/**
 * Modulo: Highlights
 * Descrizione: Sezione news con slider Swiper
 * 
 * @var ModuleRenderer $renderer
 * @var array $config
 */

// Ottieni dati del modulo dal database
$moduleData = $renderer->getModuleData('highlights', $config);

// Valori di default con fallback
$title = $config['title'] ?? $moduleData['title'] ?? 'Ultime NEWS';

// Highlights configurabili
$highlights = $config['highlights'] ?? $moduleData['highlights'] ?? [
    [
        'image' => 'assets/images/marathon-start.jpg',
        'title' => 'Presentata la Termal Bologna Marathon 2025: in 10.000 runner attesi',
        'url' => '#'
    ],
    [
        'image' => 'assets/images/marathon-start.jpg',
        'title' => 'Termal Bologna Marathon: tutte le chiusure delle strade del 2 Marzo',
        'url' => '#'
    ],
    [
        'image' => 'assets/images/marathon-start.jpg',
        'title' => 'DOCUFILM "THE SECRET IS BOLOGNA"',
        'url' => '#'
    ],
    [
        'image' => 'assets/images/marathon-start.jpg',
        'title' => 'INFORMAZIONI UTILI - TERMAL BOLOGNA MARATHON 2025',
        'url' => '#'
    ],
    [
        'image' => 'assets/images/marathon-start.jpg',
        'title' => 'Anche la 30 Km dei Portici è SOLD OUT!',
        'url' => '#'
    ]
];

// Sanitizzazione output
$title = htmlspecialchars($title);
?>

<div class="highlights" data-module="highlights" data-config='<?= htmlspecialchars(json_encode($config)) ?>'>
    
    <div class="container-fluid">
        <h2 class="highlights_title"><?= $title ?></h2>
        
        <div class="swiper highlights_swiper">
            <div class="swiper-wrapper">
                <?php foreach ($highlights as $highlight): ?>
                    <div class="swiper-slide">
                        <a href="<?= htmlspecialchars($highlight['url']) ?>" class="highlight_card">
                            <div class="highlight_image-wrapper">
                                <img src="<?= htmlspecialchars($highlight['image']) ?>" 
                                     alt="<?= htmlspecialchars($highlight['title']) ?>"
                                     class="highlight_image"
                                     loading="lazy">
                            </div>
                            <div class="highlight_content">
                                <h5 class="highlight_title"><?= htmlspecialchars($highlight['title']) ?></h5>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Navigation -->
            <div class="highlights_navigation">
                <button class="highlights_btn highlights_btn--prev">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="highlights_btn highlights_btn--next">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>