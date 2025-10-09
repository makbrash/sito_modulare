# 🕐 Modulo Countdown - Bologna Marathon

Modulo countdown elegante e moderno per mostrare il conto alla rovescia verso un evento. Disponibile in due varianti: banner full-width e card sovrapposta.

## 📋 Caratteristiche

- ✅ **Due Varianti**: Banner full-width e Card sovrapposta
- ✅ **Countdown Centralizzato**: Utilizza il sistema countdown unificato in `app.js`
- ✅ **Design Elegante**: Effetti glow e animazioni fluide
- ✅ **Temi Dinamici**: Supporto per tutti i temi colore delle gare
- ✅ **Loghi Personalizzabili**: Possibilità di mostrare due loghi laterali
- ✅ **Responsive**: Ottimizzato per tutti i dispositivi
- ✅ **Accessibile**: Conforme alle linee guida WCAG

## 🎨 Varianti

### 1. Banner Full-Width (`variant: "banner"`)
Fascia a tutta larghezza con loghi laterali. Ideale per sezioni di transizione tra blocchi.

```php
$config = [
    'variant' => 'banner',
    'target_date' => '2026-03-01T09:00:00',
    'subtitle' => 'Mancano solo',
    'show_logos' => true,
    'logo_1' => 'assets/images/logo-1.svg',
    'logo_2' => 'assets/images/logo-2.svg'
];
```

### 2. Card Sovrapposta (`variant: "card"`)
Card elegante che si sovrappone all'intersezione tra due blocchi. Perfetta per creare effetto di profondità.

```php
$config = [
    'variant' => 'card',
    'target_date' => '2026-03-01T09:00:00',
    'title' => 'Prossimo Evento',
    'subtitle' => 'Inizia tra',
    'theme_class' => 'theme-marathon'
];
```

## 🔧 Configurazione

### Parametri Disponibili

| Parametro | Tipo | Default | Descrizione |
|-----------|------|---------|-------------|
| `variant` | string | `"banner"` | Layout: `"banner"` o `"card"` |
| `target_date` | string | `"2026-03-01T09:00:00"` | Data target in formato ISO |
| `title` | string | `""` | Titolo sopra il countdown (opzionale) |
| `subtitle` | string | `"Mancano solo"` | Sottotitolo sopra il countdown |
| `show_logos` | boolean | `true` | Mostra loghi laterali (solo banner) |
| `logo_1` | string | `"assets/images/logo-bologna-marathon.svg"` | Logo sinistro |
| `logo_2` | string | `"assets/images/logo-bologna-marathon.svg"` | Logo destro |
| `theme_class` | string | `""` | Tema colore: `theme-marathon`, `theme-portici`, ecc. |

### Esempio Completo

```php
<?php
$countdownConfig = [
    'variant' => 'card',
    'target_date' => '2026-03-01T09:00:00',
    'title' => 'TERMAL BOLOGNA MARATHON 2026',
    'subtitle' => 'La maratona più bella inizia tra',
    'theme_class' => 'theme-marathon',
    'show_logos' => true,
    'logo_1' => 'assets/images/sponsor/title-sponsor.png',
    'logo_2' => 'assets/images/logo-bologna-marathon.svg'
];

// Render del modulo
$renderer->renderModule('countdown', $countdownConfig);
?>
```

## 🎨 Temi Colore

Il modulo supporta i seguenti temi dinamici:

