<?php
/**
 * Modulo: Presentation
 * Descrizione: Sezione presentazione con layout 2 colonne
 * 
 * @var ModuleRenderer $renderer
 * @var array $config
 */

// Ottieni dati del modulo dal database
$moduleData = $renderer->getModuleData('presentation', $config);

// Valori di default con fallback
$title = $config['title'] ?? $moduleData['title'] ?? 'DOVE LO SPORT INCONTRA';
$subtitle = $config['subtitle'] ?? $moduleData['subtitle'] ?? 'LA STORIA';
$description1 = $config['description1'] ?? $moduleData['description1'] ?? 'La Maratona di Bologna è un grande evento dove lo sport si fonde con la storia, la cultura, l\'arte, la musica e il buon cibo di una delle città più antiche d\'Europa.';
$description2 = $config['description2'] ?? $moduleData['description2'] ?? 'Tre giorni di festa in Piazza Maggiore, una delle piazze più belle d\'Italia, offrendo a tutti i partecipanti diverse opportunità per visitare e scoprire una città unica.';
$image_url = $config['image_url'] ?? $moduleData['image_url'] ?? 'assets/images/marathon-start.jpg';
$image_alt = $config['image_alt'] ?? $moduleData['image_alt'] ?? 'Maratona di Bologna';

// Posizione immagine (sinistra o destra)
$image_position = $config['image_position'] ?? $moduleData['image_position'] ?? 'right';

// Statistiche configurabili
$stats = $config['stats'] ?? $moduleData['stats'] ?? [
    [
        'icon' => 'fas fa-running',
        'number' => '10.000+',
        'label' => 'Runner'
    ],
    [
        'icon' => 'fas fa-globe',
        'number' => '50+',
        'label' => 'Nazioni'
    ],
    [
        'icon' => 'fas fa-music',
        'number' => '3',
        'label' => 'Giorni di Eventi'
    ]
];

// Sanitizzazione output
$title = htmlspecialchars($title);
$subtitle = htmlspecialchars($subtitle);
$description1 = htmlspecialchars($description1);
$description2 = htmlspecialchars($description2);
$image_url = htmlspecialchars($image_url);
$image_alt = htmlspecialchars($image_alt);
$image_position = htmlspecialchars($image_position);
?>

<div class="presentation presentation--image-<?= $image_position ?>" 
     data-module="presentation" 
     data-config='<?= htmlspecialchars(json_encode($config)) ?>'>
    
    <div class="container-fluid">
        <div class="row row--align-center" style="min-height: 100vh;">
            
            <?php if ($image_position === 'left'): ?>
                <!-- Immagine a sinistra -->
                <div class="col-12 col-md-6">
                    <div class="">
                        <img src="<?= $image_url ?>" 
                             alt="<?= $image_alt ?>" 
                             class=""
                             loading="lazy">
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Contenuto testuale -->
            <div class="col-12 col-md-6">
                <div class="presentation_content">
                    
                    <!-- Titolo -->
                    <h2 class="presentation_title ">
                        <?= $title ?> <strong><?= $subtitle ?></strong>
                    </h2>
                    
                    <!-- Descrizioni -->
                    <p class="presentation__description">
                        <?= $description1 ?>
                    </p>
                    <p class="text-muted">
                        <?= $description2 ?>
                    </p>
                    
                    <!-- Statistiche come card-tag -->
                    <div class="presentation_stats">
                        <?php foreach ($stats as $stat): ?>
                            <div class="card-tag">
                                <div class="card-tag_icon">
                                    <i class="<?= ($stat['icon']) ?>"></i>
                                </div>
                                <div class="card-tag_content">
                                    <span class="card-tag_number"><?= ($stat['number']) ?></span>
                                    <span class="card-tag_label"><?= ($stat['label']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                </div>
            </div>
            
            <?php if ($image_position === 'right'): ?>
                <!-- Immagine a destra -->
                <div class="col-12 col-md-6">
                    <div class="presentation_image-wrapper">
                        <img src="<?= $image_url ?>" 
                             alt="<?= $image_alt ?>" 
                             class="presentation_image"
                             loading="lazy">
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>
