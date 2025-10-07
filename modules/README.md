# 🧩 Sistema Moduli - Bologna Marathon

Guida completa per lo sviluppo, modifica e gestione dei moduli del sistema modulare.

## 📋 Panoramica

Il sistema moduli permette di creare componenti riutilizzabili che possono essere:
- **Drag & Drop**: Posizionati tramite page builder
- **Configurabili**: Personalizzati per ogni istanza
- **Responsive**: Ottimizzati per tutti i dispositivi
- **Accessibili**: Conforme alle linee guida WCAG

## 🏗️ Struttura Modulo

Ogni modulo deve seguire questa struttura:

```
modules/mio-modulo/
├── mio-modulo.php      # Template PHP
├── mio-modulo.css      # Stili del modulo
├── mio-modulo.js       # JavaScript (opzionale)
├── module.json         # Manifest del modulo
├── install.sql         # Setup database (opzionale)
└── README.md          # Documentazione (opzionale)
```

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

// Ottieni dati del modulo
$moduleData = $renderer->getModuleData('mioModulo', $config);

// Valori di default
$title = $config['title'] ?? $moduleData['title'] ?? 'Titolo Default';
$content = $config['content'] ?? $moduleData['content'] ?? '';
$variant = $config['variant'] ?? 'primary';
?>

<div class="mio-modulo mio-modulo--<?= htmlspecialchars($variant) ?>">
    <?php if (!empty($title)): ?>
        <h2 class="mio-modulo__title"><?= htmlspecialchars($title) ?></h2>
    <?php endif; ?>
    
    <?php if (!empty($content)): ?>
        <div class="mio-modulo__content">
            <?= $content ?>
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

### 3. Stili CSS (`mio-modulo.css`)

```css
/**
 * Modulo: Nome Modulo
 * File: mio-modulo.css
 */

.mio-modulo {
  /* Stili base del modulo */
  padding: 2rem;
  background: var(--color-white);
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.mio-modulo__title {
  /* Titolo del modulo */
  font-family: var(--font-display);
  font-size: 2rem;
  font-weight: 700;
  color: var(--primary);
  margin-bottom: 1rem;
}

.mio-modulo__content {
  /* Contenuto del modulo */
  font-family: var(--font-primary);
  line-height: 1.6;
  color: var(--text-color);
}

/* Varianti */
.mio-modulo--primary {
  border-left: 4px solid var(--primary);
}

.mio-modulo--secondary {
  border-left: 4px solid var(--secondary);
}

.mio-modulo--accent {
  border-left: 4px solid var(--accent);
}

/* Responsive */
@media (max-width: 768px) {
  .mio-modulo {
    padding: 1rem;
  }
  
  .mio-modulo__title {
    font-size: 1.5rem;
  }
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
```

