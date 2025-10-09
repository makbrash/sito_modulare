# Layout System - Bologna Marathon

Sistema di layout modulare basato su CSS Grid per il sito della Bologna Marathon.

## 🏗️ Architettura

Il sistema utilizza **CSS Grid** per creare un layout responsive a 12 colonne, simile a Bootstrap ma ottimizzato per le esigenze del progetto.

### File Coinvolti
- `assets/css/core/variables.css` - Variabili del sistema
- `assets/css/core/layout.css` - Classi layout principali
- `index.php` - Include automatico del CSS

## 📐 Variabili CSS

```css
/* Layout System */
--container-max-width: 1200px;
--container-padding: var(--space-md);
--grid-columns: 12;
--grid-gap: var(--space-md);
--grid-gap-sm: var(--space-sm);
--grid-gap-lg: var(--space-lg);

/* Breakpoints */
--bp-xs: 0px;      /* Mobile */
--bp-sm: 576px;    /* Small */
--bp-md: 768px;    /* Medium */
--bp-lg: 992px;    /* Large */
--bp-xl: 1200px;   /* Extra Large */
--bp-xxl: 1400px;  /* Extra Extra Large */
```

## 🎯 Utilizzo Base

### Container
```html
<!-- Container con max-width -->
<div class="container">
    <!-- Contenuto -->
</div>

<!-- Container full-width -->
<div class="container-fluid">
    <!-- Contenuto -->
</div>
```

### Row e Colonne
```html
<div class="container">
    <div class="row">
        <div class="col-12">Colonna 12 (100%)</div>
    </div>
    
    <div class="row">
        <div class="col-6">Colonna 6 (50%)</div>
        <div class="col-6">Colonna 6 (50%)</div>
    </div>
    
    <div class="row">
        <div class="col-4">Colonna 4 (33%)</div>
        <div class="col-4">Colonna 4 (33%)</div>
        <div class="col-4">Colonna 4 (33%)</div>
    </div>
</div>
```

## 📱 Responsive Design

### Mobile First
Le classi base (es. `col-6`) si applicano da mobile in su.

### Breakpoint Specifici
```html
<div class="row">
    <!-- Mobile: 12 colonne, Tablet: 6 colonne, Desktop: 3 colonne -->
    <div class="col-12 col-md-6 col-lg-3">
        Contenuto
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        Contenuto
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        Contenuto
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        Contenuto
    </div>
</div>
```

### Classi Responsive Disponibili
- `col-sm-*` - da 576px
- `col-md-*` - da 768px
- `col-lg-*` - da 992px
- `col-xl-*` - da 1200px
- `col-xxl-*` - da 1400px

## 🎨 Utility per Gap

```html
<!-- Gap normale (default) -->
<div class="row">
    <div class="col-6">Colonna</div>
    <div class="col-6">Colonna</div>
</div>

<!-- Senza gap -->
<div class="row row--no-gap">
    <div class="col-6">Colonna</div>
    <div class="col-6">Colonna</div>
</div>

<!-- Gap piccolo -->
<div class="row row--gap-sm">
    <div class="col-6">Colonna</div>
    <div class="col-6">Colonna</div>
</div>

<!-- Gap grande -->
<div class="row row--gap-lg">
    <div class="col-6">Colonna</div>
    <div class="col-6">Colonna</div>
</div>
```

## 🎯 Allineamento

### Allineamento Verticale (Row)
```html
<div class="row row--align-start">   <!-- Top -->
<div class="row row--align-center">  <!-- Center -->
<div class="row row--align-end">     <!-- Bottom -->
<div class="row row--align-stretch"> <!-- Stretch -->
```

### Allineamento Orizzontale (Row)
```html
<div class="row row--justify-start">     <!-- Left -->
<div class="row row--justify-center">    <!-- Center -->
<div class="row row--justify-end">       <!-- Right -->
<div class="row row--justify-between">   <!-- Space Between -->
<div class="row row--justify-around">    <!-- Space Around -->
<div class="row row--justify-evenly">    <!-- Space Evenly -->
```

### Allineamento Singole Colonne
```html
<div class="col-6 col--align-center">Centrato verticalmente</div>
<div class="col-6 col--align-end">Allineato in basso</div>
```

## 📏 Utility Spacing

### Margin
```html
<div class="m-0">Margin 0</div>
<div class="m-1">Margin XS</div>
<div class="m-2">Margin SM</div>
<div class="m-3">Margin MD</div>
<div class="m-4">Margin LG</div>
<div class="m-5">Margin XL</div>
```

### Padding
```html
<div class="p-0">Padding 0</div>
<div class="p-1">Padding XS</div>
<div class="p-2">Padding SM</div>
<div class="p-3">Padding MD</div>
<div class="p-4">Padding LG</div>
<div class="p-5">Padding XL</div>
```

