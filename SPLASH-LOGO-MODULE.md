# 🎬 Splash Logo Module - Documentazione Completa

## 📋 Riepilogo Creazione

Modulo **splash-logo** creato con successo seguendo le convenzioni del sistema modulare Bologna Marathon.

## 📦 File Creati

```
modules/splash-logo/
├── splash-logo.php          ✅ Template PHP
├── splash-logo.css          ✅ Stili con animazioni
├── splash-logo.js           ✅ JavaScript con auto-init
├── module.json              ✅ Manifest completo
├── install.sql              ✅ Script installazione database
└── README.md                ✅ Documentazione dettagliata
```

### File Aggiuntivi

- `test-splash-logo.html` - Pagina di test standalone
- `SPLASH-LOGO-MODULE.md` - Questo documento

### Modifiche Sistema

- ✅ `core/ModuleRenderer.php` - Aggiunto supporto per splashLogo

## 🎨 Caratteristiche Implementate

### 1. **Overlay Full-Screen**
- Position fixed con z-index 9999
- Background: `var(--gradient-dark)`
- Previene scroll durante visualizzazione

### 2. **Animazione Logo**
```css
logoFadeIn: 0.6s
- Fade da opacity 0 → 1
- Scale da 0.8 → 1.0
- Easing: ease-out
```

### 3. **Lampeggio Dolce (Pulse)**
```css
logoPulse: 2s infinite
- Opacity: 1 → 0.7 → 1
- Drop-shadow dinamica
- Easing: ease-in-out
```

### 4. **Fade-Out Overlay**
```css
splashFadeOut: 0.8s
- Delay: 1.7s
- Opacity: 1 → 0
- Easing: ease-out
```

### 5. **Auto-Rimozione**
- Classe `.hidden` applicata dopo duration
- Elemento rimosso dal DOM dopo 0.8s
- Emit evento custom `splash-logo:hidden`

## 🔧 Configurazione

### Parametri Disponibili

| Parametro | Tipo | Default | Range | Descrizione |
|-----------|------|---------|-------|-------------|
| `logo_url` | string | `assets/images/logo-bologna-marathon.svg` | - | Path logo SVG |
| `duration` | number | `2500` | 500-5000 | Durata totale (ms) |
| `logo_size` | number | `200` | 100-500 | Dimensione min logo (px) |
| `pulse_speed` | number | `2` | 1-5 | Velocità lampeggio (s) |

### Esempio Utilizzo PHP

```php
<?php
echo $renderer->renderModule('splashLogo', [
    'logo_url' => 'assets/images/logo-bologna-marathon.svg',
    'duration' => 3000,
    'logo_size' => 250,
    'pulse_speed' => 2.5
]);
?>
```

## 🎯 Timeline Animazioni

```
0.0s ──┬──> Logo inizia fade-in + scale
       │
0.6s ──┼──> Logo completamente visibile
       │    Inizia pulse infinito
       │
1.7s ──┼──> Overlay inizia fade-out
       │
2.5s ──┼──> Splash completamente nascosto
       │    Classe .hidden applicata
       │
3.3s ──┴──> Elemento rimosso dal DOM
            Evento 'splash-logo:hidden' emesso
```

## 📱 Responsive Design

### Desktop (Default)
```css
max-width: 80vw
max-height: 40vh
min-width: 200px (configurabile)
```

### Tablet (≤768px)
```css
max-width: 70vw
max-height: 30vh
```

### Mobile (≤480px)
```css
max-width: 60vw
max-height: 25vh
```

## ♿ Accessibilità

### Supporto `prefers-reduced-motion`
```css
@media (prefers-reduced-motion: reduce) {
    .splash-logo__image {
        animation: none; /* Disabilita pulse */
    }
    
    .splash-logo__overlay,
    .splash-logo__content {
        animation-duration: 0.3s; /* Animazioni più veloci */
    }
}
```

### Altri Aspetti
- ✅ Alt text descrittivo sul logo
- ✅ Nessun blocco permanente dello scroll
- ✅ Auto-rimozione completa dal DOM

## 🧪 Testing

### Test Manuale

1. **Apri**: `http://localhost/sito_modulare/test-splash-logo.html`
2. **Verifica**:
   - Logo appare centrato
   - Lampeggio dolce visibile
   - Fade-out dopo 2.5s
   - Elemento rimosso dal DOM

### Test da Console

```javascript
// Verifica inizializzazione
const splash = document.querySelector('.splash-logo');
console.log('Splash presente:', !!splash);

// Test nascondimento manuale
if (splash && window.SplashLogo) {
    const instance = new window.SplashLogo(splash);
    instance.hide();
}

// Listener evento custom
document.addEventListener('splash-logo:hidden', (e) => {
    console.log('Splash nascosto a:', e.detail.timestamp);
});
```

