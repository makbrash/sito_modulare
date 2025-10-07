# 📚 Indice Documentazione - Bologna Marathon

## 🎯 Documentazione Sistema Moduli

### 📖 Guide Principali
- **`MODULE-DEVELOPMENT-GUIDE.md`** - Guida completa per lo sviluppo moduli
- **`LAYOUT-SYSTEM.md`** - Sistema layout responsive
- **`THEME-SYSTEM-FINAL.md`** - Sistema temi dinamici finale
- **`BUILD-SYSTEM.md`** - Sistema build e deploy

### 🔗 Riferimenti Sistema
- **`.cursorrules`** - Regole per Cursor AI
- **`README.md`** - Documentazione progetto principale
- **`modules/README.md`** - Esempi pratici moduli

### 🎨 Risorse CSS
- **`assets/css/core/variables.css`** - CSS Variables del sistema
- **`assets/css/core/reset.css`** - Reset CSS
- **`assets/css/core/typography.css`** - Typography system
- **`assets/css/core/fonts.css`** - Font system

### 🔧 Sistema Core
- **`core/ModuleRenderer.php`** - Sistema rendering moduli
- **`admin/page-builder.php`** - Page builder per moduli
- **`admin/test-setup.php`** - Setup database
- **`config/database.php`** - Configurazione database

### 🗄️ Database
- **`database/schema.sql`** - Schema database
- **`database/test_data.sql`** - Dati di test

### 🚀 Build System
- **`gulpfile.js`** - Pipeline di build
- **`package.json`** - Dipendenze e script
- **`BUILD-SYSTEM.md`** - Documentazione build system

## 📋 Struttura Documentazione

### 🎯 Per Sviluppatori
1. **Inizia con**: `MODULE-SUMMARY.md` (riepilogo rapido)
2. **Leggi**: `MODULE-DEVELOPMENT-GUIDE.md` (guida completa)
3. **Segui**: `MODULE-TEMPLATE.md` (template pratico)
4. **Valida**: `MODULE-CHECKLIST.md` (checklist)

### 🎯 Per Cursor AI
1. **Regole**: `.cursorrules` (regole principali)
2. **Moduli**: `modules/.cursorrules` (regole moduli)
3. **Template**: `MODULE-TEMPLATE.md` (template completo)
4. **Checklist**: `MODULE-CHECKLIST.md` (validazione)

### 🎯 Per Troubleshooting
1. **Problemi comuni**: `MODULE-DEVELOPMENT-GUIDE.md` (sezione troubleshooting)
2. **Errori CSS**: `MODULE-RULES.md` (regole CSS critiche)
3. **Database**: `core/ModuleRenderer.php` (esempi database)
4. **Page Builder**: `admin/page-builder.php` (integrazione)

## 🧩 Moduli Esistenti

### 📁 Moduli Disponibili
- **`modules/hero/`** - Hero section con layout 2 colonne
- **`modules/results/`** - Tabella risultati gara (ordinabile, filtri)
- **`modules/menu/`** - Menu navigazione (sticky, mobile-friendly)
- **`modules/footer/`** - Footer sito (4 colonne, social)
- **`modules/text/`** - Rich Text
- **`modules/gallery/`** - Galleria immagini (lightbox)

### 📖 Documentazione Moduli
- **`modules/README.md`** - Guida moduli con esempi
- **`modules/.cursorrules`** - Regole specifiche moduli
- **`modules/*/README.md`** - Documentazione moduli individuali

## 🎨 CSS Variables System

### 🎯 Variabili Principali
```css
/* Colori */
--primary: #23a8eb
--secondary: #dc335e
--accent: #cbdf44
--info: #5DADE2
--success: #00a8ff
--warning: #F39C12
--error: #E74C3C

/* Typography */
--font-primary: 'Inter'
--font-display: 'Bebas Neue'
--font-accent: 'Gloss And Bloom'

/* Spacing */
--spacing-xs: 0.25rem
--spacing-sm: 0.5rem
--spacing-md: 1rem
--spacing-lg: 1.5rem
--spacing-xl: 2rem

/* Layout */
--border-radius: 8px
--shadow-sm: 0 2px 4px rgba(0,0,0,0.1)
--shadow-md: 0 4px 8px rgba(0,0,0,0.15)
--shadow-lg: 0 8px 16px rgba(0,0,0,0.2)
```

## 🔧 Page Builder System

### 🎯 Funzionalità
- **Drag & Drop**: SortableJS per ordinamento moduli
- **Configurazione**: UI Schema per parametri moduli
- **Anteprima**: Real-time preview con rendering server-side
- **Database**: Persistenza configurazioni in `module_instances`

