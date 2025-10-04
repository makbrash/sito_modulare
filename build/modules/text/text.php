<?php
/**
 * Rich Text Module
 * Modulo per contenuti testuali ricchi
 */

$moduleData = $renderer->getModuleData('richText', $config);
$content = $moduleData['content'] ?? '';
$wrapper = $config['wrapper'] ?? 'article';
?>

<<?= $wrapper ?> class="rich-text-module">
    <div class="rich-text-content">
        <?= $content ?>
    </div>
</<?= $wrapper ?>>
