# 🎬 Splash Logo Module

Modulo overlay animato con logo che appare all'inizio del caricamento della pagina.

## 📋 Descrizione

Questo modulo mostra un overlay full-screen con il logo Bologna Marathon all'inizio del caricamento della pagina. Il logo appare con un'animazione fade-in e lampeggia dolcemente, seguito da un fade-out dell'intero overlay.

## ✨ Caratteristiche

- **Overlay Full-Screen**: Copre l'intera viewport durante il caricamento
- **Animazione Logo**: Fade-in del logo con effetto lampeggio dolce
- **Background Gradient**: Usa `var(--gradient-dark)` dal sistema
- **Auto-rimozione**: Si nasconde automaticamente dopo la durata specificata
- **Responsive**: Si adatta a tutti i dispositivi
- **Accessibilità**: Supporta `prefers-reduced-motion`

## 🎨 Sequenza Animazioni

1. **0.0s - 0.6s**: Logo fade-in + scale da 0.8 a 1.0
2. **0.6s - 1.7s**: Logo lampeggia dolcemente (pulse continuo)
3. **1.7s - 2.5s**: Overlay fade-out
4. **2.5s**: Elemento rimosso dal DOM

## 📦 Configurazione

### Parametri Disponibili

```json
{
  "logo_url": "assets/images/logo-bologna-marathon.svg",
  "duration": 2500,
  "logo_size": 200,
  "pulse_speed": 2
}
```

### Parametri Dettagliati

| Parametro | Tipo | Default | Descrizione |
|-----------|------|---------|-------------|
| `logo_url` | string | `assets/images/logo-bologna-marathon.svg` | Percorso del logo SVG |
| `duration` | number | `2500` | Durata totale in millisecondi (500-5000) |
| `logo_size` | number | `200` | Dimensione minima del logo in pixel (100-500) |
| `pulse_speed` | number | `2` | Velocità lampeggio in secondi (1-5) |

## 🚀 Utilizzo

### Nel Template PHP

```php
<?php
echo $renderer->renderModule('splashLogo', [
    'logo_url' => 'assets/images/logo-bologna-marathon.svg',
    'duration' => 2500,
    'logo_size' => 250,
    'pulse_speed' => 2
]);
?>
```

### Nel Page Builder

1. Aggiungi il modulo "Splash Logo" all'inizio della pagina
2. Configura i parametri tramite l'interfaccia
3. Salva e testa la pagina

### Posizionamento Consigliato

⚠️ **IMPORTANTE**: Questo modulo deve essere il **primo** nella pagina per apparire correttamente durante il caricamento.

## 🎨 Stili CSS

### Classi Principali

- `.splash-logo`: Container principale (fixed, z-index: 9999)
- `.splash-logo__overlay`: Overlay con background gradient
- `.splash-logo__content`: Container centrato del logo
- `.splash-logo__image`: Immagine del logo

### Animazioni

- `logoFadeIn`: Fade-in + scale del logo (0.6s)
- `splashFadeOut`: Fade-out dell'overlay (0.8s)
- `logoPulse`: Lampeggio dolce del logo (infinite)

### Personalizzazione CSS

```css
/* Cambia colore ombra logo */
.splash-logo__image {
    filter: drop-shadow(0 0 30px rgba(35, 168, 235, 0.5));
}

/* Cambia velocità lampeggio */
.splash-logo__image {
    animation-duration: 3s; /* invece di 2s */
}
```

## 📱 Responsive

### Breakpoints

- **Desktop**: Logo max 80vw × 40vh
- **Tablet** (≤768px): Logo max 70vw × 30vh
- **Mobile** (≤480px): Logo max 60vw × 25vh

## ♿ Accessibilità

- Supporta `prefers-reduced-motion`: disabilita animazioni per utenti sensibili
- Logo ha `alt` text descrittivo
- Overlay non interferisce con screen reader dopo rimozione

## 🔧 JavaScript API

### Eventi Personalizzati

```javascript
// Evento quando lo splash è completamente nascosto
document.addEventListener('splash-logo:hidden', function(e) {
    console.log('Splash hidden at:', e.detail.timestamp);
});
```

### Rimozione Manuale

```javascript
const splash = document.querySelector('.splash-logo');
const splashInstance = new SplashLogo(splash);
splashInstance.hide(); // Nasconde immediatamente
```

## 🧪 Testing

### Test Manuale

1. Ricarica la pagina
2. Verifica che il logo appaia centrato
3. Verifica animazione lampeggio
4. Verifica fade-out dopo 2.5s
5. Verifica rimozione dal DOM

### Test Responsive

```bash
# Testa su diversi viewport
- Desktop: 1920×1080
- Tablet: 768×1024
- Mobile: 375×667
```

## 🐛 Troubleshooting

### Logo non appare

- Verifica path del logo in `logo_url`
- Controlla che il file SVG esista
- Verifica z-index (deve essere 9999)

### Animazione non parte

- Controlla console per errori JavaScript
- Verifica che il modulo sia inizializzato
- Controlla che `data-initialized` non sia già presente

### Overlay non scompare

- Verifica parametro `duration`
- Controlla che JavaScript sia caricato
- Verifica console per errori

## 📊 Performance

- **First Paint**: Non blocca il rendering
- **JavaScript**: ~2KB minified
- **CSS**: ~1.5KB minified
- **Impatto**: Minimo, si rimuove automaticamente

## 🔄 Versioni

### 1.0.0 (Gennaio 2025)
- ✨ Release iniziale
- 🎨 Animazione fade-in/fade-out
- 💫 Lampeggio logo
- 📱 Design responsive
- ♿ Supporto accessibilità

## 📝 Note Tecniche

- Il modulo previene lo scroll durante la visualizzazione
- Si auto-rimuove dal DOM dopo l'animazione
- Non interferisce con altri moduli
- Compatibile con Page Builder

## 🎯 Best Practices

1. **Posizionamento**: Sempre come primo modulo
2. **Durata**: 2-3 secondi per UX ottimale
3. **Logo Size**: 200-300px per buona visibilità
4. **Testing**: Testa su connessioni lente

---

**Splash Logo Module** - Bologna Marathon System 🎬

*Versione 1.0.0 - Gennaio 2025*

