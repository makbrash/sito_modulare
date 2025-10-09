/**
 * Core JavaScript - Bologna Marathon
 * Sistema modulare JavaScript
 */

class BolognaMarathonApp {
    constructor() {
        this.countdownInstances = [];
        this.init();
    }
    
    init() {
        this.setupMenu();
        this.setupModules();
        this.setupScrollEffects();
        this.setupCountdowns();
    }
    
    /**
     * Setup countdown centralizzato
     * Gestisce tutti i countdown presenti nella pagina
     */
    setupCountdowns() {
        const countdownElements = document.querySelectorAll('[data-countdown]');
        countdownElements.forEach(element => {
            const targetDate = element.getAttribute('data-countdown');
            if (targetDate) {
                this.initCountdown(element, targetDate);
            }
        });
    }
    
    /**
     * Inizializza un countdown specifico
     */
    initCountdown(element, targetDate) {
        const countdownDate = new Date(targetDate).getTime();
        const format = element.getAttribute('data-countdown-format') || 'full';
        
        const updateCountdown = () => {
            const now = new Date().getTime();
            const distance = countdownDate - now;
            
            if (distance < 0) {
                element.innerHTML = '<span class="countdown-ended">Evento concluso</span>';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            // Formati diversi
            if (format === 'compact') {
                // Formato compatto per menu: 146g 12h 54m 05s
                element.textContent = `${days}g ${hours}h ${minutes.toString().padStart(2, '0')}m ${seconds.toString().padStart(2, '0')}s`;
            } else if (format === 'full') {
                // Formato completo per modulo countdown
                this.renderFullCountdown(element, days, hours, minutes, seconds);
            }
        };
        
        updateCountdown();
        const interval = setInterval(updateCountdown, 1000);
        
        // Salva istanza per cleanup
        this.countdownInstances.push({ element, interval });
    }
    
    /**
     * Render countdown formato completo
     */
    renderFullCountdown(element, days, hours, minutes, seconds) {
        element.innerHTML = `
            <div class="countdown-item">
                <div class="countdown-number">${days}</div>
                <div class="countdown-label">Gior.</div>
            </div>
            <div class="countdown-item">
                <div class="countdown-number">${hours}</div>
                <div class="countdown-label">Ore</div>
            </div>
            <div class="countdown-item">
                <div class="countdown-number">${minutes.toString().padStart(2, '0')}</div>
                <div class="countdown-label">Min.</div>
            </div>
            <div class="countdown-item">
                <div class="countdown-number">${seconds.toString().padStart(2, '0')}</div>
                <div class="countdown-label">Sec.</div>
            </div>
        `;
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
            rootMargin: '0px 0px -100px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);
        
        // Osserva elementi animabili (escludendo menu, hero e primo blocco)
        const animateElements = document.querySelectorAll('.results-table, .module-wrapper:not([data-module="actionHero"]):not([data-module="menu"]):not(.no-animate)');
        
        // Filtra ulteriormente per escludere il primo blocco dopo il menu
        const filteredElements = Array.from(animateElements).filter((el, index) => {
            // Escludi il primo elemento dopo il menu
            if (index === 0) return false;
            return true;
        });
        
        // Applica stato iniziale a tutti gli elementi prima di osservarli
        filteredElements.forEach(el => {
            // Applica stile iniziale solo se l'elemento non è già visibile nel viewport
            const rect = el.getBoundingClientRect();
            const isVisible = rect.top < window.innerHeight && rect.bottom > 0;
            
            if (!isVisible) {
                el.style.opacity = '0';
                el.style.transform = 'translateY(60px)';
            }
            
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

// CSS per lightbox e controllo animazioni
const lightboxCSS = `
<style>
/* Disabilita animazioni per elementi specifici */
.no-animate,
.module-wrapper[data-module="menu"],
.module-wrapper[data-module="actionHero"] {
    animation: none !important;
    transform: none !important;
}

/* Stato iniziale per elementi animabili - Applicato via JS dopo DOMContentLoaded */



@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.animate-in {
    animation: slideInUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards !important;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(60px);
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
