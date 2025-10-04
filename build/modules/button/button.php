<?php
/**
 * Button Module
 * Sistema modulare per pulsanti riutilizzabili
 */

$moduleData = $renderer->getModuleData('button', $config);

// Configurazione con valori di default
$text = $config['text'] ?? $moduleData['text'] ?? 'Click me';
$variant = $config['variant'] ?? $moduleData['variant'] ?? 'primary';
$size = $config['size'] ?? $moduleData['size'] ?? 'medium';
$href = $config['href'] ?? $moduleData['href'] ?? null;
$target = $config['target'] ?? $moduleData['target'] ?? '_self';
$icon = $config['icon'] ?? $moduleData['icon'] ?? null;
$iconPosition = $config['iconPosition'] ?? $moduleData['iconPosition'] ?? 'left';
$disabled = $config['disabled'] ?? $moduleData['disabled'] ?? false;
$loading = $config['loading'] ?? $moduleData['loading'] ?? false;
$fullWidth = $config['fullWidth'] ?? $moduleData['fullWidth'] ?? false;
$customClass = $config['customClass'] ?? $moduleData['customClass'] ?? '';

// Determina se è link o button
$isLink = !empty($href);
$tag = $isLink ? 'a' : 'button';

// Classi CSS
$classes = ['btn', "btn-{$variant}", "btn-{$size}"];
if ($fullWidth) $classes[] = 'btn-full-width';
if ($disabled) $classes[] = 'btn-disabled';
if ($loading) $classes[] = 'btn-loading';
if ($customClass) $classes[] = $customClass;

$classString = implode(' ', $classes);

// Attributi
$attributes = [];
if ($isLink) {
    $attributes[] = "href=\"" . htmlspecialchars($href) . "\"";
    $attributes[] = "target=\"" . htmlspecialchars($target) . "\"";
} else {
    if ($disabled) $attributes[] = 'disabled';
}

$attributesString = implode(' ', $attributes);

// Icona
$iconHtml = '';
if ($icon) {
    $iconClass = strpos($icon, 'fa-') === 0 ? $icon : "fas fa-{$icon}";
    $iconHtml = "<i class=\"{$iconClass}\"></i>";
}

// Contenuto del pulsante
$content = '';
if ($icon && $iconPosition === 'left') {
    $content .= $iconHtml . ' ';
}
$content .= htmlspecialchars($text);
if ($icon && $iconPosition === 'right') {
    $content .= ' ' . $iconHtml;
}

// Loading spinner
if ($loading) {
    $content = '<span class="btn-spinner"></span><span class="btn-text">' . htmlspecialchars($text) . '</span>';
}
?>

<<?= $tag ?> class="<?= $classString ?>" <?= $attributesString ?> <?= $isLink ? '' : 'type="button"' ?>>
    <?= $content ?>
</<?= $tag ?>>