- `theme-marathon` - Azzurro (#23a8eb)
- `theme-portici` - Rosa (#dc335e)
- `theme-5k` - Giallo (#e7ad45)
- `theme-kidsrun` - Turchese (#009bac)
- `theme-run-tune-up` - Verde (#cbdf44)

## 📱 Responsive Design

Il modulo è completamente responsive con breakpoint ottimizzati:

- **Desktop (>992px)**: Layout completo con tutti gli elementi
- **Tablet (768px-992px)**: Dimensioni ridotte, layout ottimizzato
- **Mobile (480px-768px)**: Layout a colonna, elementi compatti
- **Mobile Small (<480px)**: Timer wrap, dimensioni minime

## 🔄 Sistema Countdown Unificato

Il modulo utilizza un sistema countdown intelligente con fallback automatico:

### Come Funziona

1. **Doppio Sistema di Inizializzazione**:
   - ✅ **Sistema Centralizzato**: Usa `app.js` se disponibile (preferito)
   - ✅ **Fallback Locale**: `countdown.js` inizializza immediatamente se `app.js` non è ancora caricato
   - ✅ **Upgrade Automatico**: Passa al sistema centralizzato quando `app.js` diventa disponibile

2. **Attributi Data**: Il countdown si basa su attributi `data-countdown`
```html
<div data-countdown="2026-03-01T09:00:00" data-countdown-format="full">
```

3. **Auto-inizializzazione Intelligente**:
```javascript
// countdown.js si inizializza subito (fallback)
// Poi passa a app.js quando disponibile
window.bolognaMarathon.setupCountdowns();
```

4. **Formati Supportati**:
   - `full` - Formato completo con giorni, ore, minuti, secondi
   - `compact` - Formato compatto (usato nel menu)

### Perché Due Sistemi?

Il modulo viene renderizzato nel HTML **prima** che `app.js` sia caricato. Il sistema a doppio fallback garantisce:
- ✅ Countdown visibile immediatamente (no flash di contenuto)
- ✅ Compatibilità con caricamento asincrono degli script
- ✅ Upgrade automatico al sistema centralizzato quando disponibile
- ✅ Funziona sia in sviluppo che produzione

## 💻 Struttura HTML Generata

```html
<div class="countdown-module countdown-module--card theme-marathon">
    <div class="countdown-container">
        <div class="countdown-content">
            <h3 class="countdown-title">Titolo Evento</h3>
            <p class="countdown-subtitle">Mancano solo</p>
            <div class="countdown-timer" data-countdown="..." data-countdown-format="full">
                <div class="countdown-item">
                    <div class="countdown-number">143</div>
                    <div class="countdown-label">Gior.</div>
                </div>
                <!-- Altri item -->
            </div>
        </div>
    </div>
</div>
```

## 🎯 Casi d'Uso

### 1. Banner tra Hero e Contenuto
```php
// index.php
$renderer->renderModule('hero', $heroConfig);
$renderer->renderModule('countdown', [
    'variant' => 'banner',
    'target_date' => '2026-03-01T09:00:00'
]);
$renderer->renderModule('presentation', $presentationConfig);
```

### 2. Card Sovrapposta
```php
// index.php
$renderer->renderModule('hero', $heroConfig);
$renderer->renderModule('countdown', [
    'variant' => 'card',
    'target_date' => '2026-03-01T09:00:00',
    'title' => 'PROSSIMA GARA'
]);
$renderer->renderModule('race-cards', $raceCardsConfig);
```

### 3. Countdown Tematizzato per Gara Specifica
```php
$renderer->renderModule('countdown', [
    'variant' => 'banner',
    'target_date' => '2026-03-01T09:00:00',
    'title' => '30KM DEI PORTICI',
    'theme_class' => 'theme-portici',
    'logo_1' => 'assets/images/sponsor/main-sponsor.png',
    'logo_2' => 'assets/images/logo-portici.svg'
]);
```

## 🎨 Personalizzazione CSS

### Override Colori
```css
.countdown-module.custom-theme .countdown-number {
    color: #your-color;
    text-shadow: 0 0 20px rgba(your-rgb, 0.3);
}
```

### Dimensioni Custom
```css
.countdown-module--banner.large {
    padding: var(--space-2xl) var(--container-padding);
}

.countdown-module--banner.large .countdown-number {
    font-size: 5rem;
}
```

## 🚀 Installazione

### 1. Database
```bash
# Registra il modulo nel database
mysql -u user -p database < modules/countdown/install.sql
```

### 2. Sincronizzazione
```php
// Oppure usa il sync automatico
http://localhost/sito_modulare/admin/sync-modules.php
```

### 3. Utilizzo
```php
// Nel tuo template
$renderer->renderModule('countdown', [
    'variant' => 'card',
    'target_date' => '2026-03-01T09:00:00'
]);
```

## 🐛 Troubleshooting

### Countdown Non Si Aggiorna
**Problema**: I numeri del countdown non cambiano

**Soluzione**:
```javascript
// 1. Verifica che countdown.js sia caricato
console.log('countdown.js caricato');

// 2. Controlla gli attributi data
const countdown = document.querySelector('[data-countdown]');
console.log(countdown.getAttribute('data-countdown'));

// 3. Verifica errori console
// Apri Developer Tools > Console
```

**Causa Comune**: Formato data non valido
```html
<!-- ✅ CORRETTO -->
<div data-countdown="2026-03-01T09:00:00">

<!-- ❌ SBAGLIATO -->
<div data-countdown="01/03/2026 09:00">
```

### Countdown Mostra "--" invece dei numeri
**Causa**: `countdown.js` non è incluso nella pagina

**Soluzione**: Verifica `module.json`
```json
{
  "assets": {
    "js": ["countdown/countdown.js"]  // ✅ Deve essere presente
  }
}
```

### Loghi Non Appaiono
- Controlla il path delle immagini
- Verifica che `show_logos` sia `true`
- Valido solo per variante `banner`

### Stili Non Applicati
- Verifica che `countdown.css` sia incluso
- Controlla l'ordine di caricamento CSS
- Pulisci cache del browser

### Flash di Contenuto
Se vedi "--" per un istante prima dei numeri reali, è normale. Il JavaScript si sta inizializzando. Per evitarlo:

```css
/* Aggiungi al tuo CSS */
.countdown-number {
    min-width: 60px; /* Previene layout shift */
}
```

## 📚 Riferimenti

- **Sistema Countdown**: `assets/js/core/app.js`
- **CSS Variables**: `assets/css/core/variables.css`
- **Temi Colore**: `assets/css/core/colors.css`
- **Layout System**: `assets/css/core/layout.css`

## 📝 Changelog

### v1.0.0 (Gennaio 2025)
- ✨ Release iniziale
- ✨ Due varianti: banner e card
- ✨ Sistema countdown unificato
- ✨ Supporto temi dinamici
- ✨ Design responsive completo

---

**Modulo Countdown - Bologna Marathon** 🕐

*Versione 1.0.0 - Gennaio 2025*

