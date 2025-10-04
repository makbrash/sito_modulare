<?php
/**
 * Select Module (Select2-enhanced)
 */

$id = $config['id'] ?? 'select-' . uniqid();
$name = $config['name'] ?? 'select';
$placeholder = $config['placeholder'] ?? 'Seleziona...';
$options = $config['options'] ?? [];
$value = $config['value'] ?? null;
$multiple = !empty($config['multiple']);
?>

<div class="bm-select" data-module="select">
    <select
        id="<?= htmlspecialchars($id) ?>"
        name="<?= htmlspecialchars($name) ?><?= $multiple ? '[]' : '' ?>"
        class="bm-select__input"
        <?= $multiple ? 'multiple' : '' ?>
        data-enhance="select2"
        data-placeholder="<?= htmlspecialchars($placeholder) ?>">
        <?php if (!$multiple): ?>
            <option></option>
        <?php endif; ?>
        <?php foreach ($options as $opt): ?>
            <?php
                $optValue = is_array($opt) ? ($opt['value'] ?? '') : $opt;
                $optLabel = is_array($opt) ? ($opt['label'] ?? $optValue) : $opt;
                $selected = $multiple
                    ? (is_array($value) && in_array($optValue, $value))
                    : ($value !== null && (string)$value === (string)$optValue);
            ?>
            <option value="<?= htmlspecialchars($optValue) ?>" <?= $selected ? 'selected' : '' ?>>
                <?= htmlspecialchars($optLabel) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>


