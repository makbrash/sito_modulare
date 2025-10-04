# 🔄 Workflow CSS Development

## ❌ Il Problema
- Browser carica `main.min.css` (CSS compilato)
- Cursor modifica `_menu.scss` (SCSS sorgente)
- Le modifiche nel browser non si sincronizzano con il file SCSS

## ✅ La Soluzione

### 1. Avvia File Watcher
```bash
npm run watch
```
Questo comando:
- Monitora i file SCSS
- Ricompila automaticamente quando salvi
- Aggiorna `main.min.css`

### 2. Workflow Corretto

**Passo 1:** Modifica nel browser
- F12 → Elements → Styles
- Modifica CSS direttamente
- Vedi le modifiche in tempo reale

**Passo 2:** Copia nel file SCSS
- Copia il CSS modificato dal browser
- Incolla in `assets/scss/modules/_menu.scss`
- **Salva il file**

**Passo 3:** Auto-compilazione
- `npm run watch` ricompila automaticamente
- `main.min.css` viene aggiornato
- Il browser mostra le modifiche permanenti

### 3. Verifica
1. Modifica nel browser → Vedi cambiamento
2. Copia in SCSS → Salva
3. Refresh browser → Modifica permanente

## 🎯 Comandi

```bash
# Avvia watcher (una volta)
npm run watch

# Build manuale (se necessario)
npm run build
```

## 📁 File Coinvolti

```
assets/scss/modules/_menu.scss     # Modifichi questo
           ↓ (compilazione)
assets/dist/css/main.min.css       # Browser carica questo
```

## ⚠️ Importante
- `npm run watch` deve essere attivo
- Salva sempre il file SCSS dopo le modifiche
- Refresh il browser per vedere le modifiche permanenti

---

**Ora le modifiche nel browser si sincronizzano con i file SCSS!** 🎉
