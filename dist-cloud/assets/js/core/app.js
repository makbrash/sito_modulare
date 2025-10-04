/**
 * Core JavaScript - Bologna Marathon
 * Sistema modulare JavaScript
 */

class BolognaMarathonApp {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupMenu();
        this.setupModules();
        this.setupScrollEffects();
    }
    
    /**
     * Setup menu mobile
     */
    setupMenu() {
        const menuToggle = document.getElementById('menu-toggle');
        const menuNav = document.getElementById('menu-nav');
        
        if (menuToggle && menuNav) {
            menuToggle.addEventListener('click', () => {
                menuNav.classList.toggle('active');
                menuToggle.classList.toggle('active');
            });
            
            // Chiudi menu al click su link
            const menuLinks = menuNav.querySelectorAll('.menu-link');
            menuLinks.forEach(link => {
                link.addEventListener('click', () => {
                    menuNav.classList.remove('active');
                    menuToggle.classList.remove('active');
                });
            });
            
            // Chiudi menu al click fuori
            document.addEventListener('click', (e) => {
                if (!menuToggle.contains(e.target) && !menuNav.contains(e.target)) {
                    menuNav.classList.remove('active');
                    menuToggle.classList.remove('active');
                }
            });
        }
    }
    
    /**
     * Setup moduli dinamici
     */
    setupModules() {
        // Inizializza tutti i moduli presenti
        const modules = document.querySelectorAll('[data-module]');
        modules.forEach(module => {
            const moduleName = module.getAttribute('data-module');
            this.initModule(moduleName, module);
        });
    }
    
    /**
     * Inizializza un modulo specifico
     */
    initModule(moduleName, element) {
        switch (moduleName) {
            case 'actionHero':
                this.initHeroModule(element);
                break;
            case 'resultsTable':
                this.initResultsModule(element);
                break;
            case 'gallery':
                this.initGalleryModule(element);
                break;
            default:
                console.log(`Modulo ${moduleName} non ha JavaScript specifico`);
        }
    }
    
    /**
     * Inizializza modulo Hero
     */
    initHeroModule(element) {
        // Parallax effect per background
        const heroBg = element.querySelector('.hero-bg');
        if (heroBg) {
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                const rate = scrolled * -0.5;
                heroBg.style.transform = `translateY(${rate}px)`;
            });
        }
        
        // Smooth scroll per link interni
        const heroLinks = element.querySelectorAll('a[href^="#"]');
        heroLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }
    
    /**
     * Inizializza modulo Results
     */
    initResultsModule(element) {
        // Funzionalità già implementate nel modulo PHP
        console.log('Modulo Results inizializzato');
    }
    
    /**
     * Inizializza modulo Gallery
     */
    initGalleryModule(element) {
        // Lightbox per immagini
        const images = element.querySelectorAll('img[data-lightbox]');
        images.forEach(img => {
            img.addEventListener('click', () => {
                this.openLightbox(img.src, img.alt);
            });
        });
    }
    
    /**
     * Apre lightbox
     */
    openLightbox(src, alt) {
        const lightbox = document.createElement('div');
        lightbox.className = 'lightbox';
        lightbox.innerHTML = `
            <div class="lightbox-content">
                <img src="${src}" alt="${alt}">
                <button class="lightbox-close">&times;</button>
            </div>
        `;
        
        document.body.appendChild(lightbox);
        
        // Chiudi lightbox
        const closeBtn = lightbox.querySelector('.lightbox-close');
        closeBtn.addEventListener('click', () => {
            document.body.removeChild(lightbox);
        });
        
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) {
                document.body.removeChild(lightbox);
            }
        });
        
        // ESC per chiudere
        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') {
                document.body.removeChild(lightbox);
                document.removeEventListener('keydown', escHandler);
            }
        });
    }
    
    /**
     * Setup effetti scroll
     */
    setupScrollEffects() {
        // Intersection Observer per animazioni
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);
        
        // Osserva elementi animabili
        const animateElements = document.querySelectorAll('.hero-module, .results-table, .module-wrapper');
        animateElements.forEach(el => {
            observer.observe(el);
        });
    }
}

// Utility functions
const Utils = {
    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    /**
     * Throttle function
     */
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    /**
     * Format time
     */
    formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
    }
};

// Inizializza app quando DOM è pronto
document.addEventListener('DOMContentLoaded', () => {
    window.bolognaMarathon = new BolognaMarathonApp();
});

// CSS per lightbox
const lightboxCSS = `
<style>
.lightbox {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: var(--z-modal, 1050);
    animation: fadeIn 0.3s ease;
}

.lightbox-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
}

.lightbox img {
    max-width: 100%;
    max-height: 100%;
    border-radius: var(--border-radius-lg);
}

.lightbox-close {
    position: absolute;
    top: -40px;
    right: 0;
    background: var(--white);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    font-size: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-fast);
}

.lightbox-close:hover {
    background: var(--primary-color);
    color: var(--white);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.animate-in {
    animation: slideInUp 0.6s ease forwards;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
`;

// Aggiungi CSS lightbox al documento
document.head.insertAdjacentHTML('beforeend', lightboxCSS);
