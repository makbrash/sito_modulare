# 🏃‍♂️ Bologna Marathon - Sistema Modulare

Sistema modulare SSR per il sito ufficiale della Bologna Marathon (bolognamarathon.run)

## 📋 Panoramica

- **Tipo**: Sito sportivo modulare
- **Tecnologie**: PHP 8+, MySQL, CSS Variables, JavaScript vanilla
- **Architettura**: SSR (Server-Side Rendering) modulare
- **Build System**: Gulp 4 + BrowserSync
- **Path**: `http://localhost/BM_layout/sito_modulare/`

## 🚀 Quick Start

### Prerequisiti
- XAMPP (Apache + MySQL)
- Node.js 16+
- PHP 8.0+

### Setup Iniziale
```bash
# 1. Clona il repository
git clone [repository-url]
cd sito_modulare

# 2. Installa dipendenze Node.js
npm install

# 3. Avvia XAMPP (Apache + MySQL)

# 4. Setup database
# Vai su: http://localhost/BM_layout/sito_modulare/admin/test-setup.php

# 5. Test sito
# Vai su: http://localhost/BM_layout/sito_modulare/index.php
```

## 🛠️ Comandi di Sviluppo

### Sviluppo Locale
```bash
# Avvia server di sviluppo con live-reload
npm run dev

# Configura proxy personalizzato (opzionale)
set BROWSERSYNC_PROXY=http://localhost/custom-path/index.php
npm run dev
```

### Build per Produzione
```bash
# Genera cartella build/ pronta per cloud
npm run release

# La cartella build/ contiene:
# - index.php (rinominato da index-prod.php)
# - assets/css/main.min.css (bundle minificato)
# - assets/js/app.min.js (bundle minificato)
# - Tutti i file PHP e database
```

### Altri Comandi
```bash
# Build solo CSS
npm run css:build

# Build solo JS
npm run js:build

# Ottimizza immagini
npm run images:optimize
```

## 📁 Struttura Progetto

```
sito_modulare/
├── assets/
│   ├── css/core/           # CSS core (variables, reset, typography)
│   ├── css/main.css        # Stili principali (dev)
│   ├── js/core/            # JavaScript core
│   ├── images/             # Immagini
│   └── font/               # Font personalizzati
├── config/
│   └── database.php        # Configurazione database
├── core/
│   └── ModuleRenderer.php  # Sistema rendering moduli SSR
├── modules/                # Moduli riutilizzabili
│   ├── hero/              # Action Hero
│   ├── results/           # Tabella risultati
│   ├── menu/              # Menu navigazione
│   ├── footer/            # Footer sito
│   ├── text/              # Rich Text
│   ├── button/            # Pulsanti
│   ├── race-cards/        # Card gare
│   └── select/            # Select personalizzato
├── admin/                  # Pannello amministrazione
│   ├── admin.php          # Dashboard principale
│   ├── page-builder.php   # Drag&drop moduli
│   └── test-setup.php     # Setup database
├── database/
│   ├── schema.sql         # Schema database
│   └── test_data.sql      # Dati di test
├── build/                  # Output build (generata)
├── gulpfile.js            # Configurazione build
├── package.json           # Dipendenze Node.js
├── index.php              # Homepage (dev)
├── index-prod.php         # Template produzione
└── README.md              # Questo file
```

## 🎨 Sistema CSS Variables

### File Principale
`assets/css/core/variables.css`

### Colori Principali
```css
:root {
  --primary: #23a8eb;      /* Colore principale */
  --secondary: #dc335e;    /* Colore secondario */
  --accent: #cbdf44;       /* Colore accent */
  --info: #5DADE2;         /* Colore info */
  --success: #00a8ff;      /* Colore success */
  --warning: #F39C12;      /* Colore warning */
  --error: #E74C3C;        /* Colore error */
}
```

### Font System
```css
:root {
  --font-primary: 'Inter';           /* Font principale */
  --font-display: 'Bebas Neue';      /* Font display */
  --font-accent: 'Gloss And Bloom';  /* Font accent personalizzato */
}
```

