/**
 * Menu Module JavaScript
 * Logica per il modulo menu
 */

class MenuModule {
    constructor() {
        this.menuToggle = document.getElementById('menu-toggle');
        this.menuNav = document.getElementById('menu-nav');
        this.init();
    }
    
    init() {
        if (this.menuToggle && this.menuNav) {
            this.setupToggle();
            this.setupLinks();
            this.setupOutsideClick();
            this.setupScroll();
        }
    }
    
    setupToggle() {
        this.menuToggle.addEventListener('click', () => {
            this.menuNav.classList.toggle('active');
            this.menuToggle.classList.toggle('active');
        });
    }
    
    setupLinks() {
        const menuLinks = this.menuNav.querySelectorAll('.menu-link');
        menuLinks.forEach(link => {
            link.addEventListener('click', () => {
                this.menuNav.classList.remove('active');
                this.menuToggle.classList.remove('active');
            });
        });
    }
    
    setupOutsideClick() {
        document.addEventListener('click', (e) => {
            if (!this.menuToggle.contains(e.target) && !this.menuNav.contains(e.target)) {
                this.menuNav.classList.remove('active');
                this.menuToggle.classList.remove('active');
            }
        });
    }
    
    setupScroll() {
        let lastScrollY = window.scrollY;
        
        window.addEventListener('scroll', () => {
            const currentScrollY = window.scrollY;
            const menu = document.querySelector('.main-menu');
            
            if (currentScrollY > lastScrollY && currentScrollY > 100) {
                // Scrolling down
                menu.style.transform = 'translateY(-100%)';
            } else {
                // Scrolling up
                menu.style.transform = 'translateY(0)';
            }
            
            lastScrollY = currentScrollY;
        });
    }
}

// Inizializza quando il DOM è pronto
document.addEventListener('DOMContentLoaded', () => {
    new MenuModule();
});
