# Menu Module - Bologna Marathon

## 🎯 Panoramica

Il modulo Menu per Bologna Marathon è un sistema di navigazione moderno e elegante che include:

- **Logo + Titolo**: Logo della maratona con titolo "TERMAL BOLOGNA MARATHON"
- **Countdown Timer**: Timer in tempo reale per la prossima edizione
- **Search Box AI**: Interfaccia di ricerca con animazioni avanzate
- **Menu Mobile**: Menu fullscreen con grandi voci e design elegante
- **Sticky Navigation**: Menu fisso con effetti di trasparenza

## 🚀 Funzionalità

### Desktop
- Menu sticky con background trasparente e blur
- Logo + titolo maratona con countdown animato
- Search box centrale con effetti glassmorphism
- Menu hamburger animato con colori primary

### Mobile
- Layout ottimizzato: logo + hamburger
- Menu fullscreen con overlay primary
- Grandi voci menu con animazioni
- Search box integrata nel menu mobile

### Search Box AI
- Placeholder personalizzabile ("Chiedi a Ingrid")
- Animazioni focus/blur con effetti glassmorphism
- Suggerimenti interattivi
- Pulsante invio con animazioni
- Feedback visivo durante l'invio

## ⚙️ Configurazione

### Parametri Principali

```json
{
  "logo": "assets/images/logo-bologna-marathon.svg",
  "logo_alt": "Bologna Marathon",
  "marathon_title": "TERMAL BOLOGNA MARATHON",
  "countdown_date": "2024-04-14T09:00:00",
  "search_placeholder": "Chiedi a Ingrid",
  "menu_items": [
    { "label": "Home", "url": "#home", "target": "_self" },
    { "label": "La Gara", "url": "#gara", "target": "_self" },
    { "label": "Risultati", "url": "#risultati", "target": "_self" },
    { "label": "News", "url": "#news", "target": "_self" },
    { "label": "Contatti", "url": "#contatti", "target": "_self" }
  ]
}
```

### Opzioni CSS Variables

```css
:root {
    --menu-height: 80px;
    --menu-bg-glass: rgba(255, 255, 255, 0.1);
    --search-bg: rgba(255, 255, 255, 0.15);
    --mobile-overlay-bg: rgba(35, 168, 235, 0.95);
    --countdown-color: #00ffff;
}
```

## 🎨 Design Features

### Glassmorphism
- Background trasparente con blur
- Effetti di profondità e trasparenza
- Bordi sottili con opacità

### Animazioni
- Transizioni smooth con cubic-bezier
- Hover effects con scale e translate
- Countdown timer con pulse animation
- Search box con focus animations

### Responsive Design
- Breakpoint ottimizzati
- Layout adattivo per tutti i dispositivi
- Menu mobile fullscreen elegante

## 🔧 Utilizzo

### Installazione
1. Il modulo è già incluso nel sistema
2. Aggiungi alla pagina tramite admin panel
3. Configura i parametri desiderati

### Personalizzazione
```php
// Includi il modulo con configurazione personalizzata
$config = [
    'marathon_title' => 'LA MIA MARATONA',
    'countdown_date' => '2024-12-25T10:00:00',
    'search_placeholder' => 'Cerca nel sito...'
];
```

### JavaScript API
```javascript
// Inizializza manualmente
const menuManager = new MenuManager('menu-id');

// Metodi disponibili
menuManager.toggleMobileMenu();
menuManager.closeMobileMenu();
menuManager.sendQuery('testo query');
```

## 📱 Responsive Breakpoints

- **Desktop**: > 1200px - Layout completo
- **Tablet**: 768px - 1200px - Layout compatto
- **Mobile**: < 768px - Menu hamburger
- **Small Mobile**: < 480px - Layout ottimizzato

## 🎯 Accessibilità

- Supporto completo per screen reader
- Navigazione da tastiera (Tab, Enter, Esc)
- Contrasti ottimizzati per leggibilità
- Aria labels per tutti i controlli interattivi

## 🔄 Aggiornamenti

### Versione 3.0.0
- ✅ Nuovo design con search box AI
- ✅ Countdown timer integrato
- ✅ Menu mobile fullscreen
- ✅ Animazioni avanzate
- ✅ Glassmorphism effects
- ✅ Responsive design migliorato

### Prossime Funzionalità
- 🔄 Integrazione chat AI reale
- 🔄 Personalizzazione colori avanzata
- 🔄 Multi-language support
- 🔄 Dark mode toggle

## 🐛 Risoluzione Problemi

### Menu non si apre
- Verifica che il JavaScript sia caricato
- Controlla console per errori
- Assicurati che l'ID del menu sia unico

### Countdown non funziona
- Verifica formato data: `YYYY-MM-DDTHH:mm:ss`
- Controlla che la data sia futura
- Verifica console per errori JavaScript

### Search box non animata
- Verifica che il CSS sia compilato
- Controlla che le classi CSS siano applicate
- Assicurati che il JavaScript sia inizializzato

## 📞 Supporto

Per problemi o domande:
1. Controlla la documentazione
2. Verifica la console del browser
3. Contatta il team di sviluppo

---

*Modulo Menu v3.0.0 - Bologna Marathon 2024*