### Personalizzazione
- **Override per pagina**: Tramite database (campo `css_variables`)
- **Override globali**: Modifica `variables.css`
- **Responsive**: Variabili per breakpoint
- **Font esterni**: Google Fonts + Font Awesome + Custom Fonts

## 🧩 Moduli Disponibili

### 1. actionHero
- **Descrizione**: Hero section con layout 2 colonne
- **File**: `modules/hero/hero.php`
- **Config**: title, subtitle, image, layout

### 2. resultsTable
- **Descrizione**: Tabella risultati gara (ordinabile, filtri)
- **File**: `modules/results/results.php`
- **Config**: race_id, limit, sortable

### 3. menu
- **Descrizione**: Menu navigazione (sticky, mobile-friendly)
- **File**: `modules/menu/menu.php`
- **Config**: items, sticky, mobile_breakpoint

### 4. footer
- **Descrizione**: Footer sito (4 colonne, social)
- **File**: `modules/footer/footer.php`
- **Config**: columns, social, copyright

### 5. richText
- **Descrizione**: Contenuti testuali ricchi
- **File**: `modules/text/text.php`
- **Config**: content, wrapper, content_id

### 6. button
- **Descrizione**: Pulsanti personalizzabili
- **File**: `modules/button/button.php`
- **Config**: text, variant, size, href, icon

### 7. raceCards
- **Descrizione**: Card gare con layout verticale/orizzontale
- **File**: `modules/race-cards/race-cards.php`
- **Config**: layout, race_meta

### 8. select
- **Descrizione**: Select personalizzato
- **File**: `modules/select/select.php`
- **Config**: options, placeholder, multiple

## 🗄️ Database Schema

### Tabelle Principali

#### `pages`
- Gestione pagine del sito
- Campi: id, title, slug, description, status, css_variables

#### `modules_registry`
- Registro moduli disponibili
- Campi: name, description, component_path, default_config, is_active

#### `page_modules`
- Moduli assegnati alle pagine (sistema tradizionale)
- Campi: page_id, module_name, config, order_index, is_active

#### `module_instances`
- Istanze di moduli per il page builder
- Campi: page_id, module_name, instance_name, config, order_index, is_active

#### `race_results`
- Risultati delle gare
- Campi: id, race_id, position, name, time, category

#### `races`
- Gare disponibili
- Campi: id, name, distance, status, date

#### `dynamic_content`
- Contenuti dinamici per moduli
- Campi: id, content_type, content, is_active, updated_at

### Setup Database
1. **Setup automatico**: `admin/test-setup.php`
2. **Pannello admin**: `admin/admin.php`
3. **Page Builder**: `admin/page-builder.php`
4. **Debug**: `debug.php` (se presente)

## 🔧 Workflow Sviluppo

### Aggiungere Nuovo Modulo
1. **Crea cartella**: `modules/mio-modulo/`
2. **File necessari**:
   - `mio-modulo.php` (template PHP)
   - `mio-modulo.css` (stili)
   - `mio-modulo.js` (JavaScript, opzionale)
   - `module.json` (manifest)
   - `install.sql` (setup database)

3. **Template modulo**:
```php
<?php
$moduleData = $renderer->getModuleData('mioModulo', $config);
?>
<div class="mio-modulo">
    <!-- Contenuto modulo -->
</div>
```

4. **Registra modulo**: Inserisci in `modules_registry`

### Personalizzazione Colori
```css
/* In assets/css/core/variables.css */
:root {
  --primary-color: #TUO_COLORE;
  --secondary-color: #ALTRO_COLORE;
}
```

### Override per Pagina (Database)
```json
{
  "--primary-color": "#D81E05",
  "--hero-bg": "linear-gradient(135deg, #667eea 0%, #764ba2 100%)"
}
```

## 🌐 Deployment

### Cloud (Senza Node.js)
1. **Build locale**:
   ```bash
   npm run release
   ```

