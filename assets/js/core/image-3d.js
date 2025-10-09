/**
 * Image 3D Effect
 * Effetto 3D al mouse hover e giroscopio per immagini con classe img-3d
 */

(function() {
  'use strict';

  // Configurazione
  const config = {
    perspective: 800,
    maxRotation: 4,            // Rotazione desktop
    maxRotationMobile: 12,     // Rotazione mobile (3x più marcata)
    defaultScale: 1.05,        // Scala di default (sempre attiva)
    transitionRotation: '0.5s ease-out',  // Transizione rotazione
    transitionScale: '0.6s ease-out',     // Transizione scala iniziale
    selector: 'img.img-3d, .img-3d img'
  };

  // Classe principale
  class Image3D {
    constructor(element, options = {}) {
      this.element = element;
      this.config = { ...config, ...options };
      this.isTouch = false;
      this.init();
    }

    init() {
      // Applica stili iniziali
      this.element.style.transformStyle = 'preserve-3d';
      this.element.style.willChange = 'transform';
      
      // Applica scala di default
      this.applyDefaultTransform();
      
      // Detect touch device
      this.isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
      
      // Bind eventi
      if (this.isTouch) {
        this.initGyroscope();
      } else {
        this.element.addEventListener('mouseenter', this.handleMouseEnter.bind(this));
        this.element.addEventListener('mousemove', this.handleMouseMove.bind(this));
        this.element.addEventListener('mouseleave', this.handleMouseLeave.bind(this));
      }
    }

    applyDefaultTransform() {
      // Applica scala di default con transizione lenta
      this.element.style.transition = this.config.transitionScale;
      this.element.style.transform = `
        perspective(${this.config.perspective}px) 
        rotateX(0) 
        rotateY(0) 
        scale(${this.config.defaultScale})
      `;
    }

    handleMouseEnter() {
      // Cambia transizione per rotazione veloce
      this.element.style.transition = `
        transform ${this.config.transitionRotation},
        scale ${this.config.transitionScale}
      `;
    }

    handleMouseMove(e) {
      const rect = this.element.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      const centerX = rect.width / 2;
      const centerY = rect.height / 2;

      const rotateX = ((y - centerY) / centerY) * this.config.maxRotation;
      const rotateY = ((x - centerX) / centerX) * -this.config.maxRotation;

      this.element.style.transform = `
        perspective(${this.config.perspective}px) 
        rotateX(${rotateX}deg) 
        rotateY(${rotateY}deg) 
        scale(${this.config.defaultScale})
      `;
    }

    handleMouseLeave() {
      // Torna alla scala di default
      this.element.style.transition = this.config.transitionScale;
      this.applyDefaultTransform();
    }

    initGyroscope() {
      // Check se il dispositivo supporta DeviceOrientation
      if (window.DeviceOrientationEvent) {
        window.addEventListener('deviceorientation', this.handleOrientation.bind(this), true);
      }
    }

    handleOrientation(event) {
      // Beta: inclinazione avanti/indietro (-180 a 180)
      // Gamma: inclinazione sinistra/destra (-90 a 90)
      const beta = event.beta;
      const gamma = event.gamma;

      // Limita i valori (range ridotto per maggiore sensibilità)
      const maxTilt = 20;  // Ridotto da 30 a 20 per più sensibilità
      const clampedBeta = Math.max(-maxTilt, Math.min(maxTilt, beta));
      const clampedGamma = Math.max(-maxTilt, Math.min(maxTilt, gamma));

      // Usa maxRotationMobile per smartphone (più marcato)
      const rotateX = (clampedBeta / maxTilt) * this.config.maxRotationMobile;
      const rotateY = (clampedGamma / maxTilt) * this.config.maxRotationMobile;

      // Applica transform
      this.element.style.transition = this.config.transitionRotation;
      this.element.style.transform = `
        perspective(${this.config.perspective}px) 
        rotateX(${rotateX}deg) 
        rotateY(${rotateY}deg) 
        scale(${this.config.defaultScale})
      `;
    }

    destroy() {
      this.element.removeEventListener('mouseenter', this.handleMouseEnter);
      this.element.removeEventListener('mousemove', this.handleMouseMove);
      this.element.removeEventListener('mouseleave', this.handleMouseLeave);
      window.removeEventListener('deviceorientation', this.handleOrientation);
      this.element.style.transform = '';
      this.element.style.transition = '';
      this.element.style.willChange = '';
    }
  }

  // Auto-inizializzazione
  function initImage3D() {
    const images = document.querySelectorAll(config.selector);
    
    images.forEach(img => {
      // Evita inizializzazione multipla
      if (img.dataset.image3dInitialized) return;
      
      // Escludi immagini con classe .no-3d o attributo data-3d="false"
      if (img.classList.contains('no-3d') || img.dataset['3d'] === 'false') {
        return;
      }
      
      // Inizializza effetto
      new Image3D(img);
      
      // Marca come inizializzato
      img.dataset.image3dInitialized = 'true';
    });
  }

  // Inizializzazione al DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initImage3D);
  } else {
    initImage3D();
  }

  // Re-inizializza su contenuti dinamici
  const observer = new MutationObserver(initImage3D);
  observer.observe(document.body, {
    childList: true,
    subtree: true
  });

  // Export per uso esterno
  window.Image3D = Image3D;
  window.initImage3D = initImage3D;

})();

