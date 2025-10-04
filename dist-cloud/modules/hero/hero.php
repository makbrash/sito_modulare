<?php
/**
 * Action Hero Module
 * Modulo hero con layout flessibile
 */

$moduleData = $renderer->getModuleData('actionHero', $config);
$layout = $config['layout'] ?? '2col';
$height = $config['height'] ?? '100vh';
?>

<section class="hero-module" style="--hero-height: <?= $height ?>">
    <div class="hero-container">
        <!-- Layout basato sul design dell'immagine -->
        <div class="hero-content">
            <div class="hero-text">
                <div class="hero-date-tag">
                    <i class="fas fa-calendar-alt"></i>
                    <span>2 MARZO 2026</span>
                </div>
                <h1 class="hero-title">THERMAL BOLOGNA MARATHON xxxx</h1>
                <p class="hero-subtitle">Corri Attraverso la Storia</p>
                <p class="hero-description">
                    Tre percorsi unici nel cuore di Bologna. Scegli la tua sfida e vivi 
                    un'esperienza indimenticabile tra storia, cultura e sport.
                </p>
                       <div class="hero-actions">
                           <?php
                           // Pulsante modulare per massima coerenza
                           echo $renderer->renderModule('button', [
                               'text' => 'Scopri le Gare',
                               'variant' => 'primary',
                               'size' => 'large',
                               'icon' => 'play',
                               'iconPosition' => 'left',
                               'href' => '#gare'
                           ]);
                           ?>
                       </div>
            </div>
        </div>
    </div>
    
    <!-- Overlay per leggibilità -->
    <div class="hero-overlay"></div>
    
    <!-- Background image -->
    <div class="hero-bg" style="background-image: url('assets/images/hero-bg.jpg')"></div>
</section>

