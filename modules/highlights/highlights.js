/**
 * Modulo: Highlights
 * File: highlights.js
 */

(function() {
  'use strict';

  // Classe principale del modulo
  class Highlights {
    constructor(element) {
      this.element = element;
      this.config = this.parseConfig();
      this.swiper = null;
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
          console.warn('Configurazione modulo highlights non valida:', e);
        }
      }
      
      return config;
    }

    init() {
      // Inizializzazione modulo
      this.initSwiper();
      this.bindEvents();
    }

    initSwiper() {
      // Configurazione Swiper responsive ottimizzata
      const swiperConfig = {
        // Configurazione base
        slidesPerView: 1, // Mobile: mostra 1.5 slide per indicare che ci sono altre slide
        spaceBetween: 0,
        loop: false,
        grabCursor: true,
        watchOverflow: true, // Nasconde navigazione se tutte le slide sono visibili
        
        // Navigazione
        navigation: {
          nextEl: '.highlights_btn--next',
          prevEl: '.highlights_btn--prev',
        },
        
        // Breakpoints responsive (mobile-first)
        breakpoints: {
          // >= 480px (smartphone landscape)
          480: {
            slidesPerView: 1,
          },
          // >= 640px (tablet piccolo)
          640: {
            slidesPerView: 1,
          },
          // >= 768px (tablet)
          768: {
            slidesPerView: 3,
          },
          // >= 1024px (desktop piccolo)
          1024: {
            slidesPerView: 4,
          },
          // >= 1280px (desktop medio)
          1280: {
            slidesPerView: 5,
          }
          // >= 1536px (desktop grande)

        }
      };

      // Verifica che Swiper sia disponibile
      if (typeof Swiper === 'undefined') {
        console.error('❌ Swiper library not loaded');
        return;
      }

      // Inizializza Swiper
      try {
        this.swiper = new Swiper('.highlights_swiper', swiperConfig);
        
        if (this.swiper) {
          console.log('✅ Swiper Highlights created successfully');
          console.log('📊 Current slidesPerView:', this.swiper.params.slidesPerView);
          console.log('📊 Total slides:', this.swiper.slides.length);
          
          // Aggiorna stato navigazione iniziale
          this.updateNavigationState();
          
          // Bind eventi Swiper (DOPO l'inizializzazione)
          this.swiper.on('slideChange', () => {
            this.updateNavigationState();
          });
          
          this.swiper.on('resize', () => {
            console.log('📐 Swiper resized - slidesPerView:', this.swiper.params.slidesPerView);
          });
        }
      } catch (error) {
        console.error('❌ Error initializing Swiper:', error);
      }
    }

    bindEvents() {
      // Event listeners per interazioni
      this.bindCardHover();
      this.bindKeyboardEvents();
    }

    bindCardHover() {
      // Effetti hover sulle card
      const cards = this.element.querySelectorAll('.highlight_card');
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
      this.element.setAttribute('aria-label', 'Sezione highlights news');
    }

    handleCardHover(event) {
      const card = event.currentTarget;
      const image = card.querySelector('.highlight_image');
      
      if (image) {
        image.style.transform = 'scale(1.1)';
        image.style.transition = 'transform 0.6s ease';
      }
    }

    handleCardLeave(event) {
      const card = event.currentTarget;
      const image = card.querySelector('.highlight_image');
      
      if (image) {
        image.style.transform = 'scale(1)';
      }
    }

    handleKeydown(event) {
      // Verifica che swiper sia inizializzato
      if (!this.swiper) {
        return;
      }
      
      // Navigazione da tastiera
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        this.swiper.slidePrev();
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        this.swiper.slideNext();
      }
    }

    updateNavigationState() {
      // Verifica che swiper sia inizializzato
      if (!this.swiper) {
        return;
      }
      
      // Aggiorna stato bottoni navigazione
      const prevBtn = this.element.querySelector('.highlights_btn--prev');
      const nextBtn = this.element.querySelector('.highlights_btn--next');
      
      if (prevBtn && nextBtn) {
        prevBtn.disabled = this.swiper.isBeginning;
        nextBtn.disabled = this.swiper.isEnd;
      }
    }

    updateSlides(newHighlights) {
      // Verifica che swiper sia inizializzato
      if (!this.swiper) {
        return;
      }
      
      // Aggiornamento dinamico delle slide
      const wrapper = this.element.querySelector('.swiper-wrapper');
      if (!wrapper) return;
      
      wrapper.innerHTML = '';
      
      newHighlights.forEach(highlight => {
        const slide = this.createSlideElement(highlight);
        wrapper.appendChild(slide);
      });
      
      // Aggiorna Swiper
      this.swiper.update();
      this.bindCardHover();
    }

    createSlideElement(highlight) {
      const slide = document.createElement('div');
      slide.className = 'swiper-slide';
      
      slide.innerHTML = `
        <a href="${highlight.url}" class="highlight_card">
          <div class="highlight_image-wrapper">
            <img src="${highlight.image}" 
                 alt="${highlight.title}"
                 class="highlight_image"
                 loading="lazy">
          </div>
          <div class="highlight_content">
            <h3 class="highlight_title">${highlight.title}</h3>
          </div>
        </a>
      `;
      
      return slide;
    }

    destroy() {
      // Cleanup per evitare memory leaks
      if (this.swiper) {
        this.swiper.destroy(true, true);
      }
      
      // Rimuovi event listeners
      const cards = this.element.querySelectorAll('.highlight_card');
      cards.forEach(card => {
        card.removeEventListener('mouseenter', this.handleCardHover);
        card.removeEventListener('mouseleave', this.handleCardLeave);
      });
      
      this.element.removeEventListener('keydown', this.handleKeydown);
    }
  }

  // Auto-inizializzazione
  document.addEventListener('DOMContentLoaded', function() {
    const modules = document.querySelectorAll('.highlights');
    modules.forEach(element => {
      new Highlights(element);
    });
  });

  // Export per uso esterno
  window.Highlights = Highlights;

})();