2. **Carica cartella `build/`**:
   - Comprimi `build/` in ZIP
   - Carica su server cloud
   - Estrai nella cartella web
   - Configura database
   - Esegui `install.php` (se presente)

3. **Configurazione server**:
   - Apache: `.htaccess` incluso
   - Nginx: Configurazione manuale
   - PHP 8.0+ richiesto
   - MySQL 5.7+ richiesto

### File di Configurazione
- **.htaccess**: Cache, compressione, security headers
- **config.example.php**: Template configurazione DB
- **install.php**: Installazione automatica database

## 🎯 Caratteristiche Principali

### Sistema Modulare
- **SSR**: Rendering server-side per SEO
- **Drag & Drop**: Page builder visuale
- **Istanze**: Moduli riutilizzabili con configurazioni
- **Alias**: Nomi alternativi per moduli

### Performance
- **Bundle**: CSS/JS minificati in produzione
- **Cache**: Headers per asset statici
- **Compressione**: Gzip automatica
- **Ottimizzazione**: Immagini compresse

### Accessibilità
- **Skip Links**: Navigazione da tastiera
- **ARIA**: Attributi per screen reader
- **Contrasti**: Colori accessibili
- **Focus**: Gestione focus visibile

### Responsive
- **Mobile First**: Design responsive
- **Breakpoints**: CSS Variables per media queries
- **Touch**: Interazioni touch-friendly
- **Performance**: Ottimizzato per mobile

## 🔒 Sicurezza

### Headers
- X-Content-Type-Options: nosniff
- X-Frame-Options: DENY
- X-XSS-Protection: 1; mode=block

### Database
- Prepared statements
- Input sanitization
- SQL injection protection

### File
- Configurazioni sensibili escluse
- Upload sicuro
- Permessi corretti

## 🆘 Troubleshooting

### Problemi Comuni

#### "Pagina non trovata"
- Controlla `status = 'published'` nel database
- Verifica slug della pagina

#### Database vuoto
- Usa `admin/test-setup.php`
- Controlla credenziali in `config/database.php`

#### Moduli mancanti
- Verifica `modules_registry` nel database
- Controlla file in `modules/`

#### Path sbagliato
- Usa `http://localhost/BM_layout/sito_modulare/`
- Verifica configurazione Apache

#### "Undefined variable $renderer"
- Variabile passata automaticamente dal ModuleRenderer
- Controlla include del file

#### Percorsi moduli
- Usa percorsi relativi: `hero/hero.php`
- Non `modules/hero/hero.php`

#### SQL LIMIT error
- LIMIT non può essere parametro preparato
- Usa concatenazione sicura

### Debug
1. **Errori PHP**: Controlla log Apache
2. **Database**: Usa `admin/test-setup.php`
3. **Moduli**: Verifica `modules_registry`
4. **Build**: Controlla output `npm run release`

## 📚 Documentazione Aggiuntiva

- **Moduli**: `modules/README.md`
- **CSS Variables**: `docs/COLORS_AND_FONTS.md`
- **Page Builder**: `admin/page-builder.php`
- **API Moduli**: `core/ModuleRenderer.php`

## 🤝 Contribuire

1. Fork del repository
2. Crea branch feature: `git checkout -b feature/nuovo-modulo`
3. Commit changes: `git commit -m 'Aggiungi nuovo modulo'`
4. Push branch: `git push origin feature/nuovo-modulo`
5. Crea Pull Request

### Convenzioni
- **Moduli**: Segui struttura esistente
- **CSS**: Usa CSS Variables
- **PHP**: PSR-12 coding standards
- **JS**: ES6+ con fallback
- **Commit**: Conventional commits

## 📄 Licenza

MIT License - Vedi file LICENSE per dettagli

## 👥 Team

- **Bologna Marathon Team**
- **Sistema Modulare**: Sviluppato per bolognamarathon.run

---

**Bologna Marathon - Sistema Modulare** 🏃‍♂️

*Versione 1.0.0 - Gennaio 2025*