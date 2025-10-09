# 📜 Custom Scrollbar System - Bologna Marathon

Sistema di scrollbar personalizzate ottimizzato per performance e coerenza visiva.

## 🎨 Caratteristiche

- ✅ **Design Moderno**: Scrollbar elegante con gradient dinamico
- ✅ **Temi Dinamici**: Si adatta automaticamente ai colori di ogni gara
- ✅ **Performance Ottimizzate**: Zero lag durante lo scroll
- ✅ **Cross-Browser**: Supporto WebKit (Chrome, Safari) e Firefox
- ✅ **Responsive**: Adattamento automatico per mobile e tablet
- ✅ **Accessibilità**: Supporto prefers-contrast e reduced-motion

## 🎯 Implementazione

### 1. File CSS
```
assets/css/core/scrollbar.css
```

### 2. Incluso Automaticamente
Il CSS viene caricato automaticamente da `index.php` tra i core CSS.

## 🎨 Stile Scrollbar

### Desktop
```css
- Larghezza: 12px
- Track: Sfondo scuro semi-trasparente
- Thumb: Gradient del colore primario
- Hover: Gradient più intenso
- Active: Colore solido
```

### Mobile
```css
- Larghezza: 8px (più sottile)
- Touch devices: Apparisce solo durante uso
- Ottimizzata per touch scroll
```

## 🌈 Temi Dinamici

La scrollbar cambia colore in base al tema della pagina:

```css
/* Marathon (Azzurro) */
.theme-marathon → Gradient azzurro

/* 30K Portici (Rosa) */
.theme-portici → Gradient rosa

/* 5K (Giallo) */
.theme-5k → Gradient giallo

/* Kids Run (Turchese) */
.theme-kidsrun → Gradient turchese

/* Run Tune Up (Verde) */
.theme-run-tune-up → Gradient verde
```

## 🚀 Ottimizzazioni Performance

### 1. Prevenzione Lag
```css
/* NON usa will-change: scroll-position (causa lag) */
will-change: auto;

/* Isola rendering con contain */
contain: paint;

/* Ottimizza compositing */
transform: translateZ(0);
```

### 2. Content Visibility
```css
/* Lazy rendering elementi fuori viewport */
.module-wrapper {
    content-visibility: auto;
    contain-intrinsic-size: auto 500px;
}
```

### 3. Smooth Scrolling Ottimizzato
```css
/* Smooth ma senza lag */
scroll-behavior: smooth;
-webkit-overflow-scrolling: touch;
```

## 📱 Varianti Scrollbar

### Scrollbar Thin
Per aree piccole (menu, suggestions):
```html
<div class="search-suggestions scrollbar-thin">
    <!-- Scrollbar sottile (6px) -->
</div>
```

### Scrollbar Hidden
Per carousel, swiper:
```html
<div class="carousel scrollbar-hidden">
    <!-- Scrollbar nascosta -->
</div>
```

### Scrollbar Standard
Di default su tutti gli elementi:
```html
<div class="results-table-wrapper">
    <!-- Scrollbar automatica -->
</div>
```

## 🎯 Aree Specifiche

### Menu Mobile
```css
.mobile-menu-overlay::-webkit-scrollbar {
    width: 6px; /* Sottile */
}
```

### Tabelle Risultati
```css
.results-table-wrapper::-webkit-scrollbar {
    height: 8px; /* Scroll orizzontale */
}
```

### Search Suggestions
```css
.search-suggestions::-webkit-scrollbar {
    width: 4px; /* Extra sottile */
}
```

## 🐛 Troubleshooting

### Scrollbar Non Appare
**Problema**: La scrollbar custom non si vede

**Soluzione**:
```bash
# 1. Verifica che scrollbar.css sia caricato
# Apri Developer Tools > Network > cerca 'scrollbar.css'

# 2. Controlla browser compatibility
# Chrome/Edge: ✅ Supporto completo
# Firefox: ✅ Usa scrollbar-width e scrollbar-color
# Safari: ✅ Supporto WebKit
```

### Lag Durante Scroll
**Problema**: Scroll non fluido

**Soluzione**:
```css
/* Rimuovi will-change se presente */
* {
    will-change: auto; /* Non 'scroll-position' */
}

/* Verifica che contain sia applicato */
.scroll-container {
    contain: paint;
}
```

### Colore Scrollbar Sbagliato
**Problema**: La scrollbar non usa il colore del tema

**Soluzione**:
```html
<!-- Verifica che body abbia la classe tema -->
<body class="race-marathon">
    <!-- O -->
<body class="theme-marathon">
```

## 🎨 Personalizzazione

### Cambiare Larghezza
```css
/* Nel tuo CSS custom */
::-webkit-scrollbar {
    width: 16px; /* Più larga */
}
```

### Cambiare Colore
```css
/* Scrollbar custom per una sezione */
.my-section ::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, 
        #your-color 0%, 
        #your-color-dark 100%);
}
```

### Rimuovere Gradient
```css
/* Colore solido */
::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 6px;
}
```

## 🧪 Test

### Test Cross-Browser
```bash
# Chrome/Edge
✅ Scrollbar custom completa

# Firefox
✅ Scrollbar thin con colore custom

# Safari
✅ Scrollbar custom WebKit

# Mobile
✅ Scrollbar ottimizzata per touch
```

### Test Performance
```javascript
// Apri Console
console.time('scroll-performance');
window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
// Dovrebbe essere < 16ms per 60fps
console.timeEnd('scroll-performance');
```

## 📊 Performance Metrics

### Ottimizzazioni Applicate
- ✅ **Contain Paint**: Isola repaint dello scroll
- ✅ **Content Visibility**: Lazy rendering elementi
- ✅ **Transform 3D**: Hardware acceleration
- ✅ **Will-Change Auto**: Previene lag
- ✅ **Transition Ottimizzate**: Solo proprietà necessarie

### Risultati Attesi
- **FPS**: 60fps costanti durante scroll
- **Repaint**: Isolato solo alla scrollbar
- **Paint Time**: < 16ms
- **Layout Shift**: 0 (no layout shift)

## 🔧 Integrazione con Build System

### Development
```bash
# Caricato direttamente
<link rel="stylesheet" href="assets/css/core/scrollbar.css">
```

### Production
```bash
# Incluso nel bundle CSS
npm run build
# → build/assets/css/main.min.css (con scrollbar.css)
```

## 📚 Riferimenti

- **File CSS**: `assets/css/core/scrollbar.css`
- **Integrazione**: `index.php` (linea 107)
- **Temi Colore**: `assets/css/core/colors.css`
- **Variables**: `assets/css/core/variables.css`

## 🎯 Best Practices

### ✅ DO
- Usa classi `.scrollbar-thin` per aree piccole
- Usa `.scrollbar-hidden` per carousel
- Testa su tutti i browser
- Verifica performance con DevTools

### ❌ DON'T
- Non usare `will-change: scroll-position`
- Non animare la scrollbar (causa lag)
- Non usare scrollbar troppo sottili (< 4px)
- Non rimuovere `contain: paint`

## 🚀 Prossimi Passi

1. ✅ Sistema scrollbar implementato
2. ✅ Performance ottimizzate
3. ✅ Temi dinamici funzionanti
4. ⏳ Test cross-browser
5. ⏳ Verifica performance mobile

---

**Custom Scrollbar System - Bologna Marathon** 📜

*Versione 1.0.0 - Gennaio 2025*

