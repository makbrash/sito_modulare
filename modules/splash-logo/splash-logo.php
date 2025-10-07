<?php
/**
 * Splash Logo Module
 * Overlay animato con logo che appare all'inizio del caricamento pagina
 * 
 * @var ModuleRenderer $renderer
 * @var array $config
 */

$moduleData = $renderer->getModuleData('splashLogo', $config);

// Configurazione
$logoUrl = $config['logo_url'] ?? 'assets/images/splash.svg';
$duration = $config['duration'] ?? 2500; // millisecondi
$logoSize = $config['logo_size'] ?? 100; // px larghezza
$pulseSpeed = $config['pulse_speed'] ?? 2; // secondi
?>

<div class="splash-logo" 
     data-duration="<?= (int)$duration ?>"
     data-module="splashLogo">
    <div class="splash-logo__overlay">
        <div class="splash-logo__content">
            <img src="<?= htmlspecialchars($logoUrl) ?>" 
                 alt="Bologna Marathon Logo" 
                 class="splash-logo__image"
                 style="width: <?= (int)$logoSize ?>px;">
        </div>
    </div>
</div>

