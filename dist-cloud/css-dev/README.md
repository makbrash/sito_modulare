# 🎨 CSS Development Tool

Tool semplice per modificare CSS visualmente nel browser.

## 🚀 Come usare

### 1. Avvia XAMPP
- Assicurati che Apache sia attivo

### 2. Apri setup
- Apri `css-dev/setup.html` nel browser
- Clicca "Apri Bologna Marathon"

### 3. Modifica CSS
1. **F12** → Developer Tools
2. **Elements** → Seleziona elemento
3. **Styles** → Modifica CSS direttamente
4. Le modifiche sono immediate!

### 4. Salva modifiche
1. Copia CSS modificato dal browser
2. Apri file SCSS in Cursor
3. Incolla modifiche
4. Salva
5. `npm run build` per compilare
6. Aggiorna pagina

## 📁 File SCSS

```
assets/scss/modules/_menu.scss      # Menu
assets/scss/modules/_hero.scss     # Hero section  
assets/scss/modules/_button.scss   # Pulsanti
assets/scss/modules/_results.scss  # Risultati
assets/scss/core/_variables.scss   # Variabili CSS
```

## ⚠️ Importante

- Le modifiche nel browser sono **temporanee**
- Devi sempre salvarle nei file SCSS per renderle **permanenti**

## 🔄 Flusso completo

```
Browser → F12 → Modifica CSS → Copia → Incolla in SCSS → Salva → npm run build → Aggiorna pagina
```

## 📍 URL

- Setup: `css-dev/setup.html`
- Sito: `http://localhost/BM_layout/sito_modulare/index.php`

---

**Semplice, veloce, funziona!** 🎯
