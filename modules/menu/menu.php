<?php
/**
 * Menu Module
 * Menu di navigazione modulare con configurazione dinamica
 */

// Configurazione con valori di default
$logo = $config['logo'] ?? 'assets/images/logo-bologna-marathon.svg';
$logoAlt = $config['logo_alt'] ?? 'Bologna Marathon';
$menuStyle = $config['style'] ?? 'horizontal';
$sticky = $config['sticky'] ?? true;
$stickyOffset = $config['sticky_offset'] ?? '0px';
$background = $config['background'] ?? 'transparent';
$menuItems = $config['menu_items'] ?? [
    ['label' => 'Home', 'url' => '#home', 'target' => '_self'],
    ['label' => 'La Gara', 'url' => '#gara', 'target' => '_self'],
    ['label' => 'Risultati', 'url' => '#risultati', 'target' => '_self'],
    ['label' => 'News', 'url' => '#news', 'target' => '_self'],
    ['label' => 'Contatti', 'url' => '#contatti', 'target' => '_self']
];

// Genera ID unico per questo menu
$menuId = 'menu-' . uniqid();
?>

<nav class="main-menu <?= $sticky ? 'sticky' : '' ?>" 
     id="<?= $menuId ?>"
     data-menu-style="<?= htmlspecialchars($menuStyle) ?>"
     style="--menu-style: <?= htmlspecialchars($menuStyle) ?>; --sticky-offset: <?= htmlspecialchars($stickyOffset) ?>; --menu-bg: <?= htmlspecialchars($background) ?>;">
    
    <div class="menu-container">
        <?php if ($logo): ?>
        <div class="menu-brand">
            <a href="/" class="brand-link">
                <img src="<?= htmlspecialchars($logo) ?>" 
                     alt="<?= htmlspecialchars($logoAlt) ?>" 
                     class="brand-logo">
            </a>
        </div>
        <?php endif; ?>
        
        <div class="menu-toggle" id="<?= $menuId ?>-toggle">
            <span></span>
            <span></span>
            <span></span>
        </div>
        
        <ul class="menu-nav" id="<?= $menuId ?>-nav">
            <?php foreach ($menuItems as $item): ?>
                <li>
                    <a href="<?= htmlspecialchars($item['url']) ?>" 
                       class="menu-link"
                       target="<?= htmlspecialchars($item['target']) ?>">
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>

<script>
// Inizializza menu solo se non è già stato fatto
if (!document.querySelector('#<?= $menuId ?>').hasAttribute('data-initialized')) {
    document.querySelector('#<?= $menuId ?>').setAttribute('data-initialized', 'true');
    
    // Toggle mobile menu
    const toggle = document.getElementById('<?= $menuId ?>-toggle');
    const nav = document.getElementById('<?= $menuId ?>-nav');
    const menu = document.getElementById('<?= $menuId ?>');
    
    if (toggle && nav) {
        toggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            toggle.classList.toggle('active');
            menu.classList.toggle('mobile-open');
        });
        
        // Chiudi menu quando si clicca fuori
        document.addEventListener('click', function(e) {
            if (!menu.contains(e.target)) {
                nav.classList.remove('active');
                toggle.classList.remove('active');
                menu.classList.remove('mobile-open');
            }
        });
    }
}
</script>