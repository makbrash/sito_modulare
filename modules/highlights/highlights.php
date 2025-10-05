<?php
/**
 * Highlights Module
 * Griglia di punti di forza configurabile via manifest
 */

$moduleData = $renderer->getModuleData('highlights', $config);

$eyebrow = $moduleData['eyebrow'] ?? '';
$title = $moduleData['title'] ?? '';
$subtitle = $moduleData['subtitle'] ?? '';
$items = is_array($moduleData['items'] ?? null) ? $moduleData['items'] : [];
$cta = is_array($moduleData['cta'] ?? null) ? $moduleData['cta'] : null;
$theme = $moduleData['theme'] ?? 'dark';
?>

<section class="highlights-module highlights-module--<?= htmlspecialchars($theme) ?>">
    <div class="site-container">
        <header class="section-header">
            <?php if (!empty($eyebrow)): ?>
                <span class="section-eyebrow"><?= htmlspecialchars($eyebrow) ?></span>
            <?php endif; ?>

            <?php if (!empty($title)): ?>
                <h2 class="section-title"><?= htmlspecialchars($title) ?></h2>
            <?php endif; ?>

            <?php if (!empty($subtitle)): ?>
                <p class="section-subtitle"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </header>

        <?php if (!empty($items)): ?>
            <div class="highlights-grid">
                <?php foreach ($items as $item):
                    if (!is_array($item)) { continue; }
                    $itemTitle = $item['title'] ?? '';
                    $itemDescription = $item['description'] ?? '';
                    $itemIcon = $item['icon'] ?? '';
                    $itemMeta = $item['meta'] ?? '';
                    if (!$itemTitle && !$itemDescription && !$itemMeta) { continue; }
                ?>
                    <article class="highlight-card">
                        <?php if (!empty($itemIcon)): ?>
                            <?php $iconClass = strpos($itemIcon, 'fa-') === 0 ? $itemIcon : 'fas ' . $itemIcon; ?>
                            <span class="highlight-card__icon" aria-hidden="true">
                                <i class="<?= htmlspecialchars($iconClass, ENT_QUOTES) ?>"></i>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($itemMeta)): ?>
                            <span class="highlight-card__meta"><?= htmlspecialchars($itemMeta) ?></span>
                        <?php endif; ?>

                        <?php if (!empty($itemTitle)): ?>
                            <h3 class="highlight-card__title"><?= htmlspecialchars($itemTitle) ?></h3>
                        <?php endif; ?>

                        <?php if (!empty($itemDescription)): ?>
                            <p class="highlight-card__description"><?= htmlspecialchars($itemDescription) ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($cta)): ?>
            <div class="highlights-cta">
                <?php
                if (!empty($cta['module']) && is_array($cta['module'])) {
                    $moduleName = $cta['module']['name'] ?? null;
                    $moduleConfig = $cta['module']['config'] ?? [];
                    if ($moduleName) {
                        echo $renderer->renderModule($moduleName, $moduleConfig);
                    }
                } else {
                    $buttonConfig = array_merge([
                        'text' => 'Scopri di più',
                        'variant' => 'ghost',
                        'size' => 'large'
                    ], $cta);
                    echo $renderer->renderModule('button', $buttonConfig);
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
</section>
