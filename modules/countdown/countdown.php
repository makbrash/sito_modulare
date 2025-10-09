<?php
/**
 * Countdown Module - Bologna Marathon
 * Modulo countdown elegante con due varianti: full-width e card sovrapposta
 * 
 * @var ModuleRenderer $renderer
 * @var array $config
 */

// Configurazione con valori di default
$variant = $config['variant'] ?? 'banner'; // 'banner' o 'card'
$targetDate = $config['target_date'] ?? '2026-03-01T09:00:00';
$title = $config['title'] ?? '';
$subtitle = $config['subtitle'] ?? 'Mancano solo';
$logo1 = $config['logo_1'] ?? 'assets/images/logo-bologna-marathon.svg';
$logo2 = $config['logo_2'] ?? 'assets/images/logo-bologna-marathon.svg';
$showLogos = $config['show_logos'] ?? true;
$themeClass = $config['theme_class'] ?? '';

// Genera ID unico per questo countdown
$countdownId = 'countdown-' . uniqid();
?>

<div class="countdown-module countdown-module--<?= htmlspecialchars($variant) ?> <?= htmlspecialchars($themeClass) ?>" id="<?= $countdownId ?>">
    <div class="countdown-container">
        
        <?php if ($showLogos && $variant === 'banner'): ?>
            <div class="countdown-logos">
                <?php if ($logo1): ?>
                    <img src="<?= htmlspecialchars($logo1) ?>" alt="Logo" class="countdown-logo countdown-logo--left">
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="countdown-content">
            <?php if ($title): ?>
                <h3 class="countdown-title"><?= htmlspecialchars($title) ?></h3>
            <?php endif; ?>
            
            <?php if ($subtitle): ?>
                <p class="countdown-subtitle"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
            
            <div class="countdown-timer" 
                 data-countdown="<?= htmlspecialchars($targetDate) ?>"
                 data-countdown-format="full">
                <!-- Il countdown viene popolato da JavaScript -->
                <div class="countdown-item">
                    <div class="countdown-number">--</div>
                    <div class="countdown-label">Gior.</div>
                </div>
                <div class="countdown-item">
                    <div class="countdown-number">--</div>
                    <div class="countdown-label">Ore</div>
                </div>
                <div class="countdown-item">
                    <div class="countdown-number">--</div>
                    <div class="countdown-label">Min.</div>
                </div>
                <div class="countdown-item">
                    <div class="countdown-number">--</div>
                    <div class="countdown-label">Sec.</div>
                </div>
            </div>
        </div>
        
        <?php if ($showLogos && $variant === 'banner'): ?>
            <div class="countdown-logos">
                <?php if ($logo2): ?>
                    <img src="<?= htmlspecialchars($logo2) ?>" alt="Logo" class="countdown-logo countdown-logo--right">
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    </div>
</div>