### 🎨 CSS Override
```css
/* Page Builder Overrides */
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

## 🗄️ Database Schema

### 📊 Tabelle Principali
- **`pages`** - Pagine del sito
- **`modules_registry`** - Moduli disponibili
- **`module_instances`** - Istanze moduli per pagina
- **`race_results`** - Risultati gara
- **`races`** - Gare
- **`dynamic_content`** - Contenuti dinamici

### 🔧 Setup Database
1. **Setup automatico**: `admin/test-setup.php`
2. **Pannello admin**: `admin/admin.php`
3. **Debug**: `debug.php`
4. **Path corretto**: `http://localhost/sito_modulare/`

## 🚀 Build System

### 📦 Comandi Disponibili
```bash
npm run dev        # Watch mode senza server
npm run serve      # Watch mode con BrowserSync
npm run build      # Build solo asset
npm run release    # Build completo per cloud
npm run rollback   # Rollback all'ultimo backup
npm run validate   # Validazione pre-build
npm run clean     # Pulizia build
```

### 🎯 Output Build
- **`build/`** - Cartella output per deploy
- **`build/assets/`** - Asset minificati
- **`build/modules/`** - Moduli copiati
- **`build/admin/`** - Admin panel
- **`build/index.php`** - Entry point produzione

## 🧪 Testing

### 📋 Test Manuale
1. **Page Builder**: Testa drag & drop
2. **Configurazione**: Testa tutti i campi UI Schema
3. **Responsive**: Testa su diversi dispositivi
4. **Accessibilità**: Testa con screen reader
5. **JavaScript**: Testa interazioni e eventi

### 🔍 Debug
- **PHP**: `error_log()` per debug server
- **JavaScript**: `console.log()` per debug client
- **Database**: Query logging per debug database
- **CSS**: Browser DevTools per debug stili

## 📚 Best Practices

### 🎨 CSS
- **MAI** stili annidati (`&:hover`)
- **SEMPRE** CSS Variables
- **SEMPRE** BEM methodology
- **SEMPRE** mobile-first responsive

### 🔧 PHP
- **SEMPRE** `htmlspecialchars()` per output
- **SEMPRE** prepared statements
- **SEMPRE** validazione input
- **SEMPRE** error handling

### 🎯 JavaScript
- **SEMPRE** classe modulare
- **SEMPRE** event delegation
- **SEMPRE** cleanup in destroy()
- **SEMPRE** auto-inizializzazione

### 🗄️ Database
- **SEMPRE** prepared statements
- **SEMPRE** indici appropriati
- **SEMPRE** validazione dati
- **SEMPRE** error handling

## 🎯 Workflow Sviluppo

### 1. **Setup Iniziale**
```bash
# Clona repository
git clone <repo>
cd sito_modulare

# Installa dipendenze
npm install

# Setup database
# Vai su http://localhost/sito_modulare/admin/test-setup.php
```

### 2. **Sviluppo Modulo**
```bash
# Crea cartella modulo
mkdir modules/mio-modulo

# Crea file base
touch modules/mio-modulo/mio-modulo.php
touch modules/mio-modulo/mio-modulo.css
touch modules/mio-modulo/mio-modulo.js
touch modules/mio-modulo/module.json

# Sviluppa modulo
# Usa template da MODULE-TEMPLATE.md
```

### 3. **Testing**
```bash
# Test development
npm run dev

# Test page builder
# Vai su http://localhost/sito_modulare/admin/page-builder.php

# Test responsive
# Testa su diversi dispositivi
```

### 4. **Build e Deploy**
```bash
# Build per produzione
npm run release

# Deploy
# Carica contenuto di build/ sul server
```

## 🔍 Troubleshooting

### 🚨 Problemi Comuni
1. **"Pagina non trovata"**: Controlla status = 'published'
2. **Database vuoto**: Usa `admin/test-setup.php`
3. **Moduli mancanti**: Verifica `modules_registry`
4. **Path sbagliato**: Usa `http://localhost/sito_modulare/`
5. **"Undefined variable $renderer"**: Variabile passata automaticamente
6. **Percorsi moduli**: Devono essere relativi
7. **SQL LIMIT error**: LIMIT non può essere parametro preparato

### 🔧 Soluzioni
- **CSS**: Verifica CSS Variables e BEM
- **PHP**: Controlla prepared statements e sanitizzazione
- **JavaScript**: Verifica event listeners e cleanup
- **Database**: Controlla indici e query
- **Page Builder**: Verifica CSS override e UI Schema

---

**Indice Documentazione - Bologna Marathon** 📚

*Versione 1.0.0 - Gennaio 2025*
