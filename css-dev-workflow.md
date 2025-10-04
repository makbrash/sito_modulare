# CSS Development Workflow

## 🚀 Comandi Principali

### `npm run build`
- **Cosa fa**: Compila tutti i file SCSS in CSS minificato
- **Quando usare**: Prima di caricare sul server, per vedere le modifiche
- **Output**: `assets/dist/css/main.min.css`

### `npm run watch`
- **Cosa fa**: Monitora i file SCSS e li ricompila automaticamente
- **Quando usare**: Durante lo sviluppo per vedere le modifiche in tempo reale
- **Come funziona**: Modifica un file `.scss` → salva → CSS si aggiorna automaticamente

## 📁 Struttura File

```
assets/
├── scss/                    # File sorgente (quelli che modifichi)
│   ├── main.scss           # File principale
│   ├── core/               # Variabili, reset, tipografia
│   └── modules/            # Stili dei moduli
│       ├── _menu.scss      # Stili menu
│       ├── _hero.scss      # Stili hero
│       └── ...
└── dist/css/               # File compilati (generati automaticamente)
    ├── main.min.css        # CSS finale minificato
    └── main.min.css.map    # Source map per debug
```

## 🔧 Workflow di Sviluppo

### 1. Sviluppo Normale
```bash
# Avvia il watcher
npm run watch

# Modifica i file SCSS
# Salva il file
# Il CSS si aggiorna automaticamente
# Aggiorna il browser
```

### 2. Se il Watcher Non Funziona
```bash
# Compilazione manuale
npm run build

# Aggiorna il browser
```

### 3. Prima del Deploy
```bash
# Build finale
npm run build

# Copia la cartella dist/ sul server
```

## 🎯 File da Modificare

- **Colori e variabili**: `assets/scss/core/_variables.scss`
- **Stili menu**: `assets/scss/modules/_menu.scss`
- **Stili hero**: `assets/scss/modules/_hero.scss`
- **Altri moduli**: `assets/scss/modules/_nome-modulo.scss`

## ⚠️ Note Importanti

1. **Non modificare** i file in `assets/dist/` (sono generati automaticamente)
2. **Modifica sempre** i file in `assets/scss/`
3. **Salva sempre** il file dopo le modifiche
4. **Aggiorna il browser** per vedere le modifiche
5. **Source maps** permettono di debuggare i file SCSS originali nel browser

## 🐛 Risoluzione Problemi

### Il CSS non si aggiorna
1. Controlla che il watcher sia attivo
2. Salva il file SCSS
3. Controlla il terminale per messaggi di errore
4. Usa `npm run build` come fallback

### Errori di compilazione
1. Controlla la sintassi SCSS
2. Verifica che tutti gli `@import` siano corretti
3. Controlla il terminale per messaggi di errore

### Source maps nel browser
1. Apri DevTools (F12)
2. Vai su Sources
3. Trova `webpack://` o `sass://`
4. Modifica direttamente i file SCSS nel browser
5. **Attenzione**: Le modifiche nel browser non vengono salvate!

## 📝 Esempio Pratico

```bash
# 1. Avvia il watcher
npm run watch

# 2. Modifica assets/scss/modules/_menu.scss
# Aggiungi: .menu-test { color: red; }

# 3. Salva il file (Ctrl+S)

# 4. Nel terminale vedi:
# 📝 SCSS file changed: assets/scss/modules/_menu.scss
# 🔨 Building CSS from SCSS...
# ✅ CSS build completed!

# 5. Aggiorna il browser
# 6. Vedi le modifiche applicate
```

## 🚀 Deploy

```bash
# Build finale
npm run build

# La cartella assets/dist/ contiene tutto il necessario
# Copia sul server web
```

---

**Suggerimento**: Tieni sempre aperto il terminale con `npm run watch` durante lo sviluppo!
