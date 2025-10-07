/**
 * Modulo: Newsletter Registration Multi-Platform
 * File: newsletter.js
 */

(function() {
    'use strict';

    // Classe principale del modulo Newsletter
    class Newsletter {
        constructor(element) {
            this.element = element;
            this.config = this.parseConfig();
            this.currentTab = this.element.dataset.defaultTab || 'email';
            
            // Elementi DOM
            this.tabs = this.element.querySelectorAll('.newsletter__tab');
            this.panels = this.element.querySelectorAll('.newsletter__panel');
            this.form = this.element.querySelector('.newsletter__form');
            this.whatsappButton = this.element.querySelector('.newsletter__button--whatsapp');
            this.whatsappNameInput = this.element.querySelector('#whatsapp-name');
            
            // Debug
            console.log('Newsletter: Elementi trovati', {
                tabs: this.tabs.length,
                panels: this.panels.length,
                form: !!this.form,
                whatsappButton: !!this.whatsappButton,
                whatsappNameInput: !!this.whatsappNameInput
            });
            
            this.init();
        }

        parseConfig() {
            const config = {};
            const data = this.element.dataset;
            
            if (data.config) {
                try {
                    return JSON.parse(data.config);
                } catch (e) {
                    console.warn('Newsletter: Configurazione non valida', e);
                }
            }
            
            return config;
        }

        init() {
            // Inizializza tab navigation
            this.initTabs();
            
            // Inizializza form email se presente
            if (this.form) {
                this.initEmailForm();
            }
            
            // Inizializza WhatsApp chat se presente
            if (this.whatsappButton && this.whatsappNameInput) {
                this.initWhatsAppChat();
            }
            
            // Animate on scroll
            this.setupIntersectionObserver();
        }

        /* ========================================
           TAB NAVIGATION
           ======================================== */

        initTabs() {
            this.tabs.forEach(tab => {
                tab.addEventListener('click', this.handleTabClick.bind(this));
            });
            
            // Set initial tab
            this.switchTab(this.currentTab);
        }

        handleTabClick(event) {
            const tab = event.currentTarget;
            const tabName = tab.dataset.tab;
            
            if (tabName === this.currentTab) return;
            
            this.switchTab(tabName);
            
            // Track tab change
            this.trackEvent('tab_change', { tab: tabName });
        }

        switchTab(tabName) {
            // Update current tab
            this.currentTab = tabName;
            
            // Update tab buttons
            this.tabs.forEach(tab => {
                const isActive = tab.dataset.tab === tabName;
                tab.classList.toggle('newsletter__tab--active', isActive);
                tab.setAttribute('aria-selected', isActive);
            });
            
            // Update panels
            this.panels.forEach(panel => {
                const isActive = panel.dataset.panel === tabName;
                panel.classList.toggle('newsletter__panel--active', isActive);
                panel.setAttribute('aria-hidden', !isActive);
            });
        }

        /* ========================================
           EMAIL FORM
           ======================================== */

        initEmailForm() {
            this.form.addEventListener('submit', this.handleSubmit.bind(this));
            
            // Real-time validation
            const inputs = this.form.querySelectorAll('.newsletter__input');
            inputs.forEach(input => {
                input.addEventListener('blur', this.validateField.bind(this));
                input.addEventListener('input', () => this.clearFieldError(input));
            });

            // Checkbox validation
            const checkbox = this.form.querySelector('.newsletter__checkbox');
            if (checkbox) {
                checkbox.addEventListener('change', () => this.clearFieldError(checkbox));
            }
        }

        async handleSubmit(event) {
            event.preventDefault();

            // Validate form
            if (!this.validateForm()) {
                return;
            }

            const formData = new FormData(this.form);
            
            try {
                this.setLoading(true);
                this.hideMessages();

                // Send to API
                const response = await fetch(this.form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();

                if (result.success) {
                    this.showSuccess();
                    this.form.reset();
                    
                    // Track conversion
                    this.trackEvent('newsletter_subscribe', { method: 'email' });
                } else {
                    throw new Error(result.message || 'Errore durante l\'iscrizione');
                }

            } catch (error) {
                console.error('Newsletter Error:', error);
                this.showError(error.message);
            } finally {
                this.setLoading(false);
            }
        }

        validateForm() {
            let isValid = true;

            // Validate name
            const nameInput = this.form.querySelector('input[name="name"]');
            if (nameInput && !this.validateName(nameInput.value)) {
                this.showFieldError(nameInput, 'Inserisci un nome valido');
                isValid = false;
            }

            // Validate email
            const emailInput = this.form.querySelector('input[name="email"]');
            if (emailInput && !this.validateEmail(emailInput.value)) {
                this.showFieldError(emailInput, 'Inserisci un\'email valida');
                isValid = false;
            }

            // Validate checkbox
            const checkbox = this.form.querySelector('input[name="privacy"]');
            if (checkbox && !checkbox.checked) {
                this.showFieldError(checkbox, 'Devi accettare la privacy policy');
                isValid = false;
            }

            return isValid;
        }

        validateField(event) {
            const input = event.target;
            const type = input.type;

            if (type === 'text' || input.name === 'name') {
                if (!this.validateName(input.value)) {
                    this.showFieldError(input, 'Campo obbligatorio');
                } else {
                    this.clearFieldError(input);
                }
            } else if (type === 'email') {
                if (!this.validateEmail(input.value)) {
                    this.showFieldError(input, 'Email non valida');
                } else {
                    this.clearFieldError(input);
                }
            }
        }

        validateName(name) {
            return name && name.trim().length >= 2;
        }

        validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        showFieldError(input, message) {
            input.classList.add('newsletter__input--error');
            
            // Rimuovi errore esistente
            const existingError = input.parentElement.querySelector('.newsletter__field-error');
            if (existingError) {
                existingError.remove();
            }

            // Aggiungi nuovo errore
            const errorElement = document.createElement('span');
            errorElement.className = 'newsletter__field-error';
            errorElement.textContent = message;
            errorElement.style.cssText = 'display: block; color: #e74c3c; font-size: 0.85rem; margin-top: 0.25rem;';
            input.parentElement.appendChild(errorElement);
        }

        clearFieldError(input) {
            if (input instanceof Event) {
                input = input.target;
            }
            
            input.classList.remove('newsletter__input--error');
            const error = input.parentElement.querySelector('.newsletter__field-error');
            if (error) {
                error.remove();
            }
        }

        /* ========================================
           WHATSAPP CHAT
           ======================================== */

        initWhatsAppChat() {
            if (!this.whatsappButton) {
                console.warn('Newsletter: Pulsante WhatsApp non trovato');
                return;
            }
            
            console.log('Newsletter: Inizializzo WhatsApp chat', this.whatsappButton);
            this.whatsappButton.addEventListener('click', this.handleWhatsAppClick.bind(this));
        }

        handleWhatsAppClick(event) {
            const name = this.whatsappNameInput.value.trim();
            
            if (!name || name.length < 2) {
                event.preventDefault();
                this.whatsappNameInput.focus();
                this.whatsappNameInput.style.borderColor = '#e74c3c';
                setTimeout(() => {
                    this.whatsappNameInput.style.borderColor = '';
                }, 2000);
                return;
            }
            
            // Build WhatsApp URL with name
            const number = this.whatsappButton.dataset.whatsappNumber;
            const message = `Ciao sono ${name}, vorrei registrarmi`;
            const url = `https://wa.me/${number}?text=${encodeURIComponent(message)}`;
            
            // Update button href
            this.whatsappButton.setAttribute('href', url);
            
            // Track event
            this.trackEvent('whatsapp_chat', { name: name });
        }

        /* ========================================
           MESSAGES & STATES
           ======================================== */

        setLoading(loading) {
            this.element.classList.toggle('is-loading', loading);
        }

        hideMessages() {
            const successMsg = this.element.querySelector('.newsletter__message--success');
            const errorMsg = this.element.querySelector('.newsletter__message--error');
            
            if (successMsg) successMsg.style.display = 'none';
            if (errorMsg) errorMsg.style.display = 'none';
        }

        showSuccess() {
            const successMsg = this.element.querySelector('.newsletter__message--success');
            if (successMsg) {
                successMsg.style.display = 'flex';
                this.element.classList.add('is-success');
                
                // Auto-hide
                setTimeout(() => {
                    successMsg.style.display = 'none';
                    this.element.classList.remove('is-success');
                }, 5000);
            }
        }

        showError(message) {
            const errorMsg = this.element.querySelector('.newsletter__message--error');
            if (errorMsg) {
                const messageText = errorMsg.querySelector('.newsletter__message-text');
                if (messageText && message) {
                    messageText.textContent = message;
                }
                errorMsg.style.display = 'flex';
                
                // Auto-hide
                setTimeout(() => {
                    errorMsg.style.display = 'none';
                }, 5000);
            }
        }

        /* ========================================
           TRACKING
           ======================================== */

        trackEvent(eventName, params = {}) {
            // Google Analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', eventName, {
                    event_category: 'newsletter',
                    event_label: this.currentTab,
                    ...params
                });
            }

            // Facebook Pixel
            if (typeof fbq !== 'undefined' && eventName === 'newsletter_subscribe') {
                fbq('track', 'Lead', {
                    content_name: 'Newsletter',
                    content_category: this.currentTab
                });
            }

            // Custom event
            this.element.dispatchEvent(new CustomEvent(`newsletter:${eventName}`, {
                detail: {
                    tab: this.currentTab,
                    timestamp: Date.now(),
                    ...params
                }
            }));
        }

        /* ========================================
           INTERSECTION OBSERVER
           ======================================== */

        setupIntersectionObserver() {
            if (!('IntersectionObserver' in window)) {
                this.element.classList.add('is-visible');
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.element.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });

            observer.observe(this.element);
        }

        /* ========================================
           DESTROY
           ======================================== */

        destroy() {
            // Remove event listeners
            this.tabs.forEach(tab => {
                tab.removeEventListener('click', this.handleTabClick);
            });
            
            if (this.form) {
                this.form.removeEventListener('submit', this.handleSubmit);
            }
            
            if (this.whatsappButton) {
                this.whatsappButton.removeEventListener('click', this.handleWhatsAppClick);
            }
        }
    }

    /* ========================================
       AUTO-INITIALIZATION
       ======================================== */

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        const newsletters = document.querySelectorAll('.newsletter');
        newsletters.forEach(element => {
            new Newsletter(element);
        });
    });

    // Re-initialize for dynamically added elements (page builder)
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) {
                        if (node.classList && node.classList.contains('newsletter')) {
                            new Newsletter(node);
                        } else if (node.querySelectorAll) {
                            const newsletters = node.querySelectorAll('.newsletter');
                            newsletters.forEach(element => new Newsletter(element));
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Export for external use
    window.Newsletter = Newsletter;

})();
