# 🎯 Standard di Codifica - Bologna Marathon

## 📋 Panoramica
Questo documento definisce gli standard di codifica obbligatori per il sistema modulare Bologna Marathon. **Tutti i contributi devono rispettare queste regole**.

## 🚨 REGOLE FONDAMENTALI

### ❌ ASSOLUTAMENTE VIETATO

#### 1. NO HARDCODING
```php
// ❌ SBAGLIATO
<div style="color: #23a8eb">
$apiUrl = "https://example.com/api";
$timeout = 5000;

// ✅ CORRETTO
<div class="hero" style="color: var(--primary)">
$apiUrl = config('api.url');
$timeout = config('api.timeout');
```

#### 2. NO CODICE SPAGHETTI
```php
// ❌ SBAGLIATO - Tutto in un file
<div>
    <style>.hero { color: red; }</style>
    <script>function doStuff() { alert('hi'); }</script>
    <?php $db = new PDO(...); ?>
</div>

// ✅ CORRETTO - Separazione delle responsabilità
hero.php  → Solo template HTML + variabili
hero.css  → Tutti gli stili
hero.js   → Tutta la logica JavaScript
```

#### 3. NO CSS/JS INLINE
```html
<!-- ❌ SBAGLIATO -->
<div style="padding: 20px; color: red;">
<button onclick="alert('click')">Click</button>

<!-- ✅ CORRETTO -->
<div class="hero hero--primary">
<button class="hero__button" data-action="cta">Click</button>
```

## ✅ STANDARD OBBLIGATORI

### 📄 PHP Template
**Obiettivo**: Template puliti, solo HTML + variabili PHP

```php
<?php
/**
 * Modulo: Hero
 * @var ModuleRenderer $renderer
 * @var array $config
 */

// Ottieni dati dal renderer (NO query dirette)
$moduleData = $renderer->getModuleData('hero', $config);

// Valori con fallback
$title = $config['title'] ?? $moduleData['title'] ?? 'Default';
$subtitle = $config['subtitle'] ?? '';

// Sanitizzazione output
$title = htmlspecialchars($title);
?>

<div class="hero hero--<?= htmlspecialchars($variant) ?>">
    <?php if (!empty($title)): ?>
        <h1 class="hero__title"><?= $title ?></h1>
    <?php endif; ?>
    
    <?php if (!empty($subtitle)): ?>
        <p class="hero__subtitle"><?= htmlspecialchars($subtitle) ?></p>
    <?php endif; ?>
</div>
```

**Regole Template:**
- ✅ Solo HTML + variabili PHP
- ✅ Nessun CSS inline
- ✅ Nessun JavaScript inline
- ✅ Nessuna query database
- ✅ Sempre `htmlspecialchars()` per output
- ✅ Sempre fallback per valori opzionali

### 🎨 CSS
**Obiettivo**: File separati, CSS Variables, BEM

```css
/**
 * Modulo: Hero
 * File: hero.css
 */

/* ✅ CSS Variables obbligatorie */
.hero {
    color: var(--text-color);
    background: var(--hero-bg);
    padding: var(--spacing-lg);
    font-family: var(--font-primary);
}

/* ✅ BEM Methodology */
.hero__title {
    font-size: 2.5rem;
    color: var(--primary);
}

.hero__subtitle {
    font-size: 1.2rem;
    color: var(--text-secondary);
}

/* ✅ Modificatori */
.hero--primary {
    background: var(--primary-gradient);
}

.hero--dark {
    color: var(--color-white);
    background: var(--color-dark);
}

/* ✅ Stati */
.hero.is-loading {
    opacity: 0.6;
    pointer-events: none;
}

/* ✅ Responsive Mobile-First */
@media (min-width: 768px) {
    .hero {
        padding: var(--spacing-xl);
    }
}

/* ❌ MAI stili annidati */
/* .hero {
    &:hover {}          // NO
    .hero__title {}     // NO
} */

/* ✅ SEMPRE stili espliciti */
.hero:hover {}          // SI
.hero .hero__title {}   // SI
```

