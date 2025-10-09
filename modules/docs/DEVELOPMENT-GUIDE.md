# 🧩 Guida Sviluppo Moduli - Bologna Marathon

## 🎯 Panoramica
Questa guida definisce le regole, convenzioni e best practices per creare moduli perfetti, funzionali e coerenti nel sistema modulare Bologna Marathon.

## 📁 Struttura Modulo

### File Obbligatori
```
modules/mio-modulo/
├── mio-modulo.php          # Template PHP (OBBLIGATORIO)
├── mio-modulo.css          # Stili CSS (OBBLIGATORIO)
├── module.json             # Manifest modulo (OBBLIGATORIO)
├── mio-modulo.js           # JavaScript (OPZIONALE)
├── install.sql             # Setup database (OPZIONALE)
└── README.md               # Documentazione (OPZIONALE)
```

### Regole Nomenclatura
- **Cartella**: `kebab-case` (es: `mio-modulo`)
- **File**: `kebab-case` (es: `mio-modulo.php`)
- **Classe CSS**: BEM (es: `.mio-modulo__element`)
- **Variabile PHP**: `camelCase` (es: `$moduleData`)

## 📄 Template PHP Standard

```php
<?php
/**
 * Modulo: Nome Modulo
 * Descrizione: Breve descrizione del modulo
 * 
 * @var ModuleRenderer $renderer
 * @var array $config
 */

// Ottieni dati del modulo
$moduleData = $renderer->getModuleData('mioModulo', $config);

// Valori di default con fallback
$title = $config['title'] ?? $moduleData['title'] ?? 'Titolo Default';
$content = $config['content'] ?? $moduleData['content'] ?? '';
$variant = $config['variant'] ?? 'primary';

// Sanitizzazione output
$title = htmlspecialchars($title);
?>
<div class="mio-modulo mio-modulo--<?= htmlspecialchars($variant) ?>">
    <?php if (!empty($title)): ?>
        <h2 class="mio-modulo__title"><?= $title ?></h2>
    <?php endif; ?>
    
    <?php if (!empty($content)): ?>
        <div class="mio-modulo__content">
            <?= $content ?>
        </div>
    <?php endif; ?>
</div>
```

## 📋 Manifest module.json

```json
{
  "name": "mio-modulo",
  "slug": "mio-modulo",
  "aliases": ["mioModulo", "mio_modulo"],
  "version": "1.0.0",
  "description": "Descrizione del modulo",
  "category": "content",
  "author": "Bologna Marathon Team",
  "is_active": true,
  "component_path": "mio-modulo/mio-modulo.php",
  "default_config": {
    "title": "Titolo Default",
    "content": "Contenuto default",
    "variant": "primary"
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
    }
  }
}
```

## 🎨 CSS - Regole Obbligatorie

### 1. **MAI Stili Annidati**
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

### 2. **CSS Variables Obbligatorie**
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

### 3. **BEM Methodology**
```css
/* Block */
.mio-modulo {}

/* Element */
.mio-modulo__title {}
.mio-modulo__content {}

/* Modifier */
.mio-modulo--primary {}
.mio-modulo__button--active {}
```

### 4. **Responsive Mobile First**
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

## 🔧 JavaScript - Pattern Standard

```javascript
/**
 * Modulo: Nome Modulo
 * File: mio-modulo.js
 */

(function() {
  'use strict';

  class MioModulo {
    constructor(element) {
      this.element = element;
      this.config = this.parseConfig();
      this.init();
    }

    parseConfig() {
      const data = this.element.dataset;
      if (data.config) {
        try {
          return JSON.parse(data.config);
        } catch (e) {
          console.warn('Configurazione modulo non valida:', e);
        }
      }
      return {};
    }

    init() {
      this.bindEvents();
      this.loadData();
    }

    bindEvents() {
      this.element.addEventListener('click', this.handleClick.bind(this));
      this.element.addEventListener('mio-modulo:update', this.handleUpdate.bind(this));
    }

    handleClick(event) {
      const target = event.target.closest('[data-action]');
      if (!target) return;

      const action = target.dataset.action;
      this.executeAction(action, target);
    }

    executeAction(action, target) {
      switch (action) {
        case 'toggle':
          this.toggle();
          break;
        case 'refresh':
          this.refresh();
          break;
        default:
          console.warn('Azione sconosciuta:', action);
      }
    }

    toggle() {
      this.element.classList.toggle('is-active');
    }

    refresh() {
      this.loadData();
    }

    loadData() {
      // Implementare caricamento dati se necessario
    }

    destroy() {
      this.element.removeEventListener('click', this.handleClick);
      this.element.removeEventListener('mio-modulo:update', this.handleUpdate);
    }
  }

  // Auto-inizializzazione
  document.addEventListener('DOMContentLoaded', function() {
    const modules = document.querySelectorAll('.mio-modulo');
    modules.forEach(element => new MioModulo(element));
  });

  window.MioModulo = MioModulo;
})();
```

## 🔧 Tipi Campi UI Schema

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
  "enabled": {
    "type": "boolean",
    "label": "Abilitato",
    "default": true
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
      }
    }
  }
}
```

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
    
    // Merge con default
    return array_merge([
        'title' => 'Titolo Default',
        'content' => 'Contenuto Default'
    ], $data);
}
```

### Prepared Statements Obbligatori
```php
// ✅ CORRETTO
$stmt = $this->db->prepare("SELECT * FROM table WHERE id = ?");
$stmt->execute([$id]);
return $stmt->fetch();

// ❌ SBAGLIATO
$query = "SELECT * FROM table WHERE id = $id";
return $this->db->query($query)->fetch();
```

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
  cursor: move;
  transition: all 0.3s ease;
}

.mio-modulo:hover {
  border-color: var(--primary);
  box-shadow: 0 4px 12px rgba(0,123,255,0.15);
}
```

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
error_log('Config modulo: ' . print_r($config, true));
error_log('Dati modulo: ' . print_r($moduleData, true));
?>
```

### Debug JavaScript
```javascript
// Nel JavaScript
console.log('Config modulo:', this.config);
console.log('Elemento:', this.element);
```

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

## 🔍 Troubleshooting

### Problemi Comuni

#### Modulo non appare
- Verifica `modules_registry` nel database
- Controlla `is_active = 1`
- Verifica path del componente

#### Stili non applicati
- Controlla `assets.css` nel manifest
- Verifica path CSS
- Controlla cache browser

#### JavaScript non funziona
- Controlla `assets.js` nel manifest
- Verifica errori console
- Testa in modalità development

#### Configurazione non salvata
- Verifica `ui_schema` nel manifest
- Controlla validazione campi
- Testa in page builder

## 📚 Esempi Pratici

### Modulo Semplice (Text)
Vedi `modules/text/` per esempio completo

### Modulo Complesso (Results)
Vedi `modules/results/` per esempio con database

### Modulo Interattivo (Menu)
Vedi `modules/menu/` per esempio con JavaScript

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

---

**Guida Sviluppo Moduli - Sistema Modulare Bologna Marathon** 🧩

*Versione 1.0.0 - Gennaio 2025*
