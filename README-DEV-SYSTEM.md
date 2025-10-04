# 🚀 Sistema DEV - Bologna Marathon

## 📋 Come Funziona

Il nuovo sistema di sviluppo separa **DEV** e **PROD** con live-reload automatico:

- **DEV**: CSS leggibile + sourcemaps per editing da DevTools
- **PROD**: CSS minificato per produzione
- **Live-reload**: Browser si aggiorna automaticamente

## 🎯 Avvio Rapido

### 1. Sviluppo (Consigliato)
```bash
npm run dev
```
- ✅ Avvia server su `http://localhost:3000/BM_layout/sito_modulare`
- ✅ Watch automatico su SCSS, JS, PHP
- ✅ Live-reload browser
- ✅ CSS non minificato per debug

### 2. Solo CSS
```bash
npm run css:build
```
- ✅ Compila SCSS → CSS leggibile
- ✅ Genera sourcemaps per DevTools

### 3. Produzione
```bash
npm run build
```
- ✅ CSS minificato
- ✅ JS ottimizzato
- ✅ Assets pronti per deploy

## 🔧 Workflow Sviluppo

### Modifica SCSS → Browser
1. Modifica file in `assets/scss/`
2. Salva → Gulp ricompila automaticamente
3. Browser si aggiorna da solo

### Modifica da DevTools → SCSS
1. Apri DevTools (F12)
2. Modifica CSS nella tab "Elements"
3. Salva → Il file SCSS si aggiorna
4. Browser ricarica

**⚠️ Se non funziona il salvataggio automatico:**
- Il file `com.chrome.devtools.json` abilita il mapping automatico
- Chrome DevTools riconoscerà automaticamente la cartella locale

## 📁 File Generati

### DEV
- `assets/dist/css/main.css` - CSS leggibile (1600+ righe)
- `assets/dist/css/main.css.map` - Sourcemap con sorgenti embed

### PROD
- `assets/dist/css/main.min.css` - CSS minificato (1 riga)
- `assets/dist/css/main.min.css.map` - Sourcemap

## 🌐 URL di Sviluppo

- **Sito**: http://localhost:3000/sito_modulare
- **UI Browser-sync**: http://localhost:3001
- **XAMPP originale**: http://localhost/sito_modulare

## ⚡ Vantaggi

- 🎨 **Editing SCSS da DevTools** - Modifica direttamente dal browser
- 🔄 **Live-reload** - Nessun refresh manuale
- 🚀 **Build separata** - DEV vs PROD ottimizzati
- 🐛 **Debug facile** - Sourcemaps complete
- 📱 **Proxy XAMPP** - Funziona con PHP esistente

## 🛠️ Comandi Disponibili

```bash
npm run dev          # Sviluppo completo
npm run css:build    # Solo CSS
npm run js:build     # Solo JS
npm run build        # Produzione
npm run images:optimize  # Ottimizza immagini
```

## 🔧 File di Configurazione

### `com.chrome.devtools.json`
Mapping automatico per Chrome DevTools:
```json
{
  "version": 1,
  "mappings": [
    {
      "type": "folder",
      "folder": "D:/XAMPP/htdocs/sito_modulare",
      "url": "http://localhost/sito_modulare"
    }
  ]
}
```

**Funzione**: Permette a Chrome DevTools di salvare modifiche CSS direttamente sui file SCSS locali.

## 📝 Note Importanti

- **Prima volta**: `npm install` per installare dipendenze
- **XAMPP**: Deve essere attivo su porta 80
- **Porte**: Browser-sync usa 3000 (sito) e 3001 (UI)
- **File watch**: SCSS, JS, PHP, immagini

---

**Pronto per sviluppare!** 🎉
