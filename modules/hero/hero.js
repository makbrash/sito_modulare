/**
 * Hero Module JavaScript
 * Logica per il modulo hero
 */

class HeroModule {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupParallax();
        this.setupAnimations();
    }
    
    setupParallax() {
        const heroBg = document.querySelector('.hero-bg');
        if (!heroBg) return;
        
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            heroBg.style.transform = `translateY(${rate}px)`;
        });
    }
    
    setupAnimations() {
        const heroTitle = document.querySelector('.hero-title');
        const heroSubtitle = document.querySelector('.hero-subtitle');
        const heroDescription = document.querySelector('.hero-description');
        
        if (heroTitle) {
            this.animateText(heroTitle, 0);
        }
        
        if (heroSubtitle) {
            this.animateText(heroSubtitle, 200);
        }
        
        if (heroDescription) {
            this.animateText(heroDescription, 400);
        }
    }
    
    animateText(element, delay) {
        setTimeout(() => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            element.style.transition = 'all 0.8s ease';
            
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, 100);
        }, delay);
    }
}

// Inizializza quando il DOM è pronto
document.addEventListener('DOMContentLoaded', () => {
    new HeroModule();
});