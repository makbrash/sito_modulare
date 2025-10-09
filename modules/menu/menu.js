/**
 * Menu Module JavaScript - Bologna Marathon
 * Gestione semplificata del menu mobile
 */

class MenuManager {
    constructor(menuId) {
        this.menuId = menuId;
        this.menu = document.getElementById(menuId);
        this.toggle = document.getElementById(`${menuId}-toggle`);
        this.overlay = document.getElementById(`${menuId}-overlay`);
        this.close = document.getElementById(`${menuId}-close`);
        this.countdownElement = this.menu?.querySelector('.countdown-timer');
        this.searchInput = document.getElementById(`${menuId}-search`);
        this.searchBtn = this.menu?.querySelector('.search-btn');
        this.searchBox = this.menu?.querySelector('.search-box');
        this.suggestions = document.getElementById(`${menuId}-suggestions`);
        
        this.init();
    }
    
    init() {
        if (!this.menu) return;
        
        this.initCountdown();
        this.initSearch();
        this.initMobileMenu();
        this.initStickyScroll();
        this.initKeyboardNavigation();
    }
    
    // Countdown Timer
    initCountdown() {
        if (!this.countdownElement) return;
        
        // Setta attributi per countdown centralizzato
        const countdownWrapper = this.menu.querySelector('.countdown');
        if (countdownWrapper) {
            const targetDate = countdownWrapper.dataset.date;
            this.countdownElement.setAttribute('data-countdown', targetDate);
            this.countdownElement.setAttribute('data-countdown-format', 'compact');
            
            // Se BolognaMarathonApp è già caricata, inizializza subito
            if (window.bolognaMarathon && window.bolognaMarathon.initCountdown) {
                window.bolognaMarathon.initCountdown(this.countdownElement, targetDate);
            } else {
                // Altrimenti usa fallback locale
                this.initCountdownFallback(targetDate);
            }
        }
    }
    
    // Fallback countdown locale (per compatibilità)
    initCountdownFallback(targetDate) {
        const countdownDate = new Date(targetDate).getTime();
        
        const updateCountdown = () => {
            const now = new Date().getTime();
            const distance = countdownDate - now;
            
            if (distance < 0) {
                this.countdownElement.textContent = 'Evento concluso';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            // Formato compatto: 146g 12h 54m 05s
            this.countdownElement.textContent = `${days}g ${hours}h ${minutes.toString().padStart(2, '0')}m ${seconds.toString().padStart(2, '0')}s`;
        };
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }
    
    // Search Box Functionality
    initSearch() {
        if (!this.searchInput || !this.searchBtn || !this.searchBox) return;
        
        // Focus/Blur handlers
        this.searchInput.addEventListener('focus', () => {
            this.searchBox.classList.add('focused');
            this.showSuggestions();
        });
        
        this.searchInput.addEventListener('blur', (e) => {
            // Delay per permettere click su suggestions
            setTimeout(() => {
                this.searchBox.classList.remove('focused');
                if (!this.searchInput.value) {
                    this.searchBox.classList.remove('typing');
                }
                this.hideSuggestions();
            }, 200);
        });
        
        // Typing handler
        this.searchInput.addEventListener('input', () => {
            if (this.searchInput.value.length > 0) {
                this.searchBox.classList.add('typing');
                this.searchBox.classList.add('has-input');
                if (this.suggestions) {
                    this.suggestions.classList.add('has-input');
                }
            } else {
                this.searchBox.classList.remove('typing');
                this.searchBox.classList.remove('has-input');
                if (this.suggestions) {
                    this.suggestions.classList.remove('has-input');
                }
                this.showSuggestions();
            }
        });
        
        // Enter key handler
        this.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && this.searchInput.value.trim()) {
                this.sendSearch();
            }
        });
        
        // Button click handler
        this.searchBtn.addEventListener('click', () => {
            if (this.searchInput.value.trim()) {
                this.sendSearch();
            }
        });
        
        // Suggestion click handlers
        if (this.suggestions) {
            const suggestionItems = this.suggestions.querySelectorAll('.suggestion-item');
            suggestionItems.forEach(item => {
                item.addEventListener('click', () => {
                    this.searchInput.value = item.textContent;
                    this.sendSearch();
                });
            });
        }
        
        // Click fuori per chiudere suggestions
        document.addEventListener('click', (e) => {
            if (!this.searchBox.contains(e.target) && !this.suggestions?.contains(e.target)) {
                this.hideSuggestions();
            }
        });
    }
    
    showSuggestions() {
        if (this.suggestions && !this.searchInput.value) {
            this.suggestions.classList.add('show');
        }
    }
    
    hideSuggestions() {
        if (this.suggestions) {
            this.suggestions.classList.remove('show');
        }
    }
    
    async sendSearch() {
        const query = this.searchInput.value.trim();
        if (!query) return;
        
        // Animazione invio
        this.searchBtn.classList.add('sending');
        this.searchInput.classList.add('processing');
        this.searchInput.disabled = true;
        
        // Simula caricamento (sostituire con chiamata API reale)
        await this.simulateAPICall(query);
        
        // Reset stato
        this.searchBtn.classList.remove('sending');
        this.searchInput.classList.remove('processing');
        this.searchInput.disabled = false;
        this.searchInput.value = '';
        this.searchBox.classList.remove('typing');
        this.searchBox.classList.remove('has-input');
        
        // Mostra notifica (placeholder)
        console.log('Ricerca inviata:', query);
    }
    
    async simulateAPICall(query) {
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve({ success: true, query });
            }, 1500);
        });
    }
    
    // Sticky Scroll Effect
    initStickyScroll() {
        let lastScroll = 0;
        const scrollThreshold = 50; // Pixel di scroll prima di attivare l'effetto
        
        const handleScroll = () => {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            
            if (currentScroll > scrollThreshold) {
                this.menu.classList.add('scrolled');
            } else {
                this.menu.classList.remove('scrolled');
            }
            
            lastScroll = currentScroll;
        };
        
        window.addEventListener('scroll', handleScroll, { passive: true });
        
        // Check iniziale
        handleScroll();
    }
    
    // Mobile Menu - Semplificato
    initMobileMenu() {
        if (!this.toggle || !this.overlay) return;
        
        this.toggle.addEventListener('click', () => this.toggleMobileMenu());
        this.close?.addEventListener('click', () => this.closeMobileMenu());
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay) this.closeMobileMenu();
        });
    }
    
    toggleMobileMenu() {
        this.overlay?.classList.toggle('active');
        this.toggle?.classList.toggle('active');
    }
    
    closeMobileMenu() {
        this.overlay?.classList.remove('active');
        this.toggle?.classList.remove('active');
    }
    
    // Cleanup semplificato
    destroy() {
        // Rimozione event listeners se necessario
    }
    
    // Keyboard Navigation - Semplificato
    initKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeMobileMenu();
            }
        });
    }
}

// Auto-inizializzazione per tutti i menu presenti
document.addEventListener('DOMContentLoaded', () => {
    const menus = document.querySelectorAll('.main-menu');
    menus.forEach(menu => {
        const menuId = menu.id;
        if (menuId && !menu.hasAttribute('data-initialized')) {
            new MenuManager(menuId);
            menu.setAttribute('data-initialized', 'true');
        }
    });
});

// Export per uso esterno
window.MenuManager = MenuManager;