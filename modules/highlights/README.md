# 🎯 Modulo Highlights - Bologna Marathon

## 📋 Descrizione

Il modulo **Highlights** è una sezione news con slider Swiper responsive che mostra le ultime notizie in formato card orizzontali. Basato sulla sezione "Ultime NEWS" dell'immagine allegata.

## 🎨 Caratteristiche

### Layout
- **Full-width**: Occupa tutta la larghezza dello schermo
- **Slider Swiper**: Navigazione fluida tra le news
- **Responsive**: Slide per view configurabili per dispositivo
- **Navigation**: Bottoni prev/next personalizzati

### Responsive Design (ottimizzato)
- **Desktop XL (≥1536px)**: 6 slide per view - spaceBetween: 30px
- **Desktop (≥1280px)**: 5 slide per view - spaceBetween: 24px
- **Laptop (≥1024px)**: 4 slide per view - spaceBetween: 24px
- **Tablet (≥768px)**: 3 slide per view - spaceBetween: 20px
- **Tablet Small (≥640px)**: 2.5 slide per view - spaceBetween: 20px
- **Smartphone (≥480px)**: 2 slide per view - spaceBetween: 15px
- **Mobile (<480px)**: 1.5 slide per view - spaceBetween: 15px

### Effetti
- **Zoom hover**: Immagine si ingrandisce al passaggio del mouse
- **Lift effect**: Card si solleva leggermente
- **Smooth transitions**: Animazioni fluide
- **Glass navigation**: Bottoni con effetto vetro

## 🔧 Configurazione

### Parametri Principali

| Parametro | Tipo | Default | Descrizione |
|-----------|------|---------|-------------|
| `title` | text | "Ultime NEWS" | Titolo della sezione |
| `highlights` | array | 5 news di esempio | Array di news |

### Struttura Highlight
Ogni highlight contiene:
- **image**: URL dell'immagine
- **title**: Titolo della news
- **url**: Link alla news

## 🎨 Styling

### CSS Variables Utilizzate
```css
--bg-primary: Sfondo del modulo
--text-primary: Colore testo principale
--primary: Colore accent (hover, bottoni)
--bg-glass: Sfondo vetro per bottoni
--space-*: Spacing consistente
--radius-*: Border radius
--transition-*: Transizioni smooth
```

### Classi Principali
- `.highlights`: Container principale
- `.highlights_swiper`: Container Swiper
- `.highlight_card`: Card singola news
- `.highlight_image`: Immagine con zoom
- `.highlight_title`: Titolo news
- `.highlights_btn`: Bottoni navigazione

## ⚡ JavaScript

### Funzionalità
- **Swiper Integration**: Configurazione responsive automatica
- **Hover Effects**: Zoom immagine e lift card
- **Keyboard Navigation**: Frecce sinistra/destra
- **Navigation State**: Bottoni disabilitati ai bordi
- **Dynamic Updates**: Aggiornamento slide dinamico

### Configurazione Swiper (ottimizzata)
```javascript
{
  slidesPerView: 1.5, // Mobile default (mostra peek)
  spaceBetween: 15,
  loop: false,
  grabCursor: true, // Feedback visivo per drag
  watchOverflow: true, // Nasconde navigazione se non necessaria
  navigation: {
    nextEl: '.highlights_btn--next',
    prevEl: '.highlights_btn--prev'
  },
  breakpoints: {
    480: { slidesPerView: 2, spaceBetween: 15 },
    640: { slidesPerView: 2.5, spaceBetween: 20 },
    768: { slidesPerView: 3, spaceBetween: 20 },
    1024: { slidesPerView: 4, spaceBetween: 24 },
    1280: { slidesPerView: 5, spaceBetween: 24 },
    1536: { slidesPerView: 6, spaceBetween: 30 }
  }
}
```

## 🎯 Esempi Utilizzo

### PHP Base
```php
<?php
echo $renderer->renderModule('highlights', [
    'title' => 'Ultime Notizie',
    'highlights' => [
        [
            'image' => 'assets/images/news1.jpg',
            'title' => 'Titolo della prima news',
            'url' => '/news/1'
        ],
        [
            'image' => 'assets/images/news2.jpg',
            'title' => 'Titolo della seconda news',
            'url' => '/news/2'
        ]
    ]
]);
?>
```

### Con News Personalizzate
```php
<?php
echo $renderer->renderModule('highlights', [
    'title' => 'Eventi in Evidenza',
    'highlights' => [
        [
            'image' => 'assets/images/marathon-start.jpg',
            'title' => 'Presentata la Termal Bologna Marathon 2025: in 10.000 runner attesi',
            'url' => '/marathon-2025'
        ],
        [
            'image' => 'assets/images/portici.jpg',
            'title' => 'Anche la 30 Km dei Portici è SOLD OUT!',
            'url' => '/portici-soldout'
        ]
    ]
]);
?>
```