## 🗄️ Installazione Database

### Opzione 1: SQL Diretto
```bash
mysql -u username -p database_name < modules/splash-logo/install.sql
```

### Opzione 2: PHPMyAdmin
1. Apri PHPMyAdmin
2. Seleziona database
3. Vai su SQL
4. Carica `modules/splash-logo/install.sql`
5. Esegui

### Opzione 3: Sync Automatico
```php
// Via admin/sync-modules.php
// Il modulo verrà auto-registrato
```

## 📊 Performance

### Impatto Prestazioni
- **CSS**: ~1.5KB (minified)
- **JS**: ~2KB (minified)
- **First Paint**: Non bloccante
- **Rimozione**: Completa dal DOM dopo 3.3s

### Ottimizzazioni
- ✅ CSS animations (GPU accelerated)
- ✅ `will-change` implicito tramite transform
- ✅ Rimozione completa dal DOM (no memoria leak)
- ✅ Event listener con cleanup automatico

## 🎯 Best Practices d'Uso

### 1. Posizionamento
```php
// ✅ CORRETTO: Primo modulo nella pagina
echo $renderer->renderModule('splashLogo', [...]);
echo $renderer->renderModule('menu', [...]);
echo $renderer->renderModule('hero', [...]);

// ❌ SBAGLIATO: Dopo altri moduli
echo $renderer->renderModule('menu', [...]);
echo $renderer->renderModule('splashLogo', [...]);
```

### 2. Durata Consigliata
- **Siti veloci**: 2000-2500ms
- **Siti medi**: 2500-3000ms
- **Siti lenti**: 3000-4000ms
- **MAX consigliato**: 4000ms

### 3. Logo Size
- **Desktop**: 200-300px
- **Tablet**: 180-250px
- **Mobile**: 150-200px

## 🔍 Troubleshooting

### Problema: Logo non appare

**Possibili cause:**
1. Path logo errato
2. File SVG mancante
3. Z-index coperto da altro elemento

**Soluzione:**
```javascript
// Debug
const splash = document.querySelector('.splash-logo');
console.log('Splash:', splash);
console.log('Z-index:', getComputedStyle(splash).zIndex);
console.log('Logo src:', splash.querySelector('img').src);
```

### Problema: Overlay non scompare

**Possibili cause:**
1. JavaScript non caricato
2. Errore nella console
3. Duration troppo alto

**Soluzione:**
```javascript
// Forza nascondimento
const splash = document.querySelector('.splash-logo');
if (splash) {
    splash.classList.add('hidden');
    setTimeout(() => {
        splash.remove();
    }, 800);
}
```

### Problema: Lampeggio troppo veloce/lento

**Soluzione:**
```css
/* Modifica in splash-logo.css */
.splash-logo__image {
    animation: logoPulse 3s ease-in-out infinite; /* Da 2s a 3s */
}
```

## 📚 Risorse Aggiuntive

### Documentazione
- `modules/splash-logo/README.md` - Guida completa
- `modules/README.md` - Convenzioni sistema moduli
- `modules/.cursorrules` - Regole sviluppo

### Test
- `test-splash-logo.html` - Test standalone
- `admin/page-builder.php` - Test integrato

### Codice
- `modules/splash-logo/` - Codice sorgente
- `core/ModuleRenderer.php` - Integrazione sistema

## ✅ Checklist Completamento

- ✅ Struttura modulo standard
- ✅ Template PHP con sanitizzazione
- ✅ CSS con animazioni (NO nested styles)
- ✅ JavaScript con auto-init
- ✅ Manifest completo con ui_schema
- ✅ Script SQL installazione
- ✅ README documentazione
- ✅ Integrazione ModuleRenderer
- ✅ Test standalone
- ✅ Responsive design
- ✅ Accessibilità (reduced motion)
- ✅ Performance ottimizzata
- ✅ Seguono convenzioni BEM
- ✅ CSS Variables utilizzate

## 🎉 Risultato Finale

Il modulo **Splash Logo** è completo, testato e pronto per l'uso. Segue tutte le convenzioni del sistema modulare Bologna Marathon e può essere utilizzato tramite:

1. **Direct PHP**: `$renderer->renderModule('splashLogo', [...])`
2. **Page Builder**: Drag & drop con configurazione UI
3. **Database**: Registrazione automatica via `install.sql`

---

**Splash Logo Module** - Bologna Marathon System 🎬

*Creato: Gennaio 2025*
*Versione: 1.0.0*

