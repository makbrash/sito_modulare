# 🏁 Bologna Marathon – Sistema Modulare v2.0

Sistema SSR modulare avanzato per la Bologna Marathon con:
- 🎨 **Admin Dashboard** moderno (Alpine.js + Tailwind CSS)
- 🎯 **Page Builder** drag & drop con live preview
- 🔐 **Sistema Auth** opt-in con ruoli e sessioni
- 🛠️ **API REST** per gestione completa
- 📊 **Error Handling** centralizzato con logging
- ⚡ **Build System** ottimizzato per cloud deployment

## 🌐 Architettura

| Layer | Tecnologie | Descrizione |
| --- | --- | --- |
| **Frontend** | PHP 8+ SSR, CSS Variables, JavaScript vanilla | Server-Side Rendering, nessuna dipendenza Node in produzione |
| **Admin UI** | Alpine.js, Tailwind CSS, Font Awesome | Dashboard moderna responsive con dark mode |
| **Backend** | PHP 8+, PDO, Prepared Statements | Architettura a servizi, API REST, error handling centralizzato |
| **Database** | MySQL 5.7+ | Schema ottimizzato con indici, supporto auth e activity log |
| **Build** | Gulp 4, PostCSS, Terser | Pipeline ottimizzata per minify e deploy cloud |
| **Auth** | Sessions, Bcrypt, CSRF Protection | Sistema opt-in con brute-force protection e roles |
| **Logging** | PSR-3 compliant, File rotation | Logger strutturato con livelli e auto-cleanup |

## 📁 Struttura principale

```
sito_modulare/
├── admin/                # Sistema amministrazione completo
│   ├── api/              # REST API endpoints (pages, modules, themes, auth)
│   ├── components/       # Layout components (header, sidebar, footer)
│   ├── pages/            # Admin pages (gestione pagine, moduli, temi)
│   ├── docs/             # Documentazione admin (ADMIN-SYSTEM.md, QUICK-START.md)
│   ├── dashboard.php     # Dashboard principale con statistiche
│   ├── page-builder.php  # Page Builder drag & drop
│   ├── login.php         # Pagina login (se AUTH_ENABLED=true)
│   └── auth-check.php    # Middleware autenticazione
├── core/
│   ├── Services/         # Backend services (Page, Module, Theme, Asset, Config, Data)
│   ├── Auth/             # Sistema autenticazione (AuthService, AuthMiddleware)
│   ├── Utils/            # Utilities (ErrorHandler, Logger, DotEnv)
│   ├── API/              # BaseController per API
│   ├── ModuleRenderer.php # Renderer principale moduli SSR
│   └── bootstrap.php     # Init error handler, logger, .env
├── assets/
│   ├── css/
│   │   ├── core/         # Variables, reset, typography, layout, scrollbar
│   │   └── admin/        # Stili admin dashboard
│   └── js/
│       ├── core/         # App.js, image-3d.js
│       └── admin/        # page-builder.js
├── modules/              # Moduli riutilizzabili (hero, menu, footer, etc.)
│   ├── <module>/
│   │   ├── module.json   # Manifest con ui_schema
│   │   ├── *.php         # Template PHP
│   │   ├── *.css         # Stili modulo
│   │   └── *.js          # JavaScript (opzionale)
│   └── docs/             # DEVELOPMENT-GUIDE.md, TEMPLATES-SYSTEM.md
├── database/
│   ├── docs/             # SCHEMA-REFERENCE.md, MIGRATIONS.md
│   ├── migrations/       # add_admin_users.sql per sistema auth
│   ├── schema.sql        # Schema completo database
│   └── test_data.sql     # Dati di test
├── docs/                 # Documentazione sistema generale
│   ├── README.md         # Indice documentazione
│   ├── BUILD-SYSTEM.md   # Sistema build e deploy
│   ├── THEME-SYSTEM-FINAL.md  # Sistema temi dinamici
│   └── LAYOUT-SYSTEM.md  # Sistema layout responsive
├── logs/                 # Log applicazione (gitignored)
│   └── app.log           # Log principale con rotazione automatica
├── build/                # Output deploy cloud (generato)
├── .env                  # Configurazioni ambiente (gitignored)
├── env.example           # Template .env con commenti
├── gulpfile.js           # Pipeline build (minify, bundle, deploy)
└── package.json          # Dipendenze build (Gulp, PostCSS, Terser)
```

