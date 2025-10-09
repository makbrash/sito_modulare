# 🔨 Sistema Build Unificato - Bologna Marathon

## 📋 Comandi Disponibili

### 🚀 **Comandi Principali**

```bash
# Sviluppo con hot-reload
npm run dev          # Watch mode senza server
npm run serve        # Watch mode con BrowserSync

# Build e Release
npm run build        # Build solo asset (CSS/JS/Images)
npm run release      # Build completo per cloud deployment

# Utility
npm run validate     # Validazione pre-build
npm run rollback     # Rollback all'ultimo backup
npm run clean        # Pulizia directory build
```

### 🛠️ **Comandi Specifici**

```bash
# Build singoli asset
npm run css          # Solo CSS
npm run js           # Solo JavaScript
npm run images       # Solo ottimizzazione immagini
npm run fonts        # Solo copia font
```

## 🏗️ **Architettura Build**

### **Directory Build**
- **Destinazione**: `build/` (standardizzata)
- **Backup**: `build-backup/` (rollback automatico)
- **Esclusi**: `node_modules/`, `.git/`, file di sviluppo

### **Validazione Pre-Build**
Il sistema esegue automaticamente:
- ✅ Controllo file critici (`config/database.php`, `core/ModuleRenderer.php`)
- ✅ Validazione struttura moduli (`module.json`, template PHP)
- ✅ Verifica dipendenze Node.js
- ✅ Controllo integrità file

### **Sistema Rollback**
- **Backup automatico** prima di ogni `release`
- **Rollback** con `npm run rollback`
- **Recupero** da `build-backup/`

## 🔄 **Workflow Sviluppo**

### **1. Sviluppo Locale**
```bash
npm run serve        # Avvia server con hot-reload
# Modifica file CSS/JS/PHP
# BrowserSync ricarica automaticamente
```

### **2. Build per Test**
```bash
npm run build        # Build asset ottimizzati
# Testa in build/ directory
```

### **3. Release per Cloud**
```bash
npm run release      # Build completo + configurazione
# Cartella build/ pronta per deployment
```

## 📁 **Struttura Output Build**

```
build/
├── index.php              # Entry point (da index-prod.php)
├── .htaccess             # Configurazione Apache
├── config.example.php     # Template configurazione DB
├── install.php           # Installazione automatica
├── DEPLOYMENT.md         # Guida deployment
├── assets/
│   ├── css/
│   │   └── main.min.css  # CSS minificato
│   ├── js/
│   │   └── app.min.js    # JS minificato
│   ├── images/           # Immagini ottimizzate
│   └── css/font/         # Font
├── modules/              # Moduli PHP
├── admin/                # Pannello admin
├── core/                 # Core system
├── config/               # Configurazione
└── database/             # Schema e dati
```

## ⚡ **Ottimizzazioni Automatiche**

### **CSS**
- **Autoprefixer** per compatibilità browser
- **Minificazione** in produzione
- **Source maps** per debug
- **Concat** di tutti i CSS moduli

### **JavaScript**
- **Concat** di tutti i JS moduli
- **Uglify** in produzione
- **Source maps** per debug

### **Immagini**
- **MozJPEG** per JPEG (qualità 80%)
- **OptiPNG** per PNG (livello 5)
- **SVGO** per SVG
- **Fallback** se plugin non disponibili

### **Server**
- **Gzip compression** (.htaccess)
- **Cache headers** per asset statici
- **Security headers** (XSS, CSRF protection)

## 🔧 **Configurazione Avanzata**

### **Variabili Ambiente**
```bash
NODE_ENV=production npm run release    # Forza modalità produzione
BROWSERSYNC_PROXY=http://localhost:8080 npm run serve  # Proxy personalizzato
```

### **Esclusioni Personalizzate**
Modifica `excludePatterns` in `gulpfile.js` per escludere file specifici.

### **Plugin Immagini**
Il sistema rileva automaticamente i plugin disponibili:
- `imagemin-mozjpeg` per JPEG
- `imagemin-optipng` per PNG  
- `imagemin-svgo` per SVG

## 🚨 **Troubleshooting**

### **Errore Validazione**
```bash
npm run validate     # Verifica errori specifici
```

### **Build Fallito**
```bash
npm run rollback     # Ripristina ultimo backup
```

### **Dipendenze Mancanti**
```bash
npm install          # Reinstalla dipendenze
```

## 📊 **Performance**

### **Tempi Build Tipici**
- **CSS**: ~2-3 secondi
- **JS**: ~1-2 secondi  
- **Immagini**: ~5-10 secondi (dipende da quantità)
- **Release completo**: ~15-30 secondi

### **Dimensioni Output**
- **CSS minificato**: ~50-100KB
- **JS minificato**: ~20-50KB
- **Immagini**: -30% rispetto all'originale

## 🔒 **Sicurezza**

### **File Generati**
- **install.php**: Eliminare dopo setup
- **config.example.php**: Template sicuro
- **.htaccess**: Headers di sicurezza

### **Validazione**
- **Input sanitization** nei moduli
- **XSS protection** headers
- **CSRF protection** ready

---

**Sistema Build Unificato v2.0** 🏃‍♂️