**Regole CSS:**
- ✅ File separato per ogni modulo
- ✅ CSS Variables per colori, spacing, font
- ✅ BEM Methodology per classi
- ✅ NO stili annidati (`&`)
- ✅ Mobile-first responsive
- ✅ Stati con classi (`.is-active`, `.is-loading`)

### 🔧 JavaScript
**Obiettivo**: Classe modulare, event delegation, cleanup

```javascript
/**
 * Modulo: Hero
 * File: hero.js
 */

(function() {
  'use strict';

  class Hero {
    constructor(element) {
      this.element = element;
      this.config = this.parseConfig();
      this.state = {
        isActive: false,
        isLoading: false
      };
      this.init();
    }

    parseConfig() {
      const data = this.element.dataset;
      if (data.config) {
        try {
          return JSON.parse(data.config);
        } catch (e) {
          console.warn('Configurazione non valida:', e);
        }
      }
      return {};
    }

    init() {
      this.bindEvents();
      this.loadData();
    }

    bindEvents() {
      // Event delegation per performance
      this.element.addEventListener('click', this.handleClick.bind(this));
      
      // Eventi custom
      this.element.addEventListener('hero:update', this.handleUpdate.bind(this));
    }

    handleClick(event) {
      const target = event.target.closest('[data-action]');
      if (!target) return;

      event.preventDefault();
      const action = target.dataset.action;
      this.executeAction(action, target);
    }

    executeAction(action, target) {
      switch (action) {
        case 'cta':
          this.handleCTA();
          break;
        case 'toggle':
          this.toggle();
          break;
        default:
          console.warn('Azione sconosciuta:', action);
      }
    }

    async loadData() {
      if (!this.config.apiUrl) return;

      try {
        this.setLoading(true);
        const response = await fetch(this.config.apiUrl);
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        this.renderData(data);
      } catch (error) {
        console.error('Errore caricamento:', error);
        this.setError(error.message);
      } finally {
        this.setLoading(false);
      }
    }

    setLoading(loading) {
      this.state.isLoading = loading;
      this.element.classList.toggle('is-loading', loading);
    }

    setError(message) {
      this.element.classList.add('is-error');
      console.error('Hero error:', message);
    }

    toggle() {
      this.state.isActive = !this.state.isActive;
      this.element.classList.toggle('is-active', this.state.isActive);
      
      // Emit evento custom
      this.element.dispatchEvent(new CustomEvent('hero:toggle', {
        detail: { isActive: this.state.isActive }
      }));
    }

    destroy() {
      // Cleanup per evitare memory leak
      this.element.removeEventListener('click', this.handleClick);
      this.element.removeEventListener('hero:update', this.handleUpdate);
    }
  }

  // Auto-inizializzazione
  document.addEventListener('DOMContentLoaded', function() {
    const heroes = document.querySelectorAll('.hero');
    heroes.forEach(element => new Hero(element));
  });

  // Export per uso esterno
  window.Hero = Hero;

})();
```

**Regole JavaScript:**
- ✅ File separato per ogni modulo
- ✅ Classe modulare con pattern OOP
- ✅ Event delegation per performance
- ✅ Auto-inizializzazione con DOMContentLoaded
- ✅ Metodo `destroy()` per cleanup
- ✅ Try-catch per gestione errori
- ✅ Async/await per chiamate API
- ✅ Eventi custom per comunicazione

### 🗄️ Database
**Obiettivo**: Prepared statements, logica nel core

```php
// ❌ SBAGLIATO - Query nel template
<?php
$db = new PDO(...);
$results = $db->query("SELECT * FROM users WHERE id = " . $_GET['id']);
?>

// ✅ CORRETTO - Logica nel ModuleRenderer
// In ModuleRenderer.php
private function getHeroData($config) {
    // Prepared statement obbligatorio
    $stmt = $this->db->prepare("
        SELECT * FROM heroes 
        WHERE id = ? AND status = ?
    ");
    $stmt->execute([$config['hero_id'], 'published']);
    
    $data = $stmt->fetch();
    
    // Merge con default
    return array_merge([
        'title' => 'Default Title',
        'subtitle' => '',
        'image' => 'default.jpg'
    ], $data ?: []);
}

// Nel template
$moduleData = $renderer->getModuleData('hero', $config);
```