### Spacing Specifico
```html
<!-- Margin -->
<div class="mt-3">Margin Top MD</div>
<div class="mb-3">Margin Bottom MD</div>
<div class="ml-3">Margin Left MD</div>
<div class="mr-3">Margin Right MD</div>

<!-- Padding -->
<div class="pt-3">Padding Top MD</div>
<div class="pb-3">Padding Bottom MD</div>
<div class="pl-3">Padding Left MD</div>
<div class="pr-3">Padding Right MD</div>
```

## 🔄 Flexbox Utilities

```html
<!-- Direzione -->
<div class="d-flex flex-row">      <!-- Riga (default) -->
<div class="d-flex flex-column">   <!-- Colonna -->

<!-- Wrap -->
<div class="d-flex flex-wrap">     <!-- Wrap -->
<div class="d-flex flex-nowrap">   <!-- No Wrap -->

<!-- Justify Content -->
<div class="d-flex justify-content-start">    <!-- Left -->
<div class="d-flex justify-content-center">   <!-- Center -->
<div class="d-flex justify-content-end">      <!-- Right -->
<div class="d-flex justify-content-between">  <!-- Space Between -->

<!-- Align Items -->
<div class="d-flex align-items-start">   <!-- Top -->
<div class="d-flex align-items-center">  <!-- Center -->
<div class="d-flex align-items-end">     <!-- Bottom -->
```

## 👁️ Display Utilities

### Display Base
```html
<div class="d-none">   <!-- Hidden -->
<div class="d-block">  <!-- Block -->
<div class="d-flex">   <!-- Flex -->
<div class="d-grid">   <!-- Grid -->
```

### Display Responsive
```html
<!-- Mobile: hidden, Desktop: block -->
<div class="d-none d-md-block">Visibile solo su tablet+</div>

<!-- Mobile: block, Desktop: hidden -->
<div class="d-block d-md-none">Visibile solo su mobile</div>
```

## 🎨 Esempi Pratici

### Layout Hero
```html
<div class="container">
    <div class="row row--align-center" style="min-height: 80vh;">
        <div class="col-12 col-lg-6">
            <h1>Titolo Hero</h1>
            <p>Descrizione</p>
        </div>
        <div class="col-12 col-lg-6">
            <img src="hero-image.jpg" alt="Hero">
        </div>
    </div>
</div>
```

### Layout Card Grid
```html
<div class="container">
    <div class="row">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card">Card 1</div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card">Card 2</div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card">Card 3</div>
        </div>
    </div>
</div>
```

### Layout Footer
```html
<div class="container">
    <div class="row row--gap-lg">
        <div class="col-12 col-md-6 col-lg-3">
            <h3>Sezione 1</h3>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <h3>Sezione 2</h3>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <h3>Sezione 3</h3>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <h3>Sezione 4</h3>
        </div>
    </div>
</div>
```

## 🧪 Test

Per testare il sistema layout, apri il file `test-layout.html` nel browser:
```
http://localhost/sito_modulare/test-layout.html
```

## 🔧 Personalizzazione

### Modificare Gap Globale
```css
/* In variables.css */
:root {
    --grid-gap: 2rem; /* Gap più grande */
}
```

### Modificare Container Max Width
```css
/* In variables.css */
:root {
    --container-max-width: 1400px; /* Container più largo */
}
```

### Aggiungere Breakpoint Personalizzati
```css
/* In variables.css */
:root {
    --bp-custom: 900px;
}

/* In layout.css */
@media (min-width: 900px) {
    .col-custom-6 { grid-column: span 6; }
}
```

## 📋 Best Practices

1. **Mobile First**: Sempre partire dalle classi base (es. `col-12`)
2. **Progressive Enhancement**: Aggiungere breakpoint maggiori (es. `col-md-6`)
3. **Semantic HTML**: Usare container, row e col per struttura logica
4. **Accessibilità**: Mantenere ordine logico del contenuto
5. **Performance**: Evitare troppe classi responsive se non necessarie

## 🚀 Integrazione nei Moduli

Il sistema layout è automaticamente disponibile in tutti i moduli:

```php
<!-- In un modulo PHP -->
<div class="container">
    <div class="row">
        <div class="col-12 col-md-6">
            <?= $content ?>
        </div>
        <div class="col-12 col-md-6">
            <?= $sidebar ?>
        </div>
    </div>
</div>
```

## ✅ Checklist Implementazione

- [x] Variabili CSS per grid system
- [x] Classi container e row
- [x] Sistema colonne responsive (12 colonne)
- [x] Breakpoints (xs, sm, md, lg, xl, xxl)
- [x] Utility per gap (no-gap, gap-sm, gap-lg)
- [x] Allineamento (align, justify)
- [x] Spacing utilities (margin, padding)
- [x] Flexbox utilities
- [x] Display utilities (responsive)
- [x] Integrazione in index.php
- [x] File di test
- [x] Documentazione completa

