<?php

/**
 * Menu Module - Bologna Marathon
 * Menu di navigazione moderno con search box AI e design elegante
 */
$config ='';
// Configurazione con valori di default
$logo = $config['logo'] ?? 'assets/images/logo-bologna-marathon.svg';
$logoAlt = $config['logo_alt'] ?? 'Bologna Marathon';
$marathonTitle = $config['marathon_title'] ?? '<strong>TERMAL</strong> BOLOGNA MARATHON';
$countdownDate = $config['countdown_date'] ?? '2026-03-01T09:00:00';
$searchPlaceholder = $config['search_placeholder'] ?? 'Chiedi a Ingrid';
$menuItems = $config['menu_items'] ?? [
    ['label' => 'Home', 'url' => '#home', 'target' => '_self'],
    ['label' => 'Maratona', 'url' => '#gara', 'target' => '_self', 'class' => 'theme-marathon gara'],
    ['label' => '30km dei Portici', 'url' => '#risultati', 'target' => '_self', 'class' => 'theme-portici gara'],
    ['label' => 'Run Tune Up', 'url' => '#risultati', 'target' => '_self', 'class' => 'theme-run-tune-up gara'],
    ['label' => '5km City Run', 'url' => '#risultati', 'target' => '_self', 'class' => 'theme-5k gara'],
    ['label' => 'Kids Run', 'url' => '#risultati', 'target' => '_self', 'class' => 'theme-kidsrun gara'],
    ['label' => 'News', 'url' => '#news', 'target' => '_self'],
    ['label' => 'Contatti', 'url' => '#contatti', 'target' => '_self'],
    ['label' => 'Ospitalità', 'url' => '#contatti', 'target' => '_self'],

];

// Genera ID unico per questo menu
$menuId = 'menu-' . uniqid();
?>

<nav class="main-menu sticky" id="<?= $menuId ?>">
    <div class="menu-container">
        <!-- Logo e Titolo -->
        <div class="menu-brand">
            <?php if ($logo): ?>
                <a href="/" class="brand-link">
                    <img src="<?= htmlspecialchars($logo) ?>"
                        alt="<?= htmlspecialchars($logoAlt) ?>"
                        class="brand-logo">
                    <div class="brand-text">
                        <div class="marathon-title"><?= ($marathonTitle) ?></div>
                        <div class="countdown" data-date="<?= htmlspecialchars($countdownDate) ?>">
                            <div class="countdown-date"><?= date('j M Y', strtotime($countdownDate)) ?></div>
                            <span class="countdown-timer">00:00:00</span>
                        </div>
                    </div>
                </a>
            <?php endif; ?>
        </div>

        <!-- Search Box AI -->
        <div class="search-container">
            <div class="search-box">
                <input type="text"
                    id="<?= $menuId ?>-search"
                    class="search-input"
                    placeholder="<?= htmlspecialchars($searchPlaceholder) ?>"
                    autocomplete="off">
                <button class="search-btn" type="button" aria-label="Invia domanda">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </div>
            <div class="search-suggestions" id="<?= $menuId ?>-suggestions">
                <div class="suggestion-item">Quando è la prossima maratona?</div>
                <div class="suggestion-item">Come iscriversi alla gara?</div>
                <div class="suggestion-item">Dove vedere i risultati?</div>
            </div>
        </div>

        <!-- Menu Hamburger -->
        <div class="menu-toggle-container">
            <div class="menu-toggle" id="<?= $menuId ?>-toggle" aria-label="Apri menu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </div>
        </div>
    </div>

    <!-- Menu Mobile Fullscreen -->
    <div class="mobile-menu-overlay" id="<?= $menuId ?>-overlay">
        <div class="mobile-menu-content">
            <div class="mobile-menu-header">
                <div class="mobile-brand">
                    <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($logoAlt) ?>" class="mobile-logo">
                    <h2 class="mobile-title"><?= htmlspecialchars($marathonTitle) ?></h2>
                </div>
            </div>

            <nav class="mobile-nav">
                <ul class="mobile-menu-list">
                    <?php foreach ($menuItems as $item): ?>
                        <li class="mobile-menu-item">
                            <a href="<?= htmlspecialchars($item['url']) ?> "
                                class="mobile-menu-link <?= !empty($item['class']) ? ' ' . htmlspecialchars($item['class']) : '' ?>"
                                target="<?= htmlspecialchars($item['target']) ?>">
                                <span class="menu-link-text"><?= htmlspecialchars($item['label']) ?></span>
                                <svg class="menu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="mobile-menu-footer">
                <div class="mobile-search">
                    <input type="text"
                        class="mobile-search-input"
                        placeholder="<?= htmlspecialchars($searchPlaceholder) ?>">
                    <button class="mobile-search-btn" type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- JavaScript esterno gestito da menu.js -->