## ⚙️ Setup Rapido (5 minuti)

### 1. **Prerequisiti**
- PHP ≥ 8.0 con estensioni: PDO, MySQLi, JSON, Mbstring
- MySQL ≥ 5.7
- Node.js ≥ 16 (solo per build locale, non in produzione)
- Web server: Apache/Nginx con mod_rewrite

### 2. **Installazione**
```bash
git clone <repo>
cd sito_modulare
npm install           # Solo per build tools
```

### 3. **Configurazione .env**
```bash
cp env.example .env   # Copia template configurazione
```

Modifica `.env`:
```env
# Database
DB_HOST=localhost
DB_DATABASE=bologna_marathon
DB_USERNAME=root
DB_PASSWORD=

# Application
APP_DEBUG=true                # false in produzione
AUTH_ENABLED=false            # true per attivare login admin
LOG_ENABLED=true
LOG_LEVEL=error              # debug|info|warning|error
```

### 4. **Setup Database**
```bash
# Importa schema completo (include dati test)
mysql -u root bologna_marathon < database/schema.sql

# (Opzionale) Sistema autenticazione
mysql -u root bologna_marathon < database/migrations/add_admin_users.sql
```

Oppure usa tool visuale:
```
http://localhost/sito_modulare/admin/test-setup.php
```

### 5. **Accesso Admin**

**Senza autenticazione** (default):
```
http://localhost/sito_modulare/admin/
# Accesso diretto, nessun login richiesto
```

**Con autenticazione** (se `AUTH_ENABLED=true`):
```
http://localhost/sito_modulare/admin/
Username: admin
Password: admin123
⚠️ CAMBIA PASSWORD al primo accesso!
```

### 6. **Sviluppo**
```bash
npm run dev          # Watch mode: auto-compile CSS/JS
npm run serve        # Watch + BrowserSync (live reload)
```

### 7. **Build per Produzione**
```bash
npm run build        # Minify CSS/JS
npm run release      # Build completo → cartella build/
npm run rollback     # Rollback ultimo backup
```

La cartella `build/` contiene tutto pronto per deploy cloud. **Zero dipendenze Node in produzione**.

## 🧩 Page Builder (admin/page-builder.php)

### Funzionalità principali
- Drag & drop con SortableJS (supporto ordinamento dinamico)
- Moduli annidabili basati su manifest JSON (`modules/<slug>/module.json`)
- Configuratore dinamico generato da `ui_schema`
- Anteprima live con rendering server-side
- API RESTful (`admin/api/page_builder.php`) per CRUD istanze moduli

