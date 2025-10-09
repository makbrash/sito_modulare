<?php
/**
 * Modulo: Partner
 * Descrizione: Galleria partner con tre gruppi sponsor
 * 
 * @var ModuleRenderer $renderer
 * @var array $config
 */

$moduleData = $renderer->getModuleData('partner', $config);
$title = $config['title'] ?? $moduleData['title'] ?? 'I Nostri Partner';
$hideTexts = $config['hide_texts'] ?? false;
$showGroup1 = $config['show_group1'] ?? true;
$showGroup2 = $config['show_group2'] ?? true;
$showGroup3 = $config['show_group3'] ?? true;
$showGroup4 = $config['show_group4'] ?? true;

// Carica sponsor dal database
$sponsors = $renderer->getSponsors();
$group1Sponsors = array_filter($sponsors, function($s) { 
    return in_array($s['group_type'], ['main', 'official']); 
});
$group2Sponsors = array_filter($sponsors, function($s) { 
    return $s['group_type'] === 'sponsor'; 
});
$group3Sponsors = array_filter($sponsors, function($s) { 
    return $s['group_type'] === 'technical'; 
});
$group4Sponsors = array_filter($sponsors, function($s) { 
    return $s['group_type'] === 'credits'; 
});
?>

<div class="partner">
    <?php if (!$hideTexts && !empty($title)): ?>
        <h2 class="partner__title"><?= $title ?></h2>
    <?php endif; ?>
    
    <?php if (!$hideTexts): ?>
        <p class="partner__subtitle">Grazie ai nostri partner che rendono possibile questo straordinario evento sportivo</p>
    <?php endif; ?>
    
    <?php if ($showGroup1 && !empty($group1Sponsors)): ?>
    <!-- Gruppo 1: Main Sponsor, Official Car, Official Water -->
    <div class="partner__group">
        <?php if (!$hideTexts): ?>
            <h5 class="partner__group-title">SPONSOR PRINCIPALI</h5>
        <?php endif; ?>
        <div class="partner__swiper partner__swiper--group1" data-group="1">
            <div class="swiper-wrapper">
                <?php foreach ($group1Sponsors as $sponsor): ?>
                <div class="swiper-slide">
                    <div class="partner__card">
                        <img src="<?= htmlspecialchars($sponsor['image_path']) ?>" 
                             alt="<?= htmlspecialchars($sponsor['name']) ?>" 
                             class="partner__logo">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="partner__navigation">
                <button class="partner__btn partner__btn--prev">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="partner__btn partner__btn--next">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($showGroup2 && !empty($group2Sponsors)): ?>
    <!-- Gruppo 2: Sponsor -->
    <div class="partner__group">
        <?php if (!$hideTexts): ?>
            <h5 class="partner__group-title">SPONSOR</h5>
        <?php endif; ?>
        <div class="partner__swiper partner__swiper--group2" data-group="2">
            <div class="swiper-wrapper">
                <?php foreach ($group2Sponsors as $sponsor): ?>
                <div class="swiper-slide">
                    <div class="partner__card">
                        <img src="<?= htmlspecialchars($sponsor['image_path']) ?>" 
                             alt="<?= htmlspecialchars($sponsor['name']) ?>" 
                             class="partner__logo">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="partner__navigation">
                <button class="partner__btn partner__btn--prev">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="partner__btn partner__btn--next">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($showGroup3 && !empty($group3Sponsors)): ?>
    <!-- Gruppo 3: Sponsor Tecnici -->
    <div class="partner__group">
        <?php if (!$hideTexts): ?>
            <h5 class="partner__group-title">SPONSOR TECNICI</h5>
        <?php endif; ?>
        <div class="partner__swiper partner__swiper--group3" data-group="3">
            <div class="swiper-wrapper">
                <?php foreach ($group3Sponsors as $sponsor): ?>
                <div class="swiper-slide">
                    <div class="partner__card">
                        <img src="<?= htmlspecialchars($sponsor['image_path']) ?>" 
                             alt="<?= htmlspecialchars($sponsor['name']) ?>" 
                             class="partner__logo">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="partner__navigation">
                <button class="partner__btn partner__btn--prev">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="partner__btn partner__btn--next">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($showGroup4 && !empty($group4Sponsors)): ?>
    <!-- Gruppo 4: Credits -->
    <div class="partner__group partner__group--credits">
        <?php if (!$hideTexts): ?>
            <h5 class="partner__group-title">CREDITS</h5>
        <?php endif; ?>
        <div class="partner__swiper partner__swiper--group4" data-group="4">
            <div class="swiper-wrapper">
                <?php foreach ($group4Sponsors as $sponsor): ?>
                <div class="swiper-slide">
                    <div class="partner__card partner__card--credits">
                        <img src="<?= htmlspecialchars($sponsor['image_path']) ?>" 
                             alt="<?= htmlspecialchars($sponsor['name']) ?>" 
                             class="partner__logo partner__logo--credits">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="partner__navigation">
                <button class="partner__btn partner__btn--prev">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="partner__btn partner__btn--next">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>



