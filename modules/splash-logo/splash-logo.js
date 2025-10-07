/**
 * Splash Logo Module JavaScript
 * Gestisce l'animazione e rimozione dell'overlay splash
 */

(function() {
    'use strict';

    class SplashLogo {
        constructor(element) {
            this.element = element;
            this.duration = parseInt(this.element.dataset.duration) || 200000;
            this.init();
        }

        init() {
            // Previeni scroll durante lo splash
            document.body.style.overflow = 'hidden';
            
            // Avvia la sequenza di animazione
            this.startSequence();
        }

        startSequence() {
            // Dopo la durata specificata, nascondi lo splash
            setTimeout(() => {
                this.hide();
            }, this.duration);
        }

        hide() {
            // Aggiungi classe per fade-out
            this.element.classList.add('hidden');
            
            // Ripristina scroll
            document.body.style.overflow = '';
            
            // Rimuovi elemento dal DOM dopo l'animazione
            setTimeout(() => {
                if (this.element && this.element.parentNode) {
                    this.element.parentNode.removeChild(this.element);
                }
                
                // Emetti evento personalizzato
                const event = new CustomEvent('splash-logo:hidden', {
                    detail: { timestamp: Date.now() }
                });
                document.dispatchEvent(event);
            }, 800); // Durata transizione CSS
        }

        destroy() {
            // Cleanup
            document.body.style.overflow = '';
            if (this.element && this.element.parentNode) {
                this.element.parentNode.removeChild(this.element);
            }
        }
    }

    // Auto-inizializzazione immediata (prima del DOMContentLoaded)
    function initSplash() {
        const splashElements = document.querySelectorAll('.splash-logo');
        splashElements.forEach(element => {
            if (!element.hasAttribute('data-initialized')) {
                new SplashLogo(element);
                element.setAttribute('data-initialized', 'true');
            }
        });
    }

    // Inizializza appena possibile
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSplash);
    } else {
        initSplash();
    }

    // Export per uso esterno
    window.SplashLogo = SplashLogo;

})();

