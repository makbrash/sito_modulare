<?php
/**
 * Action Hero Module
 * Modulo hero con layout flessibile e contenuti dinamici
 */

$moduleData = $renderer->getModuleData('actionHero', $config);

$height = $moduleData['height'] ?? '100vh';
$eyebrow = is_array($moduleData['eyebrow'] ?? null) ? $moduleData['eyebrow'] : [];
$title = $moduleData['title'] ?? '';
$subtitle = $moduleData['subtitle'] ?? '';
$description = $moduleData['description'] ?? '';
$actions = is_array($moduleData['actions'] ?? null) ? array_values(array_filter($moduleData['actions'])) : [];
$stats = is_array($moduleData['stats'] ?? null) ? array_values(array_filter($moduleData['stats'], function ($stat) {
    return is_array($stat) && (!empty($stat['value']) || !empty($stat['label']));
})) : [];
$background = is_array($moduleData['background'] ?? null) ? $moduleData['background'] : [];

$wrapperStyle = [];
if (!empty($height)) {
    $wrapperStyle[] = '--hero-height: ' . htmlspecialchars($height, ENT_QUOTES);
}
if (!empty($background['overlay'])) {
    $wrapperStyle[] = '--hero-overlay: ' . htmlspecialchars($background['overlay'], ENT_QUOTES);
}
if (isset($background['overlay_opacity'])) {
    $opacity = (float) $background['overlay_opacity'];
    $wrapperStyle[] = '--hero-overlay-opacity: ' . htmlspecialchars((string) $opacity, ENT_QUOTES);
}
$wrapperStyleAttr = $wrapperStyle ? ' style="' . implode('; ', $wrapperStyle) . '"' : '';

$bgStyleParts = [];
if (!empty($background['image'])) {
    $bgStyleParts[] = "background-image: url('" . htmlspecialchars($background['image'], ENT_QUOTES) . "')";
}
if (!empty($background['position'])) {
    $bgStyleParts[] = 'background-position: ' . htmlspecialchars($background['position'], ENT_QUOTES);
}
if (!empty($background['size'])) {
    $bgStyleParts[] = 'background-size: ' . htmlspecialchars($background['size'], ENT_QUOTES);
}
if (!empty($background['repeat'])) {
    $bgStyleParts[] = 'background-repeat: ' . htmlspecialchars($background['repeat'], ENT_QUOTES);
}
$bgStyleAttr = $bgStyleParts ? ' style="' . implode('; ', $bgStyleParts) . '"' : '';
?>

<section class="hero-module"<?= $wrapperStyleAttr ?>>
    <div class="hero-container site-container">
        <div class="hero-content">
            <div class="hero-text">
                <?php if (!empty($eyebrow['label'])): ?>
                    <div class="hero-eyebrow">
                        <?php if (!empty($eyebrow['icon'])): ?>
                            <?php $iconClass = strpos($eyebrow['icon'], 'fa-') === 0 ? $eyebrow['icon'] : 'fas ' . $eyebrow['icon']; ?>
                            <i class="<?= htmlspecialchars($iconClass, ENT_QUOTES) ?>" aria-hidden="true"></i>
                        <?php endif; ?>
                        <span><?= htmlspecialchars($eyebrow['label']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($title)): ?>
                    <h1 class="hero-title"><?= htmlspecialchars($title) ?></h1>
                <?php endif; ?>

                <?php if (!empty($subtitle)): ?>
                    <p class="hero-subtitle"><?= htmlspecialchars($subtitle) ?></p>
                <?php endif; ?>

                <?php if (!empty($description)): ?>
                    <p class="hero-description"><?= nl2br(htmlspecialchars($description)) ?></p>
                <?php endif; ?>

                <?php if (!empty($actions)): ?>
                    <div class="hero-actions">
                        <?php foreach ($actions as $action): ?>
                            <?php
                            if (!is_array($action)) {
                                continue;
                            }

                            if (!empty($action['module']) && is_array($action['module'])) {
                                $moduleName = $action['module']['name'] ?? null;
                                $moduleConfig = $action['module']['config'] ?? [];
                                if ($moduleName) {
                                    echo $renderer->renderModule($moduleName, $moduleConfig);
                                }
                                continue;
                            }

                            $buttonConfig = array_merge([
                                'text' => 'Azione',
                                'variant' => 'primary',
                                'size' => 'large'
                            ], $action);
                            echo $renderer->renderModule('button', $buttonConfig);
                            ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($stats)): ?>
                    <div class="hero-stats">
                        <?php foreach ($stats as $stat):
                            $statValue = $stat['value'] ?? null;
                            $statLabel = $stat['label'] ?? null;
                            if (!$statValue && !$statLabel) {
                                continue;
                            }
                            ?>
                            <div class="hero-stat">
                                <?php if (!empty($stat['icon'])): ?>
                                    <?php $statIcon = strpos($stat['icon'], 'fa-') === 0 ? $stat['icon'] : 'fas ' . $stat['icon']; ?>
                                    <i class="<?= htmlspecialchars($statIcon, ENT_QUOTES) ?>" aria-hidden="true"></i>
                                <?php endif; ?>
                                <?php if ($statValue): ?>
                                    <span class="hero-stat__value"><?= htmlspecialchars($statValue) ?></span>
                                <?php endif; ?>
                                <?php if ($statLabel): ?>
                                    <span class="hero-stat__label"><?= htmlspecialchars($statLabel) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="hero-overlay" aria-hidden="true"></div>
    <div class="hero-bg"<?= $bgStyleAttr ?> aria-hidden="true"></div>
</section>

