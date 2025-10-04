<?php
/**
 * Menu Module - Bologna Marathon
 * Menu di navigazione moderno con search box AI e design elegante
 */

// Configurazione con valori di default
$logo = $config['logo'] ?? 'assets/images/logo-bologna-marathon.svg';
$logoAlt = $config['logo_alt'] ?? 'Bologna Marathon';
$marathonTitle = $config['marathon_title'] ?? 'TERMAL BOLOGNA MARATHON';
$countdownDate = $config['countdown_date'] ?? '2024-04-14T09:00:00';
$searchPlaceholder = $config['search_placeholder'] ?? 'Chiedi a Ingrid';
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
                    <h1 class="marathon-title"><?= htmlspecialchars($marathonTitle) ?></h1>
                    <div class="countdown" data-date="<?= htmlspecialchars($countdownDate) ?>">
                        <span class="countdown-text">Prossima edizione:</span>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
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
        <div class="menu-toggle" id="<?= $menuId ?>-toggle" aria-label="Apri menu">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
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
                <button class="mobile-close" id="<?= $menuId ?>-close" aria-label="Chiudi menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <nav class="mobile-nav">
                <ul class="mobile-menu-list">
                    <?php foreach ($menuItems as $item): ?>
                        <li class="mobile-menu-item">
                            <a href="<?= htmlspecialchars($item['url']) ?>" 
                               class="mobile-menu-link"
                               target="<?= htmlspecialchars($item['target']) ?>">
                                <span class="menu-link-text"><?= htmlspecialchars($item['label']) ?></span>
                                <svg class="menu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
// Inizializza menu solo se non è già stato fatto
if (!document.querySelector('#<?= $menuId ?>').hasAttribute('data-initialized')) {
    document.querySelector('#<?= $menuId ?>').setAttribute('data-initialized', 'true');
    
    // Elementi del menu
    const menu = document.getElementById('<?= $menuId ?>');
    const toggle = document.getElementById('<?= $menuId ?>-toggle');
    const overlay = document.getElementById('<?= $menuId ?>-overlay');
    const close = document.getElementById('<?= $menuId ?>-close');
    const searchInput = document.getElementById('<?= $menuId ?>-search');
    const searchBtn = searchInput?.parentElement.querySelector('.search-btn');
    const suggestions = document.getElementById('<?= $menuId ?>-suggestions');
    const countdownElement = menu?.querySelector('.countdown-timer');
    
    // Countdown Timer
    function updateCountdown() {
        if (!countdownElement) return;
        
        const countdownDate = new Date('<?= $countdownDate ?>').getTime();
        const now = new Date().getTime();
        const distance = countdownDate - now;
        
        if (distance < 0) {
            countdownElement.textContent = 'Evento concluso';
            return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        countdownElement.textContent = `${days}d ${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
    
    // Aggiorna countdown ogni secondo
    if (countdownElement) {
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }
    
    // Toggle mobile menu
    function toggleMobileMenu() {
        overlay?.classList.toggle('active');
        toggle?.classList.toggle('active');
        document.body.classList.toggle('menu-open');
    }
    
    function closeMobileMenu() {
        overlay?.classList.remove('active');
        toggle?.classList.remove('active');
        document.body.classList.remove('menu-open');
    }
    
    // Event listeners per mobile menu
    toggle?.addEventListener('click', toggleMobileMenu);
    close?.addEventListener('click', closeMobileMenu);
    overlay?.addEventListener('click', function(e) {
        if (e.target === overlay) closeMobileMenu();
    });
    
    // Search Box Animations
    if (searchInput && searchBtn) {
        let searchTimeout;
        
        // Focus/Blur animations
        searchInput.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
            suggestions?.classList.add('show');
            
            // Mostra placeholder animato
            if (!this.value) {
                this.placeholder = 'Digita la tua domanda...';
            }
        });
        
        searchInput.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
            
            // Delay per permettere click sui suggerimenti
            setTimeout(() => {
                suggestions?.classList.remove('show');
                if (!this.value) {
                    this.placeholder = '<?= htmlspecialchars($searchPlaceholder) ?>';
                }
            }, 200);
        });
        
        // Input typing animation
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            // Simula "typing" effect
            this.parentElement.classList.add('typing');
            
            searchTimeout = setTimeout(() => {
                this.parentElement.classList.remove('typing');
            }, 300);
            
            // Mostra/nascondi suggerimenti
            if (this.value.length > 0) {
                suggestions?.classList.add('has-input');
            } else {
                suggestions?.classList.remove('has-input');
            }
        });
        
        // Send button animation
        searchBtn.addEventListener('click', function() {
            const query = searchInput.value.trim();
            if (!query) return;
            
            // Animazione invio
            this.classList.add('sending');
            searchInput.classList.add('processing');
            
            // Simula invio (per ora solo feedback visivo)
            setTimeout(() => {
                this.classList.remove('sending');
                searchInput.classList.remove('processing');
                
                // Placeholder temporaneo
                const originalPlaceholder = searchInput.placeholder;
                searchInput.placeholder = 'Invio in corso...';
                searchInput.value = '';
                
                setTimeout(() => {
                    searchInput.placeholder = originalPlaceholder;
                }, 2000);
            }, 1000);
        });
        
        // Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchBtn.click();
            }
        });
    }
    
    // Suggestion clicks
    suggestions?.addEventListener('click', function(e) {
        if (e.target.classList.contains('suggestion-item')) {
            searchInput.value = e.target.textContent;
            searchInput.focus();
            searchBtn.click();
        }
    });
    
    // Chiudi menu con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileMenu();
        }
    });
    
    // Sticky menu effect
    let lastScrollY = window.scrollY;
    window.addEventListener('scroll', function() {
        const currentScrollY = window.scrollY;
        
        if (currentScrollY > 100) {
            menu?.classList.add('scrolled');
        } else {
            menu?.classList.remove('scrolled');
        }
        
        lastScrollY = currentScrollY;
    });
}
</script>