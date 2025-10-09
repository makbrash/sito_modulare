/**
 * Modulo: Partner
 * File: partner.js
 * 
 * Basato su highlights.js ma con autoplay differito e breakpoints specifici
 */

(function() {
  'use strict';

  // Classe principale del modulo
  class Partner {
    constructor(element) {
      this.element = element;
      this.config = this.parseConfig();
      this.swipers = [];
      this.autoplayTimeouts = [];
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
          console.warn('Configurazione modulo partner non valida:', e);
        }
      }
      
      return config;
    }

    init() {
      // Inizializzazione modulo
      this.initSwipers();
      this.bindEvents();
      this.startAutoplay();
    }

    initSwipers() {
      // Trova tutti i swiper nel modulo
      const swiperElements = this.element.querySelectorAll('.partner__swiper');
      
      swiperElements.forEach((swiperEl, index) => {
        const group = swiperEl.dataset.group;
        const swiperConfig = this.getSwiperConfig(group);
        
        // Verifica che Swiper sia disponibile
        if (typeof Swiper === 'undefined') {
          console.error('❌ Swiper library not loaded');
          return;
        }

        // Inizializza Swiper
        try {
          const swiper = new Swiper(swiperEl, swiperConfig);
          
          if (swiper) {
            console.log(`✅ Swiper Partner Group ${group} created successfully`);
            console.log(`📊 Current slidesPerView:`, swiper.params.slidesPerView);
            console.log(`📊 Total slides:`, swiper.slides.length);
            
            // Salva riferimento
            this.swipers.push({
              swiper: swiper,
              element: swiperEl,
              group: group
            });
            
            // Aggiorna stato navigazione iniziale
            this.updateNavigationState(swiper, swiperEl);
            
            // Bind eventi Swiper
            swiper.on('slideChange', () => {
              this.updateNavigationState(swiper, swiperEl);
            });
            
            swiper.on('resize', () => {
              console.log(`📐 Swiper Group ${group} resized - slidesPerView:`, swiper.params.slidesPerView);
            });
          }
        } catch (error) {
          console.error(`❌ Error initializing Swiper Group ${group}:`, error);
        }
      });
    }

    getSwiperConfig(group) {
      // Configurazioni specifiche per gruppo
      const baseConfig = {
        spaceBetween: 20,
        loop: false,
        grabCursor: true,
        slidesPerView: 1,
        watchOverflow: true,
        autoplay: {
          delay: 3000,
          disableOnInteraction: false,
          pauseOnMouseEnter: true
        },
        navigation: {
          nextEl: `.partner__swiper--group${group} .partner__btn--next`,
          prevEl: `.partner__swiper--group${group} .partner__btn--prev`,
        }
      };

      // Configurazioni specifiche per gruppo
      if (group === '1') {
        // Gruppo 1: 7 slide per grandi schermi, 1 per smartphone
        return {
          ...baseConfig,
          slidesPerView: 1,
          breakpoints: {
            480: { slidesPerView: 1 },
            640: { slidesPerView: 2 },
            768: { slidesPerView: 3 },
            1024: { slidesPerView: 5 },
            1280: { slidesPerView: 7 }
          }
        };
      } else if (group === '4') {
        // Gruppo 4 Credits: Ancora più slide per loghi piccoli
        return {
          ...baseConfig,
          slidesPerView: 3,
          spaceBetween: 10,
          breakpoints: {
            480: { slidesPerView: 3 },
            640: { slidesPerView: 6 },
            768: { slidesPerView: 8 },
            1024: { slidesPerView: 10 },
            1280: { slidesPerView: 13 },
            1536: { slidesPerView: 18 }
          }
        };
      } else {
        // Gruppo 2 e 3: 10 slide per grandi schermi, 1 per smartphone
        return {
          ...baseConfig,
          slidesPerView: 1,
          breakpoints: {
            480: { slidesPerView: 1 },
            640: { slidesPerView: 2 },
            768: { slidesPerView: 4 },
            1024: { slidesPerView: 6 },
            1280: { slidesPerView: 8 },
            1536: { slidesPerView: 10 }
          }
        };
      }
    }

    bindEvents() {
      // Event listeners per interazioni
      this.bindCardHover();
      this.bindKeyboardEvents();
      this.bindVisibilityChange();
    }

    bindCardHover() {
      // Effetti hover sulle card
      const cards = this.element.querySelectorAll('.partner__card');
      cards.forEach(card => {
        card.addEventListener('mouseenter', this.handleCardHover.bind(this));
        card.addEventListener('mouseleave', this.handleCardLeave.bind(this));
      });
    }

    bindKeyboardEvents() {
      // Navigazione da tastiera
      this.element.addEventListener('keydown', this.handleKeydown.bind(this));
      
      // Rendi il modulo focusabile per accessibilità
      this.element.setAttribute('tabindex', '0');
      this.element.setAttribute('role', 'region');
      this.element.setAttribute('aria-label', 'Sezione partner e sponsor');
    }

    bindVisibilityChange() {
      // Pausa autoplay quando la pagina non è visibile
      document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
          this.pauseAutoplay();
        } else {
          this.resumeAutoplay();
        }
      });
    }

    handleCardHover(event) {
      const card = event.currentTarget;
      const logo = card.querySelector('.partner__logo');
      
      if (logo) {
        logo.style.filter = 'brightness(1)';
        logo.style.transition = 'filter 0.3s ease';
      }
    }

    handleCardLeave(event) {
      const card = event.currentTarget;
      const logo = card.querySelector('.partner__logo');
      
      if (logo) {
        logo.style.filter = 'brightness(0.9)';
      }
    }

    handleKeydown(event) {
      // Navigazione da tastiera per il primo swiper attivo
      const activeSwiper = this.swipers.find(s => s.swiper && !s.swiper.destroyed);
      if (!activeSwiper) return;
      
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        activeSwiper.swiper.slidePrev();
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        activeSwiper.swiper.slideNext();
      }
    }

    updateNavigationState(swiper, swiperEl) {
      // Aggiorna stato bottoni navigazione
      const prevBtn = swiperEl.querySelector('.partner__btn--prev');
      const nextBtn = swiperEl.querySelector('.partner__btn--next');
      
      if (prevBtn && nextBtn) {
        prevBtn.disabled = swiper.isBeginning;
        nextBtn.disabled = swiper.isEnd;
      }
    }

    startAutoplay() {
      // Avvia autoplay con delay differito per ogni gruppo
      this.swipers.forEach((swiperData, index) => {
        const delay = (index + 1) * 2000; // 2s, 4s, 6s...
        
        const timeout = setTimeout(() => {
          if (swiperData.swiper && !swiperData.swiper.destroyed) {
            swiperData.swiper.autoplay.start();
            console.log(`🎬 Autoplay started for Group ${swiperData.group}`);
          }
        }, delay);
        
        this.autoplayTimeouts.push(timeout);
      });
    }

    pauseAutoplay() {
      // Pausa tutti gli autoplay
      this.swipers.forEach(swiperData => {
        if (swiperData.swiper && !swiperData.swiper.destroyed) {
          swiperData.swiper.autoplay.stop();
        }
      });
    }

    resumeAutoplay() {
      // Riprendi tutti gli autoplay
      this.swipers.forEach(swiperData => {
        if (swiperData.swiper && !swiperData.swiper.destroyed) {
          swiperData.swiper.autoplay.start();
        }
      });
    }

    destroy() {
      // Cleanup per evitare memory leaks
      this.swipers.forEach(swiperData => {
        if (swiperData.swiper) {
          swiperData.swiper.destroy(true, true);
        }
      });
      
      // Clear timeouts
      this.autoplayTimeouts.forEach(timeout => {
        clearTimeout(timeout);
      });
      
      // Rimuovi event listeners
      const cards = this.element.querySelectorAll('.partner__card');
      cards.forEach(card => {
        card.removeEventListener('mouseenter', this.handleCardHover);
        card.removeEventListener('mouseleave', this.handleCardLeave);
      });
      
      this.element.removeEventListener('keydown', this.handleKeydown);
      document.removeEventListener('visibilitychange', this.bindVisibilityChange);
    }
  }

  // Auto-inizializzazione
  document.addEventListener('DOMContentLoaded', function() {
    const modules = document.querySelectorAll('.partner');
    modules.forEach(element => {
      new Partner(element);
    });
  });

  // Export per uso esterno
  window.Partner = Partner;

})();