**Regole Database:**
- ✅ **SEMPRE** prepared statements
- ✅ **MAI** query dirette in template
- ✅ Logica nel `ModuleRenderer.php`
- ✅ Validazione parametri
- ✅ Gestione errori con try-catch
- ✅ Merge con valori default

## 📚 GESTIONE DOCUMENTAZIONE

### Regole Obbligatorie

#### 1. Organizzazione File
```
✅ STRUTTURA CORRETTA:
docs/                    → Sistema generale
admin/docs/              → Admin specifico
modules/docs/            → Moduli
database/docs/           → Database

❌ STRUTTURA SBAGLIATA:
FIX-1.md, FIX-2.md, FIX-final.md sparsi in root
```

#### 2. Eliminazione File Obsoleti
- ✅ Elimina `FIX-*.md` dopo consolidamento
- ✅ Elimina `QUICK-*.md` dopo risoluzione
- ✅ Mantieni MAX 1 file per argomento
- ❌ MAI accumulare file `*-v2.md`, `*-new.md`, `*-final.md`

#### 3. Modifica vs Creazione
```
✅ WORKFLOW CORRETTO:
1. Cerco file esistente simile
2. Se esiste → AGGIORNO file esistente
3. Se non esiste → CREO nuovo file
4. Aggiorno riferimenti in README.md e .cursorrules

❌ WORKFLOW SBAGLIATO:
1. Creo FILE-new.md
2. Poi creo FILE-v2.md
3. Poi creo FILE-final.md
4. Non aggiorno riferimenti
```

#### 4. Aggiornamento Riferimenti
Quando sposti/rinomini file:
- ✅ Aggiorna `README.md`
- ✅ Aggiorna `.cursorrules`
- ✅ Aggiorna link interni nei `.md`
- ✅ Verifica che tutti i link funzionino

## ⚠️ CHECKLIST PRE-COMMIT

### 🚨 Codice Pulito
- [ ] ❌ Nessun CSS inline in HTML/PHP
- [ ] ❌ Nessun JavaScript inline in HTML/PHP
- [ ] ❌ Nessun valore hardcoded
- [ ] ❌ Nessuna query SQL diretta in template
- [ ] ✅ Separazione: `.php` + `.css` + `.js`
- [ ] ✅ CSS Variables per colori/spacing
- [ ] ✅ Prepared statements per database
- [ ] ✅ Sanitizzazione output

### 📚 Documentazione
- [ ] ❌ Nessun file `.md` duplicato
- [ ] ❌ Nessun file obsoleto
- [ ] ✅ File organizzati per categoria
- [ ] ✅ Riferimenti aggiornati
- [ ] ✅ Link interni funzionanti

### 🧹 Pulizia
- [ ] ✅ File temporanei eliminati
- [ ] ✅ Commenti debug rimossi
- [ ] ✅ Console.log() rimossi
- [ ] ✅ Codice formattato

## 📖 Riferimenti

### Guide Correlate
- `.cursorrules` - Regole complete sistema
- `modules/docs/DEVELOPMENT-GUIDE.md` - Sviluppo moduli
- `admin/docs/PAGE-BUILDER.md` - Page Builder
- `database/docs/SCHEMA-REFERENCE.md` - Database

### Tool e Setup
- `admin/test-setup.php` - Setup database
- `admin/sync-modules.php` - Registrazione moduli
- `gulpfile.js` - Build system

---

**Standard di Codifica - Sistema Modulare Bologna Marathon** 🎯

*Versione 1.0.0 - Gennaio 2025*

**Regola d'oro**: Codice pulito, modulare e manutenibile. No hardcoding, no spaghetti code, sempre separazione delle responsabilità.
