/**
 * Button Module JavaScript
 * Funzionalità interattive per pulsanti
 */

document.addEventListener('DOMContentLoaded', function() {
    // Gestione pulsanti con loading
    const buttons = document.querySelectorAll('.btn');
    
    buttons.forEach(button => {
        // Click handler per pulsanti con data-loading
        if (button.hasAttribute('data-loading')) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Aggiungi stato loading
                this.classList.add('btn-loading');
                this.setAttribute('disabled', 'true');
                
                // Simula operazione asincrona
                const loadingDuration = parseInt(this.getAttribute('data-loading')) || 2000;
                
                setTimeout(() => {
                    this.classList.remove('btn-loading');
                    this.removeAttribute('disabled');
                }, loadingDuration);
            });
        }
        
        // Gestione click per pulsanti con bounce effect
        if (button.hasAttribute('data-bounce')) {
            button.addEventListener('click', function() {
                this.classList.add('btn-bounce');
                
                setTimeout(() => {
                    this.classList.remove('btn-bounce');
                }, 600);
            });
        }
        
        // Gestione hover per pulsanti con pulse
        if (button.hasAttribute('data-pulse')) {
            button.addEventListener('mouseenter', function() {
                this.classList.add('btn-pulse');
            });
            
            button.addEventListener('mouseleave', function() {
                this.classList.remove('btn-pulse');
            });
        }
    });
    
    // Gestione form submission con loading
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const submitButton = form.querySelector('.btn[type="submit"]');
        if (submitButton) {
            form.addEventListener('submit', function() {
                submitButton.classList.add('btn-loading');
                submitButton.setAttribute('disabled', 'true');
            });
        }
    });
    
    // Gestione link esterni con icona
    const externalLinks = document.querySelectorAll('a[href^="http"]:not([href*="' + window.location.hostname + '"])');
    externalLinks.forEach(link => {
        if (!link.querySelector('.fa-external-link-alt')) {
            const icon = document.createElement('i');
            icon.className = 'fas fa-external-link-alt';
            icon.style.marginLeft = '4px';
            link.appendChild(icon);
        }
    });
});

// Utility functions per gestione pulsanti programmatica
window.ButtonUtils = {
    /**
     * Setta stato loading a un pulsante
     */
    setLoading: function(button, loading = true) {
        if (loading) {
            button.classList.add('btn-loading');
            button.setAttribute('disabled', 'true');
        } else {
            button.classList.remove('btn-loading');
            button.removeAttribute('disabled');
        }
    },
    
    /**
     * Cambia variante di un pulsante
     */
    changeVariant: function(button, variant) {
        // Rimuovi tutte le varianti
        const variants = ['primary', 'secondary', 'marathon', 'portici', 'runtune', 'ghost', 'outline'];
        variants.forEach(v => button.classList.remove(`btn-${v}`));
        
        // Aggiungi nuova variante
        button.classList.add(`btn-${variant}`);
    },
    
    /**
     * Cambia dimensione di un pulsante
     */
    changeSize: function(button, size) {
        // Rimuovi tutte le dimensioni
        const sizes = ['small', 'medium', 'large', 'xl'];
        sizes.forEach(s => button.classList.remove(`btn-${s}`));
        
        // Aggiungi nuova dimensione
        button.classList.add(`btn-${size}`);
    },
    
    /**
     * Animazione bounce
     */
    bounce: function(button) {
        button.classList.add('btn-bounce');
        setTimeout(() => {
            button.classList.remove('btn-bounce');
        }, 600);
    }
};
