/**
 * Menu Module JavaScript - Bologna Marathon
 * Gestione avanzata del menu con search box AI e animazioni
 */

class MenuManager {
    constructor(menuId) {
        this.menuId = menuId;
        this.menu = document.getElementById(menuId);
        this.searchInput = document.getElementById(`${menuId}-search`);
        this.searchBtn = this.searchInput?.parentElement.querySelector('.search-btn');
        this.suggestions = document.getElementById(`${menuId}-suggestions`);
        this.toggle = document.getElementById(`${menuId}-toggle`);
        this.overlay = document.getElementById(`${menuId}-overlay`);
        this.close = document.getElementById(`${menuId}-close`);
        this.countdownElement = this.menu?.querySelector('.countdown-timer');
        
        this.init();
    }
    
    init() {
        if (!this.menu) return;
        
        this.initCountdown();
        this.initMobileMenu();
        this.initSearchBox();
        this.initStickyEffect();
        this.initKeyboardNavigation();
    }
    
    // Countdown Timer
    initCountdown() {
        if (!this.countdownElement) return;
        
        const countdownDate = new Date(this.menu.querySelector('.countdown').dataset.date).getTime();
        
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
            
            this.countdownElement.textContent = `${days}d ${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        };
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }
    
    // Mobile Menu
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
        document.body.classList.toggle('menu-open');
    }
    
    closeMobileMenu() {
        this.overlay?.classList.remove('active');
        this.toggle?.classList.remove('active');
        document.body.classList.remove('menu-open');
    }
    
    // Search Box
    initSearchBox() {
        if (!this.searchInput || !this.searchBtn) return;
        
        let searchTimeout;
        
        // Focus/Blur animations
        this.searchInput.addEventListener('focus', () => {
            this.searchInput.parentElement.classList.add('focused');
            this.suggestions?.classList.add('show');
            
            if (!this.searchInput.value) {
                this.searchInput.placeholder = 'Digita la tua domanda...';
            }
        });
        
        this.searchInput.addEventListener('blur', () => {
            this.searchInput.parentElement.classList.remove('focused');
            
            setTimeout(() => {
                this.suggestions?.classList.remove('show');
                if (!this.searchInput.value) {
                    this.searchInput.placeholder = 'Chiedi a Ingrid';
                }
            }, 200);
        });
        
        // Input typing animation
        this.searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            
            this.searchInput.parentElement.classList.add('typing');
            
            searchTimeout = setTimeout(() => {
                this.searchInput.parentElement.classList.remove('typing');
            }, 300);
            
            if (this.searchInput.value.length > 0) {
                this.suggestions?.classList.add('has-input');
            } else {
                this.suggestions?.classList.remove('has-input');
            }
        });
        
        // Send button animation
        this.searchBtn.addEventListener('click', () => {
            const query = this.searchInput.value.trim();
            if (!query) return;
            
            this.sendQuery(query);
        });
        
        // Enter key
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.searchBtn.click();
            }
        });
        
        // Suggestion clicks
        this.suggestions?.addEventListener('click', (e) => {
            if (e.target.classList.contains('suggestion-item')) {
                this.searchInput.value = e.target.textContent;
                this.searchInput.focus();
                this.searchBtn.click();
            }
        });
    }
    
    sendQuery(query) {
        // Animazione invio
        this.searchBtn.classList.add('sending');
        this.searchInput.classList.add('processing');
        
        // Simula invio (per ora solo feedback visivo)
        setTimeout(() => {
            this.searchBtn.classList.remove('sending');
            this.searchInput.classList.remove('processing');
            
            // Placeholder temporaneo
            const originalPlaceholder = this.searchInput.placeholder;
            this.searchInput.placeholder = 'Invio in corso...';
            this.searchInput.value = '';
            
            setTimeout(() => {
                this.searchInput.placeholder = originalPlaceholder;
            }, 2000);
        }, 1000);
    }
    
    // Sticky Effect
    initStickyEffect() {
        let lastScrollY = window.scrollY;
        
        window.addEventListener('scroll', () => {
            const currentScrollY = window.scrollY;
            
            if (currentScrollY > 100) {
                this.menu?.classList.add('scrolled');
            } else {
                this.menu?.classList.remove('scrolled');
            }
            
            lastScrollY = currentScrollY;
        });
    }
    
    // Keyboard Navigation
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