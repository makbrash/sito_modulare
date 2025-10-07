# 🎯 Modulo Presentation - Bologna Marathon

## 📋 Descrizione

Il modulo **Presentation** è una sezione hero full-width con layout a 2 colonne (50% ciascuna) che presenta il contenuto principale del sito. La colonna sinistra contiene testo, titoli e statistiche, mentre la colonna destra mostra un'immagine fullscreen.

## 🎨 Caratteristiche

### Layout
- **Full-width**: Occupa tutta la larghezza dello schermo
- **2 Colonne**: 50% ciascuna su desktop
- **Responsive**: Stack verticale su mobile
- **Altezza**: 100vh (viewport height)

### Contenuto Sinistra
- **Titolo principale**: Testo bianco in maiuscolo
- **Sottotitolo accent**: Colore primario in maiuscolo
- **Due descrizioni**: Testo esplicativo
- **Statistiche**: 3 card con icone, numeri e etichette

### Contenuto Destra
- **Immagine fullscreen**: Object-fit cover
- **Overlay gradient**: Per migliorare la leggibilità
- **Responsive**: 50vh su mobile

## 🔧 Configurazione

### Parametri Principali

| Parametro | Tipo | Default | Descrizione |
|-----------|------|---------|-------------|
| `title` | text | "DOVE LO SPORT INCONTRA" | Titolo principale |
| `subtitle` | text | "LA STORIA" | Sottotitolo con accent |
| `description1` | textarea | "La Maratona di Bologna è..." | Prima descrizione |
| `description2` | textarea | "Tre giorni di festa..." | Seconda descrizione |
| `image_url` | image | "assets/images/marathon-start.jpg" | URL immagine |
| `image_alt` | text | "Maratona di Bologna" | Alt text per accessibilità |

### Varianti Stile

| Variante | Colori | Uso |
|----------|--------|-----|
| `primary` | Blu scuro | Tema principale |
| `secondary` | Marrone | Tema alternativo |
| `accent` | Viola | Tema accent |

### Statistiche

Ogni statistica contiene:
- **Icona**: Emoji o simbolo
- **Numero**: Valore numerico
- **Etichetta**: Descrizione

## 🎨 Styling

### CSS Variables Utilizzate
```css
--presentation-bg: Sfondo del modulo
--presentation-text: Colore testo principale
--presentation-accent: Colore accent (sottotitolo)
```

### Varianti CSS
- `.presentation--primary`: Tema blu scuro
- `.presentation--secondary`: Tema marrone
- `.presentation--accent`: Tema viola

## 📱 Responsive Design

### Desktop (>1024px)
- Layout 2 colonne 50%
- Altezza 100vh
- Padding generoso

### Tablet (768px-1024px)
- Layout 2 colonne mantenuto
- Font size ridotti
- Padding ottimizzato

### Mobile (<768px)
- Layout stack verticale
- Colonna sinistra: contenuto
- Colonna destra: immagine 50vh
- Font size mobile-friendly

## ⚡ JavaScript

### Funzionalità
- **Intersection Observer**: Animazioni al scroll
- **Image preload**: Caricamento ottimizzato
- **Hover effects**: Effetti sulle statistiche
- **Keyboard navigation**: Accessibilità
- **Error handling**: Gestione errori immagine

### Eventi
- `presentation:imageLoaded`: Immagine caricata
- `presentation:animateIn`: Animazione completata

## 🎯 Esempi Utilizzo

### PHP Base
```php
<?php
echo $renderer->renderModule('presentation', [
    'title' => 'IL TUO TITOLO',
    'subtitle' => 'IL TUO SOTTOTITOLO',
    'description1' => 'Prima descrizione...',
    'description2' => 'Seconda descrizione...',
    'image_url' => 'assets/images/tua-immagine.jpg'
]);
?>
```

### Con Statistiche Personalizzate
```php
<?php
echo $renderer->renderModule('presentation', [
    'title' => 'DOVE LO SPORT INCONTRA LA STORIA',
    'stats' => [
        [
            'icon' => '🏃‍♂️',
            'number' => '15.000+',
            'label' => 'Partecipanti'
        ],
        [
            'icon' => '🌍',
            'number' => '60+',
            'label' => 'Paesi'
        ],
        [
            'icon' => '🏆',
            'number' => '5',
            'label' => 'Categorie'
        ]
    ]
]);
?>
```

### Con Variante Personalizzata
```php
<?php
echo $renderer->renderModule('presentation', [
    'variant' => 'secondary',
    'background_color' => 'linear-gradient(135deg, #2c1810 0%, #8b4513 100%)',
    'accent_color' => '#ff6b35'
]);
?>
```

## 🧪 Testing

### Test Manuale
1. **Desktop**: Verifica layout 2 colonne
2. **Tablet**: Controlla responsive design
3. **Mobile**: Testa stack verticale
4. **Immagini**: Verifica caricamento e fallback
5. **Animazioni**: Controlla smooth scroll
6. **Accessibilità**: Testa navigazione tastiera

### Test JavaScript
```javascript
// Verifica inizializzazione
const presentation = document.querySelector('.presentation');
console.log('Presentation presente:', !!presentation);

// Test aggiornamento statistiche
if (window.Presentation) {
    const instance = new window.Presentation(presentation);
    instance.updateStats([
        {icon: '🎯', number: '100', label: 'Test'}
    ]);
}
```

## 🔍 Troubleshooting

### Problema: Layout non responsive
**Soluzione**: Verifica CSS Grid support e media queries

### Problema: Immagine non si carica
**Soluzione**: Controlla path immagine e fallback

### Problema: Animazioni non funzionano
**Soluzione**: Verifica IntersectionObserver support

### Problema: Statistiche non si aggiornano
**Soluzione**: Controlla formato dati e JavaScript

## 📚 Riferimenti

- **Template**: `presentation.php`
- **Stili**: `presentation.css`
- **JavaScript**: `presentation.js`
- **Manifest**: `module.json`
- **Sistema**: `ModuleRenderer.php`

## ✅ Checklist

- [x] Template PHP funzionante
- [x] CSS responsive mobile-first
- [x] JavaScript con animazioni
- [x] Manifest completo con UI Schema
- [x] Integrazione ModuleRenderer
- [x] Documentazione completa
- [x] Test responsive design
- [x] Accessibilità keyboard
- [x] Performance ottimizzata

---

**Modulo Presentation** - Bologna Marathon System 🎯

*Versione 1.0.0 - Gennaio 2025*
