# Modulo Partner

Modulo per visualizzare partner e sponsor con gallerie Swiper responsive.

## Caratteristiche

- **Quattro gruppi sponsor**: Sponsor Principali, Sponsor, Sponsor Tecnici, Credits
- **Nascondi testi**: Opzione per nascondere tutti i testi (titoli e sottotitoli)
- **Swiper responsive**: Breakpoints ottimizzati per ogni gruppo
- **Autoplay differito**: Ogni gruppo inizia con delay diverso
- **Design moderno**: Card con effetti hover e backdrop blur
- **Credits più piccoli**: Il gruppo credits ha loghi ridotti per maggiore densità
- **Accessibilità**: Navigazione da tastiera e screen reader

## Configurazione

### Gruppi Sponsor

1. **Sponsor Principali** (Gruppo 1)
   - Main Sponsor, Official Car, Official Water
   - Breakpoints: 7 slide per desktop, 1 per mobile

2. **Sponsor** (Gruppo 2)  
   - Technical Sponsor, Official Beer
   - Breakpoints: 10 slide per desktop, 1 per mobile

3. **Sponsor Tecnici** (Gruppo 3)
   - Sponsor 2 + Sponsor
   - Breakpoints: 10 slide per desktop, 1 per mobile

4. **Credits** (Gruppo 4)
   - Loghi più piccoli per credits
   - Breakpoints: 12 slide per desktop, 2 per mobile
   - Design: Loghi ridotti all'80%, card più trasparenti

### Breakpoints

#### Gruppo 1 (Sponsor Principali)
- Mobile: 1 slide
- Tablet: 2-3 slide  
- Desktop: 5-7 slide

#### Gruppo 2 e 3 (Sponsor e Sponsor Tecnici)
- Mobile: 1 slide
- Tablet: 2-4 slide
- Desktop: 6-10 slide

#### Gruppo 4 (Credits)
- Mobile: 2 slide
- Tablet: 4-6 slide
- Desktop: 8-12 slide

## Autoplay

- **Delay differito**: 2s, 4s, 6s per ogni gruppo
- **Pausa hover**: Si ferma al passaggio del mouse
- **Pausa visibility**: Si ferma quando la pagina non è visibile
- **Riprendi**: Automatico quando la pagina torna visibile

## File Struttura

```
modules/partner/
├── partner.php          # Template PHP
├── partner.css          # Stili CSS
├── partner.js           # JavaScript con Swiper
├── module.json          # Manifest modulo
├── install.sql          # Setup database
└── README.md           # Documentazione
```

## Utilizzo

### Nel Page Builder
1. Aggiungi il modulo "Partner"
2. Configura i gruppi da mostrare
3. Personalizza il titolo se necessario

### Nel codice
```php
// Configurazione personalizzata
$config = [
    'title' => 'I Nostri Partner',
    'hide_texts' => false,  // Nascondi tutti i testi
    'show_group1' => true,  // Sponsor Principali
    'show_group2' => true,  // Sponsor
    'show_group3' => true,  // Sponsor Tecnici
    'show_group4' => true   // Credits
];

// Esempio: Solo loghi senza testi
$config = [
    'hide_texts' => true,
    'show_group1' => true,
    'show_group2' => true,
    'show_group3' => true,
    'show_group4' => true
];
```

## Dipendenze

- **Swiper.js**: Per le gallerie responsive
- **Font Awesome**: Per le icone di navigazione
- **CSS Variables**: Per i colori e spacing

## Responsive Design

Il modulo è completamente responsive con:
- **Mobile First**: Design ottimizzato per mobile
- **Breakpoints**: 480px, 640px, 768px, 1024px, 1280px, 1536px
- **Touch Friendly**: Navigazione touch ottimizzata
- **Performance**: Lazy loading delle immagini

## Accessibilità

- **Keyboard Navigation**: Frecce per navigare
- **Screen Reader**: ARIA labels appropriati
- **Focus Management**: Focus visibile sui controlli
- **Color Contrast**: Contrasti conformi WCAG

## Browser Support

- **Modern Browsers**: Chrome, Firefox, Safari, Edge
- **Mobile**: iOS Safari, Chrome Mobile
- **Fallback**: Funziona anche senza JavaScript

## Troubleshooting

### Swiper non si carica
- Verifica che Swiper.js sia incluso
- Controlla errori console
- Verifica path delle immagini

### Autoplay non funziona
- Verifica che la pagina sia visibile
- Controlla che non ci siano errori JavaScript
- Verifica configurazione Swiper

### Responsive non funziona
- Verifica breakpoints CSS
- Controlla viewport meta tag
- Testa su dispositivi reali
