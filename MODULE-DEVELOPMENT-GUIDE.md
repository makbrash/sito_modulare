# 🧩 Guida Completa Sviluppo Moduli - Bologna Marathon

## 🎯 Obiettivo
Questa guida definisce le regole, convenzioni e best practices per creare moduli perfetti, funzionali e coerenti nel sistema modulare Bologna Marathon.

## 📋 Indice
1. [Struttura Cartella Modulo](#struttura-cartella-modulo)
2. [File Obbligatori](#file-obbligatori)
3. [Convenzioni Nomenclatura](#convenzioni-nomenclatura)
4. [Template PHP Standard](#template-php-standard)
5. [Manifest module.json](#manifest-modulejson)
6. [CSS e Stili](#css-e-stili)
7. [JavaScript](#javascript)
8. [Database Integration](#database-integration)
9. [Page Builder Integration](#page-builder-integration)
10. [Testing e Debug](#testing-e-debug)
11. [Best Practices](#best-practices)
12. [Esempi Pratici](#esempi-pratici)

---

## 📁 Struttura Cartella Modulo

### Struttura Obbligatoria
```
modules/mio-modulo/
├── mio-modulo.php          # Template PHP (OBBLIGATORIO)
├── mio-modulo.css          # Stili CSS (OBBLIGATORIO)
├── module.json             # Manifest modulo (OBBLIGATORIO)
├── mio-modulo.js           # JavaScript (OPZIONALE)
├── install.sql             # Setup database (OPZIONALE)
├── uninstall.sql           # Cleanup database (OPZIONALE)
└── README.md               # Documentazione (OPZIONALE)
```

### Regole Cartella
- **Nome cartella**: `kebab-case` (es: `mio-modulo`, `race-results`)
- **Path completo**: `modules/nome-modulo/`
- **File principali**: Stesso nome della cartella
- **Subfolder**: Evitare, mantenere tutto nella root del modulo

---

## 📄 File Obbligatori

### 1. Template PHP (`mio-modulo.php`)

```php
<?php
/**
 * Modulo: Nome Modulo
 * Descrizione: Breve descrizione del modulo
 * 
 * @var ModuleRenderer $renderer
 * @var array $config
 */

// Ottieni dati del modulo dal database
$moduleData = $renderer->getModuleData('mioModulo', $config);

// Valori di default con fallback
$title = $config['title'] ?? $moduleData['title'] ?? 'Titolo Default';
$content = $config['content'] ?? $moduleData['content'] ?? '';
$variant = $config['variant'] ?? 'primary';
$isActive = $config['is_active'] ?? true;

// Sanitizzazione output
$title = htmlspecialchars($title);
$content = $content; // Se HTML, già sanitizzato dal database
?>

<div class="mio-modulo mio-modulo--<?= htmlspecialchars($variant) ?>" 
     data-module="mio-modulo" 
     data-config='<?= htmlspecialchars(json_encode($config)) ?>'>
    
    <?php if (!empty($title)): ?>
        <h2 class="mio-modulo__title"><?= $title ?></h2>
    <?php endif; ?>
    
    <?php if (!empty($content)): ?>
        <div class="mio-modulo__content">
            <?= $content ?>
        </div>
    <?php endif; ?>
    
    <?php if ($isActive): ?>
        <div class="mio-modulo__actions">
            <button class="mio-modulo__button" data-action="toggle">
                <span class="mio-modulo__button-text">Toggle</span>
            </button>
        </div>
    <?php endif; ?>
</div>
```

### 2. Manifest (`module.json`)

```json
{
  "name": "mio-modulo",
  "slug": "mio-modulo",
  "aliases": ["mioModulo", "mio_modulo"],
  "version": "1.0.0",
  "description": "Descrizione completa del modulo",
  "category": "content",
  "author": "Bologna Marathon Team",
  "is_active": true,
  "component_path": "mio-modulo/mio-modulo.php",
  "default_config": {
    "title": "Titolo Default",
    "content": "Contenuto default",
    "variant": "primary",
    "is_active": true
  },
  "assets": {
    "css": ["mio-modulo/mio-modulo.css"],
    "js": ["mio-modulo/mio-modulo.js"],
    "vendors": []
  },
  "dependencies": [],
  "ui_schema": {
    "title": {
      "type": "text",
      "label": "Titolo",
      "placeholder": "Inserisci il titolo",
      "required": true,
      "default": "Titolo Default"
    },
    "content": {
      "type": "textarea",
      "label": "Contenuto",
      "placeholder": "Inserisci il contenuto",
      "required": true,
      "default": ""
    },
    "variant": {
      "type": "select",
      "label": "Variante",
      "options": [
        { "value": "primary", "label": "Primario" },
        { "value": "secondary", "label": "Secondario" },
        { "value": "accent", "label": "Accent" }
      ],
      "default": "primary"
    },
    "is_active": {
      "type": "boolean",
      "label": "Abilitato",
      "default": true
    }
  }
}
```

### 3. Stili CSS (`mio-modulo.css`)

```css
/**
 * Modulo: Nome Modulo
 * File: mio-modulo.css
 * 
 * IMPORTANTE: Usa SOLO CSS classico, MAI stili annidati (&)
 */

/* Block principale */
.mio-modulo {
  /* CSS Variables obbligatorie */
  color: var(--text-color);
  background: var(--color-white);
  border: 1px solid var(--border-color);
  
  /* Spacing consistente */
  padding: var(--spacing-md);
  margin: var(--spacing-sm) 0;
  
  /* Typography */
  font-family: var(--font-primary);
  font-size: var(--font-size-base);
  line-height: var(--line-height-base);
  
  /* Layout */
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-sm);
  transition: all 0.3s ease;
}

/* Elementi */
.mio-modulo__title {
  font-family: var(--font-display);
  font-size: 2rem;
  font-weight: 700;
  color: var(--primary);
  margin-bottom: var(--spacing-md);
  line-height: 1.2;
}

.mio-modulo__content {
  color: var(--text-color);
  line-height: var(--line-height-base);
  margin-bottom: var(--spacing-md);
}

.mio-modulo__button {
  background: var(--primary);
  color: var(--color-white);
  border: none;
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: all 0.3s ease;
  font-family: var(--font-primary);
  font-weight: 500;
}

.mio-modulo__button:hover {
  background: var(--primary-dark);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

/* Modificatori */
.mio-modulo--primary {
  border-left: 4px solid var(--primary);
}

.mio-modulo--secondary {
  border-left: 4px solid var(--secondary);
}

.mio-modulo--accent {
  border-left: 4px solid var(--accent);
}

/* Stati */
.mio-modulo.is-loading {
  opacity: 0.6;
  pointer-events: none;
}

.mio-modulo.is-error {
  border-color: var(--error);
  background: var(--error-light);
}

.mio-modulo.is-active {
  background: var(--success-light);
  border-color: var(--success);
}

/* Responsive - Mobile First */
@media (max-width: 768px) {
  .mio-modulo {
    padding: var(--spacing-sm);
    margin: var(--spacing-xs) 0;
  }
  
  .mio-modulo__title {
    font-size: 1.5rem;
  }
  
  .mio-modulo__button {
    width: 100%;
    padding: var(--spacing-md);
  }
}

@media (min-width: 1024px) {
  .mio-modulo {
    padding: var(--spacing-lg);
  }
  
  .mio-modulo__title {
    font-size: 2.5rem;
  }
}
```

### 4. JavaScript (`mio-modulo.js`)

```javascript
/**
 * Modulo: Nome Modulo
 * File: mio-modulo.js
 */

(function() {
  'use strict';

  // Classe principale del modulo
  class MioModulo {
    constructor(element) {
      this.element = element;
      this.config = this.parseConfig();
      this.isActive = false;
      this.init();
    }

    parseConfig() {
      // Parsing configurazione da data attributes
      const config = {};
      const data = this.element.dataset;
      
      if (data.config) {
        try {
          return JSON.parse(data.config);
        } catch (e) {
          console.warn('Configurazione modulo non valida:', e);
        }
      }
      
      return config;
    }

    init() {
      // Inizializzazione modulo
      this.bindEvents();
      this.loadData();
    }

    bindEvents() {
      // Event listeners con binding corretto
      this.element.addEventListener('click', this.handleClick.bind(this));
      
      // Eventi personalizzati
      this.element.addEventListener('mio-modulo:update', this.handleUpdate.bind(this));
      
      // Keyboard navigation
      this.element.addEventListener('keydown', this.handleKeydown.bind(this));
    }

    handleClick(event) {
      // Gestione click con delegation
      const target = event.target.closest('[data-action]');
      if (!target) return;

      event.preventDefault();
      event.stopPropagation();

      const action = target.dataset.action;
      this.executeAction(action, target);
    }

    handleKeydown(event) {
      // Gestione navigazione da tastiera
      if (event.key === 'Enter' || event.key === ' ') {
        const target = event.target.closest('[data-action]');
        if (target) {
          event.preventDefault();
          this.executeAction(target.dataset.action, target);
        }
      }
    }

    executeAction(action, target) {
      // Esecuzione azioni
      switch (action) {
        case 'toggle':
          this.toggle();
          break;
        case 'refresh':
          this.refresh();
          break;
        case 'close':
          this.close();
          break;
        default:
          console.warn('Azione sconosciuta:', action);
      }
    }

    loadData() {
      // Caricamento dati se necessario
      if (this.config.apiUrl) {
        this.fetchData(this.config.apiUrl);
      }
    }

    async fetchData(url) {
      try {
        this.setLoading(true);
        const response = await fetch(url);
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        this.renderData(data);
      } catch (error) {
        this.setError(error.message);
      } finally {
        this.setLoading(false);
      }
    }

    renderData(data) {
      // Rendering dati
      const content = this.element.querySelector('.mio-modulo__content');
      if (content && data.content) {
        content.innerHTML = data.content;
      }
    }

    setLoading(loading) {
      this.element.classList.toggle('is-loading', loading);
    }

    setError(message) {
      this.element.classList.add('is-error');
      const errorEl = this.element.querySelector('.mio-modulo__error');
      if (errorEl) {
        errorEl.textContent = message;
      }
    }

    toggle() {
      this.isActive = !this.isActive;
      this.element.classList.toggle('is-active', this.isActive);
      
      // Dispatch evento personalizzato
      this.element.dispatchEvent(new CustomEvent('mio-modulo:toggle', {
        detail: { isActive: this.isActive }
      }));
    }

    refresh() {
      this.loadData();
    }

    close() {
      this.isActive = false;
      this.element.classList.remove('is-active');
    }

    handleUpdate(event) {
      // Gestione aggiornamenti
      const { config } = event.detail;
      this.config = { ...this.config, ...config };
      this.render();
    }

    render() {
      // Re-rendering modulo
      // Implementare logica di rendering se necessario
    }

    destroy() {
      // Cleanup per evitare memory leaks
      this.element.removeEventListener('click', this.handleClick);
      this.element.removeEventListener('keydown', this.handleKeydown);
      this.element.removeEventListener('mio-modulo:update', this.handleUpdate);
    }
  }

  // Auto-inizializzazione
  document.addEventListener('DOMContentLoaded', function() {
    const modules = document.querySelectorAll('.mio-modulo');
    modules.forEach(element => {
      new MioModulo(element);
    });
  });

  // Export per uso esterno
  window.MioModulo = MioModulo;

})();
```

---

## 🏷️ Convenzioni Nomenclatura

### Cartelle e File
- **Cartella**: `kebab-case` (es: `mio-modulo`, `race-results`)
- **File PHP**: `kebab-case.php` (es: `mio-modulo.php`)
- **File CSS**: `kebab-case.css` (es: `mio-modulo.css`)
- **File JS**: `kebab-case.js` (es: `mio-modulo.js`)

### CSS Classes (BEM)
- **Block**: `.mio-modulo`
- **Element**: `.mio-modulo__title`, `.mio-modulo__content`
- **Modifier**: `.mio-modulo--primary`, `.mio-modulo__button--active`

### JavaScript
- **Classe**: `PascalCase` (es: `MioModulo`)
- **Metodi**: `camelCase` (es: `handleClick`, `executeAction`)
- **Variabili**: `camelCase` (es: `isActive`, `config`)

### PHP
- **Variabili**: `camelCase` (es: `$moduleData`, `$isActive`)
- **Metodi**: `camelCase` (es: `getModuleData`)

---

## 🎨 CSS e Stili

### Regole CSS Obbligatorie

#### 1. **MAI Stili Annidati**
```css
/* ✅ CORRETTO */
.mio-modulo {}
.mio-modulo:hover {}
.mio-modulo .mio-modulo__title {}
.mio-modulo.mio-modulo--primary {}

/* ❌ SBAGLIATO */
.mio-modulo {
  &:hover {}
  .mio-modulo__title {}
}
```

#### 2. **CSS Variables Obbligatorie**
```css
.mio-modulo {
  /* Colori */
  color: var(--text-color);
  background: var(--color-white);
  border-color: var(--border-color);
  
  /* Spacing */
  padding: var(--spacing-md);
  margin: var(--spacing-sm);
  
  /* Typography */
  font-family: var(--font-primary);
  font-size: var(--font-size-base);
  line-height: var(--line-height-base);
  
  /* Layout */
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-sm);
}
```

#### 3. **Responsive Mobile First**
```css
/* Mobile (base) */
.mio-modulo {}

/* Tablet */
@media (min-width: 768px) {
  .mio-modulo {}
}

/* Desktop */
@media (min-width: 1024px) {
  .mio-modulo {}
}
```

#### 4. **Stati e Modificatori**
```css
/* Stati */
.mio-modulo.is-loading {}
.mio-modulo.is-error {}
.mio-modulo.is-active {}

/* Modificatori */
.mio-modulo--primary {}
.mio-modulo--secondary {}
.mio-modulo--large {}
```

---

## 🔧 Tipi di Campi UI Schema

### Text
```json
{
  "title": {
    "type": "text",
    "label": "Titolo",
    "placeholder": "Inserisci il titolo",
    "required": true,
    "default": "Titolo Default"
  }
}
```

### Textarea
```json
{
  "content": {
    "type": "textarea",
    "label": "Contenuto",
    "placeholder": "Inserisci il contenuto",
    "rows": 4,
    "default": ""
  }
}
```

### Select
```json
{
  "variant": {
    "type": "select",
    "label": "Variante",
    "options": [
      { "value": "primary", "label": "Primario" },
      { "value": "secondary", "label": "Secondario" }
    ],
    "default": "primary"
  }
}
```

### Boolean
```json
{
  "is_active": {
    "type": "boolean",
    "label": "Abilitato",
    "default": true
  }
}
```

### Color
```json
{
  "color": {
    "type": "color",
    "label": "Colore",
    "default": "#23a8eb"
  }
}
```

### DateTime
```json
{
  "event_date": {
    "type": "datetime",
    "label": "Data Evento",
    "default": ""
  }
}
```

### Image
```json
{
  "image": {
    "type": "image",
    "label": "Immagine",
    "placeholder": "URL dell'immagine"
  }
}
```

### Array
```json
{
  "menu_items": {
    "type": "array",
    "label": "Voci Menu",
    "item_schema": {
      "label": {
        "type": "text",
        "label": "Etichetta",
        "required": true
      },
      "url": {
        "type": "url",
        "label": "URL",
        "required": true
      },
      "target": {
        "type": "select",
        "label": "Target",
        "options": [
          { "value": "_self", "label": "Stessa Finestra" },
          { "value": "_blank", "label": "Nuova Finestra" }
        ],
        "default": "_self"
      }
    }
  }
}
```

---

## 🗄️ Database Integration

### Nel ModuleRenderer.php

```php
/**
 * Ottiene dati per un modulo specifico
 */
public function getModuleData($moduleName, $config = []) {
    $resolved = $this->resolveModuleName($moduleName);
    switch ($resolved) {
        case 'mioModulo':
            return $this->getMioModuloData($config);
        default:
            return [];
    }
}

/**
 * Ottiene dati del mio modulo
 */
private function getMioModuloData($config) {
    $data = [];
    
    // Esempio: dati dal database
    if (!empty($config['data_id'])) {
        $stmt = $this->db->prepare("SELECT * FROM my_table WHERE id = ?");
        $stmt->execute([$config['data_id']]);
        $data = $stmt->fetch();
    }
    
    // Esempio: dati dinamici
    if (!empty($config['category'])) {
        $stmt = $this->db->prepare("SELECT * FROM content WHERE category = ? ORDER BY created_at DESC");
        $stmt->execute([$config['category']]);
        $data['items'] = $stmt->fetchAll();
    }
    
    // Merge con default
    return array_merge([
        'title' => 'Titolo Default',
        'content' => 'Contenuto Default',
        'items' => []
    ], $data);
}
```

### Prepared Statements Obbligatori
```php
// ✅ CORRETTO
$stmt = $this->db->prepare("SELECT * FROM table WHERE id = ? AND status = ?");
$stmt->execute([$id, $status]);
return $stmt->fetchAll();

// ❌ SBAGLIATO
$query = "SELECT * FROM table WHERE id = $id";
return $this->db->query($query)->fetchAll();
```

---

## 🎨 Page Builder Integration

### CSS Override per Builder
```css
/* Page Builder Overrides - Disabilita classi fixed */
.page-builder .mio-modulo,
.page-builder .mio-modulo [class*="fixed"] {
  position: static !important;
  top: auto !important;
  left: auto !important;
  right: auto !important;
  bottom: auto !important;
  z-index: auto !important;
  transform: none !important;
}
```

### Supporto Drag & Drop
```css
.mio-modulo {
  /* Assicura che il modulo sia draggable */
  cursor: move;
  transition: all 0.3s ease;
}

.mio-modulo:hover {
  border-color: var(--primary);
  box-shadow: 0 4px 12px rgba(0,123,255,0.15);
}
```

---

## 🧪 Testing e Debug

### Test Manuale
1. **Page Builder**: Testa drag & drop
2. **Configurazione**: Testa tutti i campi UI Schema
3. **Responsive**: Testa su diversi dispositivi
4. **Accessibilità**: Testa con screen reader
5. **JavaScript**: Testa interazioni e eventi

### Debug PHP
```php
// Nel template PHP
<?php
// Debug configurazione
error_log('Config modulo: ' . print_r($config, true));

// Debug dati
error_log('Dati modulo: ' . print_r($moduleData, true));
?>
```

### Debug JavaScript
```javascript
// Nel JavaScript
console.log('Config modulo:', this.config);
console.log('Elemento:', this.element);
console.log('Stato:', this.isActive);
```

### Test Database
```php
// Test connessione database
try {
    $stmt = $this->db->prepare("SELECT COUNT(*) FROM modules_registry WHERE name = ?");
    $stmt->execute(['mio-modulo']);
    $count = $stmt->fetchColumn();
    error_log("Modulo trovato: $count volte");
} catch (Exception $e) {
    error_log('Errore database: ' . $e->getMessage());
}
```

---

## 🚀 Best Practices

### Sicurezza
- **Sanitizzazione**: Usa `htmlspecialchars()` per output
- **Validazione**: Valida input nel template
- **SQL Injection**: Usa prepared statements
- **XSS**: Escape output HTML

### Performance
- **CSS**: Minifica e ottimizza
- **JS**: Usa event delegation
- **Database**: Usa indici appropriati
- **Images**: Ottimizza dimensioni

### Accessibilità
- **ARIA**: Aggiungi attributi appropriati
- **Keyboard**: Supporta navigazione da tastiera
- **Screen Reader**: Testa con screen reader
- **Contrast**: Verifica contrasti colori

### SEO
- **Semantic HTML**: Usa tag semantici
- **Meta Tags**: Aggiungi meta appropriati
- **Structured Data**: Implementa schema.org
- **Performance**: Ottimizza Core Web Vitals

---

## 📚 Esempi Pratici

### Modulo Semplice (Text)
Vedi `modules/text/` per esempio completo

### Modulo Complesso (Results)
Vedi `modules/results/` per esempio con database

### Modulo Interattivo (Menu)
Vedi `modules/menu/` per esempio con JavaScript

### Modulo con Array (Menu Items)
Vedi `modules/menu/` per esempio con array dinamico

---

## 🔍 Troubleshooting

### Problemi Comuni

#### Modulo non appare
- Verifica `modules_registry` nel database
- Controlla `is_active = 1`
- Verifica path del componente
- Controlla errori PHP

#### Stili non applicati
- Controlla `assets.css` nel manifest
- Verifica path CSS
- Controlla cache browser
- Verifica CSS Variables

#### JavaScript non funziona
- Controlla `assets.js` nel manifest
- Verifica errori console
- Testa in modalità development
- Verifica event listeners

#### Configurazione non salvata
- Verifica `ui_schema` nel manifest
- Controlla validazione campi
- Testa in page builder
- Verifica database `module_instances`

#### Page Builder non funziona
- Verifica CSS override per builder
- Controlla classi `fixed` disabilitate
- Testa drag & drop
- Verifica ordinamento moduli

---

## 🎯 Checklist Modulo Completo

### ✅ Struttura
- [ ] Cartella creata con nome `kebab-case`
- [ ] File PHP con template corretto
- [ ] File CSS con stili BEM
- [ ] File JS con classe modulare
- [ ] Manifest `module.json` completo
- [ ] README.md con documentazione

### ✅ Funzionalità
- [ ] Template PHP funzionante
- [ ] CSS responsive mobile-first
- [ ] JavaScript con event handling
- [ ] Database integration (se necessario)
- [ ] Page builder compatibility
- [ ] UI Schema completo

### ✅ Qualità
- [ ] Codice pulito e commentato
- [ ] Sicurezza (sanitizzazione, prepared statements)
- [ ] Performance ottimizzata
- [ ] Accessibilità (ARIA, keyboard)
- [ ] SEO friendly
- [ ] Cross-browser compatibility

### ✅ Testing
- [ ] Test in development
- [ ] Test in page builder
- [ ] Test responsive design
- [ ] Test accessibilità
- [ ] Test performance
- [ ] Test database (se applicabile)

---

## 📖 Riferimenti

- **Sistema Moduli**: `modules/README.md`
- **CSS Variables**: `assets/css/core/variables.css`
- **Page Builder**: `admin/page-builder.php`
- **ModuleRenderer**: `core/ModuleRenderer.php`
- **Database Schema**: `database/schema.sql`

---

**Sistema Moduli - Bologna Marathon** 🧩

*Versione 1.0.0 - Gennaio 2025*