## 🧪 Testing

### Test Manuale
1. **Desktop XL (≥1536px)**: Verifica 6 slide per view
2. **Desktop (≥1280px)**: Verifica 5 slide per view
3. **Laptop (≥1024px)**: Verifica 4 slide per view
4. **Tablet (≥768px)**: Controlla 3 slide per view
5. **Mobile (≥480px)**: Testa 2 slide per view
6. **Small Mobile (<480px)**: Testa 1.5 slide per view (peek effect)
7. **Navigation**: Testa bottoni prev/next
8. **Hover**: Verifica zoom immagine
9. **Keyboard**: Testa frecce sinistra/destra
10. **Responsive**: Testa tutti i breakpoint
11. **Grab Cursor**: Verifica cursor pointer quando hover
12. **Overflow**: Verifica che navigazione si nasconda se non necessaria

### Test JavaScript
```javascript
// Verifica inizializzazione
const highlights = document.querySelector('.highlights');
console.log('Highlights presente:', !!highlights);

// Test aggiornamento slide
if (window.Highlights) {
    const instance = new window.Highlights(highlights);
    instance.updateSlides([
        {image: 'test.jpg', title: 'Test', url: '#'}
    ]);
}
```

## 🔍 Troubleshooting

### Problema: Swiper non si carica
**Soluzione**: Verifica che Swiper sia incluso nei vendors e che la libreria sia caricata prima del modulo JS

### Problema: Slide troppo grandi o troppo piccole
**Soluzione**: NON usare larghezze fisse sulle card. Lascia che Swiper calcoli automaticamente basandosi su `slidesPerView`

### Problema: Breakpoint non funzionano
**Soluzione**: Rimuovi qualsiasi `width` fissa nel CSS. I breakpoint di Swiper gestiscono automaticamente le dimensioni

### Problema: Hover non funziona
**Soluzione**: Verifica che CSS sia caricato correttamente e che le immagini abbiano la classe `.highlight_image`

### Problema: Navigation non funziona
**Soluzione**: Controlla che i selettori `.highlights_btn--prev` e `.highlights_btn--next` siano corretti

### Problema: Immagini deformate
**Soluzione**: Verifica che il wrapper abbia `padding-top: 100%` per aspect ratio 1:1 e che l'immagine sia `position: absolute`

## 📚 Riferimenti

- **Template**: `highlights.php`
- **Stili**: `highlights.css`
- **JavaScript**: `highlights.js`
- **Manifest**: `module.json`
- **Sistema**: `ModuleRenderer.php`
- **Swiper**: https://swiperjs.com/

## ✅ Checklist

- [x] Template PHP funzionante
- [x] CSS responsive mobile-first
- [x] JavaScript con Swiper
- [x] Manifest completo con UI Schema
- [x] Integrazione ModuleRenderer
- [x] Documentazione completa
- [x] Test responsive design
- [x] Accessibilità keyboard
- [x] Performance ottimizzata
- [x] Zoom hover effect
- [x] Navigation prev/next
- [x] No loop, no pagination
- [x] Slide per view responsive

---

**Modulo Highlights** - Bologna Marathon System 🎯

*Versione 1.1.0 - Ottobre 2025 - Fix Swiper Configuration*

## 📝 Changelog

### v1.1.0 - Ottobre 2025
- ✅ **FIX CRITICO**: Rimosso `width` fisso dalle card (era 280px)
- ✅ **FIX**: Slide ora usano `width: 100%` per calcolo automatico Swiper
- ✅ **FIX**: Breakpoint ora funzionano correttamente
- ✅ **NEW**: Aggiunto `slidesPerView: 1.5` per mobile con peek effect
- ✅ **NEW**: Aggiunto breakpoint 640px per tablet small (2.5 slides)
- ✅ **NEW**: Aggiunto breakpoint 1280px per desktop medio (5 slides)
- ✅ **NEW**: Aggiunto breakpoint 1536px per desktop XL (6 slides)
- ✅ **NEW**: Aggiunto `grabCursor: true` per UX migliore
- ✅ **NEW**: Aggiunto `watchOverflow: true` per nascondere navigazione quando non necessaria
- ✅ **IMPROVE**: Aspect ratio 1:1 con `padding-top: 100%` invece di altezza fissa
- ✅ **IMPROVE**: Spacing ottimizzato per ogni breakpoint
- ✅ **DOCS**: Aggiornata documentazione con troubleshooting

### v1.0.0 - Gennaio 2025
- 🎉 Release iniziale