### Workflow
1. **Seleziona una pagina** dal menù a tendina
2. **Aggiungi moduli** dalla libreria (riutilizzabili e annidabili)
3. **Configura il modulo** tramite form generato da `ui_schema`
4. **Salva** per creare/aggiornare l'istanza (`module_instances`)
5. **Trascina** per riordinare (persistenza automatica dell'ordine)
6. **Anteprima** apre rendering lato server in modal oppure pagina pubblica

### UI Schema (estratto)
   ```json
"ui_schema": {
  "title": {
    "type": "text",
    "label": "Titolo",
    "placeholder": "Titolo sezione",
    "help": "Usato nell'hero principale"
  },
  "menu_items": {
    "type": "array",
    "label": "Voci menu",
    "item_schema": {
      "label": { "type": "text", "label": "Etichetta" },
      "url":   { "type": "url",  "label": "URL" }
    }
  }
}
```

Ogni campo supporta `type`, `label`, `placeholder`, `default`, `help`, `options` (per select) e strutture `array` con `item_schema` annidato.

## 🧩 Moduli

### Guida Completa Sviluppo
- **Documentazione**: `modules/docs/DEVELOPMENT-GUIDE.md` (guida completa)
- **Sistema Template**: `modules/docs/TEMPLATES-SYSTEM.md` (modelli globali)
- **Esempi**: `modules/README.md` (esempi pratici)

### Struttura Modulo
- Ogni modulo vive in `modules/<slug>/`
- File obbligatori: `module.json`, template PHP, CSS/JS opzionali
- `module.json` deve includere almeno:
  ```json
  {
    "name": "Hero",
    "slug": "hero",
    "component_path": "hero/hero.php",
    "default_config": { ... },
    "ui_schema": { ... }
  }
  ```
- I campi `default_config` e `ui_schema` vengono uniti lato server con la configurazione salvata
- Documenta ogni modulo con README o schema per facilitare automazione LLM futura

### Regole CSS CRITICHE
- **MAI** stili annidati (`&:hover`, `&::before`)
- **SOLO** CSS classico esplicito
- **SEMPRE** CSS Variables
- **SEMPRE** BEM methodology
- **SEMPRE** mobile-first responsive

### Consigli
- Riutilizza moduli esistenti quando possibile
- Evita hardcoding di colori: usa `assets/css/core/variables.css`
- Mantieni compatibilità con CSS del menu principale
- Per select/form usa componenti validati dalla community (es. [Shoelace](https://shoelace.style/)) integrandoli via manifest `assets.vendors`

## 🛠️ Maintenance & Quality

- **PHP**: segui PSR-12, niente `try/catch` attorno agli `include`
- **JS**: ES2015+, nessun transpiler necessario
- **CSS**: niente nesting tipo `&`, usa classi esplicite
- **Database**: tutte le tabelle già indicizzate, mantieni `module_instances.instance_name` univoco per pagina
- **Logs**: eventuali errori AJAX restituiscono JSON con messaggi significativi

### Test veloci
- `php -l admin/page-builder.php` (lint)
- `npm run release` (verifica build)
- Controlla anteprima moduli dalla UI admin

## 🚀 Deploy su cloud

1. Esegui `npm run release`
2. Carica il contenuto di `build/` sul server PHP
3. Imposta credenziali DB su `build/config/database.php`
4. (Opzionale) configura cache HTTP e compressione da `.htaccess`

> **Nota:** la produzione non richiede Node.js. Tutti gli asset sono già precompilati.

## 📄 Licenza

MIT License – consulta il file `LICENSE` per i dettagli.

---

## 🚨 REGOLE FONDAMENTALI

### ⚠️ PRIMA DI SCRIVERE CODICE

**Leggi**: `docs/CODING-STANDARDS.md` - **OBBLIGATORIO**

#### Regole Base
1. ❌ **NO HARDCODING** - Usa CSS Variables, configurazioni, costanti
2. ❌ **NO CODICE SPAGHETTI** - Separa CSS, JS, PHP in file dedicati
3. ❌ **NO CSS/JS INLINE** - Sempre file esterni
4. ✅ **Separazione responsabilità** - Template ≠ Stili ≠ Logica

## 📚 Documentazione Completa

### 🚀 Quick Start
- **⚡ Guida Rapida**: `admin/docs/QUICK-START.md` (5 minuti)
- **📖 Mappa Documentazione**: `DOCUMENTATION-MAP.md` (navigazione completa)

### 🎨 Sistema Admin
- **📘 Admin System**: `admin/docs/ADMIN-SYSTEM.md` - Guida completa admin dashboard
  - Architettura backend services
  - API REST endpoints
  - Sistema autenticazione
  - Error handling & logging
  - Page Builder workflow
  - Best practices
- **🚀 Quick Start**: `admin/docs/QUICK-START.md` - Setup 5 minuti
- **🎨 Page Builder**: `admin/docs/PAGE-BUILDER.md` - Drag & drop interface
- **🐛 Troubleshooting**: `admin/docs/TROUBLESHOOTING.md` - Risoluzione problemi

### 🧩 Sviluppo Moduli
- **📘 Development Guide**: `modules/docs/DEVELOPMENT-GUIDE.md` - Guida completa
- **🎯 Templates System**: `modules/docs/TEMPLATES-SYSTEM.md` - Moduli globali
- **📝 Module Examples**: `modules/README.md` - Esempi pratici
- **✅ Regole**: `.cursorrules` (sezione moduli)

### 🗄️ Database
- **📊 Schema Reference**: `database/docs/SCHEMA-REFERENCE.md` - Schema completo
- **🔄 Migrations**: `database/docs/MIGRATIONS.md` - Guide migrazione
- **💾 SQL Files**: 
  - `database/schema.sql` - Schema completo
  - `database/migrations/add_admin_users.sql` - Sistema auth

### 🎨 Sistema Generale
- **📖 Overview**: `docs/README.md` - Panoramica documentazione
- **🚨 Coding Standards**: `docs/CODING-STANDARDS.md` - Standard obbligatori
- **⚡ Quick Reference**: `docs/QUICK-REFERENCE.md` - Riferimento rapido
- **🏗️ Build System**: `docs/BUILD-SYSTEM.md` - Build e deploy
- **🎨 Theme System**: `docs/THEME-SYSTEM-FINAL.md` - Temi dinamici
- **📐 Layout System**: `docs/LAYOUT-SYSTEM.md` - Layout responsive

### 🔐 Autenticazione
- **🔒 Auth Guide**: `admin/docs/AUTH-ACTIVATION-GUIDE.md` - Attivazione auth
- **👤 User Management**: Sistema ruoli e permessi
- **🔑 Security**: CSRF, brute-force protection, session management

### 📊 Error Handling & Logging
- **⚠️ Error Handler**: `core/Utils/ErrorHandler.php` - Gestione centralizzata
- **📝 Logger**: `core/Utils/Logger.php` - Logging strutturato PSR-3
- **🔧 Bootstrap**: `core/bootstrap.php` - Inizializzazione sistema

### 🔄 API Reference
- **📡 Pages API**: `admin/api/pages.php` - CRUD pagine
- **🧩 Modules API**: `admin/api/modules.php` - CRUD moduli
- **🎨 Themes API**: `admin/api/themes.php` - CRUD temi
- **🔐 Auth API**: `admin/api/auth.php` - Autenticazione
- **📚 API Docs**: `admin/api/README.md` - Documentazione completa

### 🛠️ Per Sviluppatori
**Prima di iniziare** (obbligatorio):
1. ⚡ **Leggi**: `docs/QUICK-REFERENCE.md` (2 min)
2. 🚨 **Segui**: `docs/CODING-STANDARDS.md` (10 min)
3. 📘 **Consulta**: `admin/docs/ADMIN-SYSTEM.md` (riferimento completo)

**Durante sviluppo**:
- Moduli: `modules/docs/DEVELOPMENT-GUIDE.md`
- Database: `database/docs/SCHEMA-REFERENCE.md`
- API: `admin/api/README.md`

### 🤖 Per AI Models
**File critici per context**:
1. `.cursorrules` - Regole complete progetto
2. `docs/CODING-STANDARDS.md` - Standard codifica
3. `admin/docs/ADMIN-SYSTEM.md` - Sistema admin
4. `modules/docs/DEVELOPMENT-GUIDE.md` - Sviluppo moduli
5. `database/docs/SCHEMA-REFERENCE.md` - Schema database

### 🎯 Guide Rapide per Task Comuni
| Task | Guida | Tempo |
|------|-------|-------|
| Setup iniziale | `admin/docs/QUICK-START.md` | 5 min |
| Creare modulo | `modules/docs/DEVELOPMENT-GUIDE.md` | 30 min |
| Modificare tema | `docs/THEME-SYSTEM-FINAL.md` | 10 min |
| API endpoint | `admin/api/README.md` | 20 min |
| Deploy produzione | `docs/BUILD-SYSTEM.md` | 15 min |
| Troubleshooting | `admin/docs/TROUBLESHOOTING.md` | - |

Per dettagli completi, consulta `DOCUMENTATION-MAP.md` con navigazione gerarchica di tutta la documentazione disponibile.