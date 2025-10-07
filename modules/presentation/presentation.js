/**
 * Modulo: Presentation
 * File: presentation.js
 */

(function() {
  'use strict';

  // Classe principale del modulo
  class Presentation {
    constructor(element) {
      this.element = element;
      this.config = this.parseConfig();
      this.isVisible = false;
      this.observer = null;
      this.init();
    }

    parseConfig() {
      // Parsing configurazione da data attributes
      const config = {};
      const data = this.element.dataset;
      
      if (data.config) {
        try {
          return JSON.parse(data.config);
        } catch (e) {
          console.warn('Configurazione modulo presentation non valida:', e);
        }
      }
      
      return config;
    }

    init() {
      // Inizializzazione modulo
      this.setupIntersectionObserver();
      this.bindEvents();
      this.preloadImage();
    }

    setupIntersectionObserver() {
      // Observer per animazioni quando il modulo entra in vista
      if ('IntersectionObserver' in window) {
        this.observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting && !this.isVisible) {
              this.isVisible = true;
              this.animateIn();
            }
          });
        }, {
          threshold: 0.1,
          rootMargin: '0px 0px -50px 0px'
        });

        this.observer.observe(this.element);
      } else {
        // Fallback per browser senza supporto IntersectionObserver
        this.animateIn();
      }
    }

    bindEvents() {
      // Event listeners per interazioni
      this.bindStatHover();
      this.bindImageEvents();
      this.bindKeyboardEvents();
    }

    bindStatHover() {
      // Effetti hover sulle statistiche
      const stats = this.element.querySelectorAll('.presentation__stat');
      stats.forEach(stat => {
        stat.addEventListener('mouseenter', this.handleStatHover.bind(this));
        stat.addEventListener('mouseleave', this.handleStatLeave.bind(this));
      });
    }

    bindImageEvents() {
      // Eventi per l'immagine
      const image = this.element.querySelector('.presentation__image');
      if (image) {
        image.addEventListener('load', this.handleImageLoad.bind(this));
        image.addEventListener('error', this.handleImageError.bind(this));
      }
    }

    bindKeyboardEvents() {
      // Navigazione da tastiera
      this.element.addEventListener('keydown', this.handleKeydown.bind(this));
      
      // Rendi il modulo focusabile per accessibilità
      this.element.setAttribute('tabindex', '0');
      this.element.setAttribute('role', 'banner');
      this.element.setAttribute('aria-label', 'Sezione presentazione principale');
    }

    handleStatHover(event) {
      const stat = event.currentTarget;
      const icon = stat.querySelector('.presentation__stat-icon');
      
      if (icon) {
        icon.style.transform = 'scale(1.2) rotate(5deg)';
        icon.style.transition = 'transform 0.3s ease';
      }
    }

    handleStatLeave(event) {
      const stat = event.currentTarget;
      const icon = stat.querySelector('.presentation__stat-icon');
      
      if (icon) {
        icon.style.transform = 'scale(1) rotate(0deg)';
      }
    }

    handleImageLoad(event) {
      // Immagine caricata con successo
      const image = event.target;
      image.classList.add('loaded');
      
      // Dispatch evento personalizzato
      this.element.dispatchEvent(new CustomEvent('presentation:imageLoaded', {
        detail: { image: image.src }
      }));
    }

    handleImageError(event) {
      // Errore caricamento immagine
      const image = event.target;
      image.classList.add('error');
      
      // Fallback immagine
      if (this.config.fallback_image) {
        image.src = this.config.fallback_image;
      }
      
      console.warn('Errore caricamento immagine presentation:', image.src);
    }

    handleKeydown(event) {
      // Navigazione da tastiera
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        this.focusStats();
      }
    }

    focusStats() {
      // Focus sulla prima statistica per accessibilità
      const firstStat = this.element.querySelector('.presentation__stat');
      if (firstStat) {
        firstStat.setAttribute('tabindex', '0');
        firstStat.focus();
      }
    }

    preloadImage() {
      // Preload dell'immagine per performance
      const image = this.element.querySelector('.presentation__image');
      if (image && image.src) {
        const img = new Image();
        img.onload = () => {
          image.classList.add('preloaded');
        };
        img.src = image.src;
      }
    }

    animateIn() {
      // Animazione di entrata del modulo
      const content = this.element.querySelector('.presentation__content');
      const image = this.element.querySelector('.presentation__image-container');
      
      if (content) {
        content.style.opacity = '0';
        content.style.transform = 'translateX(-30px)';
        content.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
        
        requestAnimationFrame(() => {
          content.style.opacity = '1';
          content.style.transform = 'translateX(0)';
        });
      }
      
      if (image) {
        image.style.opacity = '0';
        image.style.transform = 'translateX(30px)';
        image.style.transition = 'opacity 0.8s ease 0.3s, transform 0.8s ease 0.3s';
        
        requestAnimationFrame(() => {
          image.style.opacity = '1';
          image.style.transform = 'translateX(0)';
        });
      }
      
      // Animazione statistiche
      this.animateStats();
    }

    animateStats() {
      const stats = this.element.querySelectorAll('.presentation__stat');
      stats.forEach((stat, index) => {
        stat.style.opacity = '0';
        stat.style.transform = 'translateY(20px)';
        stat.style.transition = `opacity 0.6s ease ${0.8 + index * 0.1}s, transform 0.6s ease ${0.8 + index * 0.1}s`;
        
        setTimeout(() => {
          stat.style.opacity = '1';
          stat.style.transform = 'translateY(0)';
        }, 100 + index * 100);
      });
    }

    updateStats(newStats) {
      // Aggiornamento dinamico delle statistiche
      const statsContainer = this.element.querySelector('.presentation__stats');
      if (!statsContainer) return;
      
      statsContainer.innerHTML = '';
      
      newStats.forEach(statData => {
        const statElement = this.createStatElement(statData);
        statsContainer.appendChild(statElement);
      });
      
      // Re-bind eventi
      this.bindStatHover();
    }

    createStatElement(statData) {
      const stat = document.createElement('div');
      stat.className = 'presentation__stat';
      
      stat.innerHTML = `
        <div class="presentation__stat-icon">${statData.icon}</div>
        <div class="presentation__stat-content">
          <span class="presentation__stat-number">${statData.number}</span>
          <span class="presentation__stat-label">${statData.label}</span>
        </div>
      `;
      
      return stat;
    }

    updateImage(newImageUrl, newAlt) {
      // Aggiornamento dinamico dell'immagine
      const image = this.element.querySelector('.presentation__image');
      if (!image) return;
      
      image.style.opacity = '0.7';
      image.style.transition = 'opacity 0.3s ease';
      
      image.onload = () => {
        image.style.opacity = '1';
        image.classList.add('loaded');
      };
      
      image.src = newImageUrl;
      if (newAlt) {
        image.alt = newAlt;
      }
    }

    destroy() {
      // Cleanup per evitare memory leaks
      if (this.observer) {
        this.observer.disconnect();
      }
      
      // Rimuovi event listeners
      const stats = this.element.querySelectorAll('.presentation__stat');
      stats.forEach(stat => {
        stat.removeEventListener('mouseenter', this.handleStatHover);
        stat.removeEventListener('mouseleave', this.handleStatLeave);
      });
      
      this.element.removeEventListener('keydown', this.handleKeydown);
    }
  }

  // Auto-inizializzazione
  document.addEventListener('DOMContentLoaded', function() {
    const modules = document.querySelectorAll('.presentation');
    modules.forEach(element => {
      new Presentation(element);
    });
  });

  // Export per uso esterno
  window.Presentation = Presentation;

})();
