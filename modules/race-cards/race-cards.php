<?php
/**
 * Race Cards Module (dinamico)
 * Legge le gare dal DB tramite ModuleRenderer->getRaceCardsData()
 */

$moduleData = $renderer->getModuleData('raceCards', $config);
$layout = $moduleData['layout'] ?? ($config['layout'] ?? 'vertical');
$cards = $moduleData['cards'] ?? [];
?>

<section class="race-cards-module">
    <div class="race-cards-container">
        <?php foreach ($cards as $idx => $race): 
            $number = str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
            $slug = $race['slug'] ?? 'generic';
            $title = $race['title'] ?? '';
            $distance = $race['distance'] ?? '';
            $tag = $race['tag'] ?? '';
            $description = $race['description'] ?? '';
            $details = $race['details'] ?? [];
            $button = $race['button_text'] ?? ('ISCRIVITI A ' . $title);
            
            // Mappa slug a classi CSS specifiche
            $cardClass = '';
            $tagClass = '';
            $distanceClass = '';
            $buttonClass = '';
            
            switch($slug) {
                case 'marathon':
                    $cardClass = 'marathon-card';
                    $tagClass = 'marathon-tag';
                    $distanceClass = 'marathon-distance';
                    $buttonClass = 'marathon-button';
                    break;
                case 'portici':
                    $cardClass = 'portici-card';
                    $tagClass = 'portici-tag';
                    $distanceClass = 'portici-distance';
                    $buttonClass = 'portici-button';
                    break;
                case 'runtune':
                    $cardClass = 'runtune-card';
                    $tagClass = 'runtune-tag';
                    $distanceClass = 'runtune-distance';
                    $buttonClass = 'runtune-button';
                    break;
                default:
                    $cardClass = 'generic-card';
                    $tagClass = 'generic-tag';
                    $distanceClass = 'generic-distance';
                    $buttonClass = 'generic-button';
            }
        ?>
            <div class="race-card <?= htmlspecialchars($cardClass) ?>">
                <div class="card-number"><?= htmlspecialchars($number) ?></div>
                <div class="card-tag <?= htmlspecialchars($tagClass) ?>"><?= htmlspecialchars($tag) ?></div>
                <div class="card-content">
                    <h3 class="card-title"><?= htmlspecialchars($title) ?></h3>
                    <?php if (!empty($distance)): ?>
                        <div class="card-distance <?= htmlspecialchars($distanceClass) ?>"><?= htmlspecialchars($distance) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($description)): ?>
                        <p class="card-description"><?= htmlspecialchars($description) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($details)): ?>
                        <div class="card-details">
                            <?php foreach ($details as $detail): ?>
                                <div class="detail-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span><?= htmlspecialchars($detail) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <button class="card-button <?= htmlspecialchars($buttonClass) ?>"><?= htmlspecialchars($button) ?></button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