### 4. JavaScript (opzionale - `mio-modulo.js`)

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
      // Event listeners
      this.element.addEventListener('click', this.handleClick.bind(this));
      
      // Eventi personalizzati
      this.element.addEventListener('mio-modulo:update', this.handleUpdate.bind(this));
    }

    handleClick(event) {
      // Gestione click
      const target = event.target.closest('[data-action]');
      if (!target) return;

      const action = target.dataset.action;
      this.executeAction(action, target);
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
        default:
          console.warn('Azione sconosciuta:', action);
      }
    }

    loadData() {
      // Caricamento dati
      if (this.config.apiUrl) {
        this.fetchData(this.config.apiUrl);
      }
    }

    async fetchData(url) {
      try {
        this.setLoading(true);
        const response = await fetch(url);
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
      this.element.classList.toggle('is-active');
    }

    refresh() {
      this.loadData();
    }

    handleUpdate(event) {
      // Gestione aggiornamenti
      const { config } = event.detail;
      this.config = { ...this.config, ...config };
      this.render();
    }

    render() {
      // Re-rendering modulo
      // Implementare logica di rendering
    }

    destroy() {
      // Cleanup
      this.element.removeEventListener('click', this.handleClick);
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

## 🎨 Convenzioni CSS

### Nomenclatura BEM
```css
/* Block */
.mio-modulo {}

/* Element */
.mio-modulo__title {}
.mio-modulo__content {}
.mio-modulo__button {}

/* Modifier */
.mio-modulo--primary {}
.mio-modulo--large {}
.mio-modulo__button--active {}
```

### CSS Variables
```css
.mio-modulo {
  /* Usa sempre le variabili del sistema */
  color: var(--text-color);
  background: var(--color-white);
  border: 1px solid var(--border-color);
  
  /* Spacing consistente */
  padding: var(--spacing-md);
  margin: var(--spacing-sm);
  
  /* Typography */
  font-family: var(--font-primary);
  font-size: var(--font-size-base);
  line-height: var(--line-height-base);
}
```

### Responsive Design
```css
/* Mobile First */
.mio-modulo {
  /* Stili base (mobile) */
}

/* Tablet */
@media (min-width: 768px) {
  .mio-modulo {
    /* Stili tablet */
  }
}

/* Desktop */
@media (min-width: 1024px) {
  .mio-modulo {
    /* Stili desktop */
  }
}
```

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
  "enabled": {
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
  "items": {
    "type": "array",
    "label": "Elementi",
    "item_schema": {
      "title": {
        "type": "text",
        "label": "Titolo",
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

## 📊 Gestione Dati

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
    // Logica per ottenere dati
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

## 🚀 Workflow Sviluppo

### 1. Creare Nuovo Modulo
```bash
# 1. Crea cartella
mkdir modules/mio-modulo

# 2. Crea file base
touch modules/mio-modulo/mio-modulo.php
touch modules/mio-modulo/mio-modulo.css
touch modules/mio-modulo/mio-modulo.js
touch modules/mio-modulo/module.json

# 3. Sviluppa modulo
# 4. Testa in development
npm run dev

# 5. Registra nel database
# Vai su admin/test-setup.php
```

### 2. Modificare Modulo Esistente
```bash
# 1. Modifica file
# 2. Testa in development
npm run dev

# 3. Aggiorna manifest se necessario
# 4. Testa in page builder
# Vai su admin/page-builder.php
```

### 3. Debug Modulo
```php
// Nel template PHP
<?php
// Debug configurazione
error_log('Config modulo: ' . print_r($config, true));

// Debug dati
error_log('Dati modulo: ' . print_r($moduleData, true));
?>
```

```javascript
// Nel JavaScript
console.log('Config modulo:', this.config);
console.log('Elemento:', this.element);
```

## 🧪 Testing

### Test Manuale
1. **Page Builder**: Testa drag & drop
2. **Configurazione**: Testa tutti i campi
3. **Responsive**: Testa su diversi dispositivi
4. **Accessibilità**: Testa con screen reader

### Test Automatico
```javascript
// Test unitario modulo
describe('MioModulo', function() {
  let element, module;

  beforeEach(function() {
    element = document.createElement('div');
    element.className = 'mio-modulo';
    document.body.appendChild(element);
    module = new MioModulo(element);
  });

  afterEach(function() {
    module.destroy();
    document.body.removeChild(element);
  });

  it('should initialize correctly', function() {
    expect(module.element).toBe(element);
    expect(module.config).toBeDefined();
  });

  it('should handle click events', function() {
    spyOn(module, 'toggle');
    element.click();
    expect(module.toggle).toHaveBeenCalled();
  });
});
```

## 📚 Best Practices

### Sicurezza
- **Sanitizzazione**: Usa `htmlspecialchars()` per output
- **Validazione**: Valida input nel template
- **SQL Injection**: Usa prepared statements
- **XSS**: Escape output HTML

### Performance
- **CSS**: Minifica e ottimizza
- **JS**: Usa event delegation
- **Images**: Ottimizza dimensioni
- **Database**: Usa indici appropriati

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

## 🔍 Debugging

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

### Log Debug
```php
// Abilita debug in development
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Modulo: ' . $moduleName);
    error_log('Config: ' . print_r($config, true));
}
```

## 📖 Esempi Pratici

### Modulo Semplice (Text)
Vedi `modules/text/` per esempio completo

### Modulo Complesso (Results)
Vedi `modules/results/` per esempio con database

### Modulo Interattivo (Menu)
Vedi `modules/menu/` per esempio con JavaScript

## 🤝 Contribuire

1. **Fork** del repository
2. **Crea branch**: `git checkout -b feature/nuovo-modulo`
3. **Sviluppa modulo** seguendo convenzioni
4. **Testa** in development e production
5. **Documenta** nel README del modulo
6. **Commit**: `git commit -m 'feat: aggiungi modulo X'`
7. **Push**: `git push origin feature/nuovo-modulo`
8. **Pull Request**

## 📄 Licenza

MIT License - Vedi file LICENSE per dettagli

---

**Sistema Moduli - Bologna Marathon** 🧩

*Versione 1.0.0 - Gennaio 2025*
