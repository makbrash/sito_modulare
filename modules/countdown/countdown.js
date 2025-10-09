/**
 * Countdown Module JavaScript - Bologna Marathon
 * Sistema di inizializzazione countdown con fallback
 */

(function() {
    'use strict';

    /**
     * Inizializza countdown con supporto per app.js centralizzato
     */
    function initCountdowns() {
        const countdownElements = document.querySelectorAll('[data-countdown]');
        
        countdownElements.forEach(element => {
            // Skip se già inizializzato
            if (element.hasAttribute('data-countdown-initialized')) {
                return;
            }
            
            const targetDate = element.getAttribute('data-countdown');
            const format = element.getAttribute('data-countdown-format') || 'full';
            
            if (!targetDate) return;
            
            // Marca come inizializzato
            element.setAttribute('data-countdown-initialized', 'true');
            
            // Prova a usare sistema centralizzato se disponibile
            if (window.bolognaMarathon && window.bolognaMarathon.initCountdown) {
                window.bolognaMarathon.initCountdown(element, targetDate);
            } else {
                // Fallback locale
                initCountdownLocal(element, targetDate, format);
            }
        });
    }
    
    /**
     * Inizializzazione countdown locale (fallback)
     */
    function initCountdownLocal(element, targetDate, format) {
        const countdownDate = new Date(targetDate).getTime();
        
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
            
            if (format === 'compact') {
                // Formato compatto per menu
                element.textContent = `${days}g ${hours}h ${minutes.toString().padStart(2, '0')}m ${seconds.toString().padStart(2, '0')}s`;
            } else {
                // Formato completo per modulo countdown
                renderFullCountdown(element, days, hours, minutes, seconds);
            }
        };
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }
    
    /**
     * Render countdown formato completo
     */
    function renderFullCountdown(element, days, hours, minutes, seconds) {
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
    
    // Inizializza al caricamento DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCountdowns);
    } else {
        // DOM già caricato
        initCountdowns();
    }
    
    // Ri-inizializza quando app.js è caricato (per usare sistema centralizzato)
    window.addEventListener('load', function() {
        // Aspetta un momento per essere sicuri che app.js sia inizializzato
        setTimeout(() => {
            if (window.bolognaMarathon && window.bolognaMarathon.setupCountdowns) {
                // Rimuovi marker di inizializzazione per permettere re-init
                document.querySelectorAll('[data-countdown-initialized]').forEach(el => {
                    el.removeAttribute('data-countdown-initialized');
                });
                // Usa sistema centralizzato
                window.bolognaMarathon.setupCountdowns();
            }
        }, 100);
    });
    
})();

