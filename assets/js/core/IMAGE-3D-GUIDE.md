# Effetto 3D Immagini

Sistema avanzato con effetto 3D interattivo, scala di default e supporto giroscopio per smartphone.

## Come Funziona

L'effetto 3D viene applicato alle immagini con classe `img-3d`:

### Desktop
- **Scala di default**: 1.05 (immagini già leggermente ingrandite)
- **Rotazione 3D**: Al movimento del mouse (veloce)
- **Scala hover**: 1.08 (ingrandimento ulteriore lento)

### Smartphone
- **Scala di default**: 1.05
- **Rotazione 3D**: Con giroscopio del dispositivo
- **Movimento fluido**: Segue l'inclinazione del telefono

## Utilizzo

### Metodo 1: Classe CSS (Consigliato)
```html
<img src="image.jpg" alt="Demo" class="img-3d">
```

### Metodo 2: Container
```html
<div class="img-3d">
  <img src="image.jpg" alt="Demo">
</div>
```

## Configurazione

### Configurazione Globale
```javascript
// Modifica in image-3d.js
const config = {
  perspective: 800,                          // Prospettiva 3D
  maxRotation: 8,                            // Rotazione massima (gradi)
  defaultScale: 1.05,                        // Scala di default
  hoverScale: 1.08,                          // Scala al hover
  transitionRotation: '0.1s ease-out',       // Transizione veloce rotazione
  transitionScale: '0.6s ease-out',          // Transizione lenta scala
  selector: 'img.img-3d, .img-3d img'       // Solo immagini con classe
};
```

### Inizializzazione Manuale
```javascript
// Inizializza su immagine specifica
const img = document.querySelector('.mia-immagine');
const effect = new Image3D(img, {
  perspective: 1000,
  maxRotation: 10,
  scale: 1.05
});

// Distruggi effetto
effect.destroy();
```

### Re-inizializza su Contenuti Dinamici
```javascript
// Dopo aver caricato contenuti via AJAX
window.initImage3D();
```

## Esempi

### Hero Image
```html
<div class="hero">
  <img src="hero.jpg" class="img-3d" alt="Hero">
  <!-- Desktop: hover per effetto 3D -->
  <!-- Mobile: muovi il telefono per effetto 3D -->
</div>
```

### Gallery
```html
<div class="gallery">
  <img src="img1.jpg" class="img-3d" alt="Image 1">
  <img src="img2.jpg" class="img-3d" alt="Image 2">
  <img src="img3.jpg" class="img-3d" alt="Image 3">
  <!-- Tutte le immagini già scalate di default -->
</div>
```

### Partner Logos
```html
<div class="partner__card">
  <img src="logo.png" class="partner__logo img-3d" alt="Partner">
  <!-- Scala 1.05 default, 1.08 al hover -->
</div>
```

### Giroscopio Smartphone
```html
<!-- L'effetto giroscopio si attiva automaticamente su dispositivi touch -->
<img src="product.jpg" class="img-3d" alt="Product">
<!-- Inclina il telefono per vedere l'effetto 3D -->
```

## Personalizzazione per Modulo

### CSS Custom
```css
/* Aumenta effetto per hero */
.hero .img-3d {
  transition: transform 0.2s ease-out;
}

/* Disabilita effetto su mobile */
@media (max-width: 768px) {
  .img-3d {
    transform: none !important;
  }
}
```

### JavaScript Custom
```javascript
// Configurazione specifica per gallery
document.querySelectorAll('.gallery img').forEach(img => {
  new Image3D(img, {
    maxRotation: 15,
    scale: 1.1
  });
});
```

## Performance

### Ottimizzazioni
- **MutationObserver**: Osserva DOM per inizializzare nuove immagini
- **Event Delegation**: Gestione efficiente eventi
- **Debounce**: Previene inizializzazioni multiple
- **Transform Hardware**: Usa GPU per animazioni smooth

### Best Practices
- Usa su immagini già caricate
- Evita su immagini troppo grandi
- Disabilita su touch devices se necessario

## Disabilitare Effetto

### Per Immagine Specifica
```html
<!-- Aggiungi classe no-3d -->
<img src="image.jpg" alt="No 3D" class="no-3d">

<!-- O usa data attribute -->
<img src="image.jpg" alt="No 3D" data-3d="false">
```

### Per Sezione
```html
<!-- Aggiungi classe no-3d a tutte le immagini -->
<div class="gallery">
  <img src="img1.jpg" class="no-3d" alt="No 3D">
  <img src="img2.jpg" class="no-3d" alt="No 3D">
</div>
```

### Globalmente
```javascript
// Rimuovi script da index.php
// assets/js/core/image-3d.js
```

## Supporto Giroscopio (Smartphone)

### Come Funziona
Su dispositivi touch (smartphone/tablet), l'effetto 3D usa il giroscopio invece del mouse:

1. **Inclinazione Avanti/Indietro**: Controlla rotazione asse X
2. **Inclinazione Sinistra/Destra**: Controlla rotazione asse Y
3. **Limiti**: ±30° di inclinazione massima
4. **Scala**: Mantiene scala di default (1.05)

### Permessi iOS
Su iOS 13+ potrebbe essere necessario richiedere permessi:

```javascript
// Richiesta permessi iOS
if (typeof DeviceOrientationEvent.requestPermission === 'function') {
  DeviceOrientationEvent.requestPermission()
    .then(response => {
      if (response === 'granted') {
        console.log('Giroscopio abilitato');
      }
    });
}
```

### Testing
Per testare su smartphone:
1. Apri il sito su dispositivo mobile
2. Inclina il telefono
3. Osserva l'immagine seguire il movimento

## Browser Support

### Desktop
- Chrome/Edge (latest) - Mouse events
- Firefox (latest) - Mouse events
- Safari (latest) - Mouse events
- Opera (latest) - Mouse events

### Mobile
- iOS Safari 13+ - Giroscopio (richiede permessi)
- Chrome Mobile - Giroscopio
- Firefox Mobile - Giroscopio
- Samsung Internet - Giroscopio

## Troubleshooting

### Effetto non funziona
1. Verifica che `image-3d.js` sia caricato
2. Controlla che l'immagine abbia classe corretta
3. Verifica console per errori

### Effetto troppo forte
```javascript
// Riduci maxRotation
new Image3D(img, { maxRotation: 4 });
```

### Effetto troppo lento
```javascript
// Riduci transition time
const config = {
  transition: '0.05s ease-out'
};
```

### Conflitti con altri script
```javascript
// Disabilita MutationObserver
// Inizializza manualmente
window.initImage3D();
```

