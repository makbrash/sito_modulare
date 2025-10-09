# ⚡ Quick Reference - Regole Fondamentali

## 🚨 REGOLA #1: NO HARDCODING

### ❌ SBAGLIATO
```php
<div style="color: #23a8eb; padding: 20px;">
$apiUrl = "https://api.example.com";
$timeout = 5000;
```

### ✅ CORRETTO
```php
<div class="hero" style="color: var(--primary); padding: var(--spacing-lg);">
$apiUrl = config('api.url');
$timeout = config('api.timeout');
```

---

## 🚨 REGOLA #2: NO CODICE SPAGHETTI

### ❌ SBAGLIATO (tutto in un file)
```php
<!-- hero.php -->
<div>
    <style>
        .hero { color: red; }
    </style>
    <script>
        function doStuff() { alert('hi'); }
    </script>
    <?php
        $db = new PDO(...);
        $results = $db->query("SELECT * FROM users");
    ?>
</div>
```

### ✅ CORRETTO (file separati)
```php
// hero.php - SOLO template
<?php
$moduleData = $renderer->getModuleData('hero', $config);
$title = htmlspecialchars($config['title'] ?? 'Default');
?>
<div class="hero">
    <h1 class="hero__title"><?= $title ?></h1>
</div>
```

```css
/* hero.css - SOLO stili */
.hero {
    color: var(--primary);
    padding: var(--spacing-lg);
}
```

```javascript
// hero.js - SOLO logica
class Hero {
    constructor(element) {
        this.element = element;
        this.bindEvents();
    }
}
```

---

## 🚨 REGOLA #3: NO CSS/JS INLINE

### ❌ SBAGLIATO
```html
<div style="padding: 20px; color: red;">
<button onclick="alert('click')">Click</button>
```

### ✅ CORRETTO
```html
<div class="hero hero--primary">
<button class="hero__button" data-action="cta">Click</button>
```

---

## 🚨 REGOLA #4: SEPARAZIONE RESPONSABILITÀ

### File Structure
```
✅ CORRETTO:
module.php     → Template HTML + variabili PHP
module.css     → TUTTI gli stili
module.js      → TUTTA la logica JavaScript
module.json    → Configurazione

❌ SBAGLIATO:
module.php     → HTML + CSS + JS + logica + database
```

---

## 📚 REGOLA #5: DOCUMENTAZIONE ORGANIZZATA

### ❌ SBAGLIATO
```
FIX-1.md
FIX-2.md
FIX-final.md
QUICK-FIX.md
PAGE-BUILDER-v2.md
GUIDE-new.md
```

### ✅ CORRETTO
```
docs/
├── CODING-STANDARDS.md
admin/docs/
├── PAGE-BUILDER.md
├── FIXES.md (consolidato)
modules/docs/
├── DEVELOPMENT-GUIDE.md
database/docs/
├── SCHEMA-REFERENCE.md
```

### Workflow Documentazione
```
1. Cerco file esistente simile
2. Se esiste → AGGIORNO file esistente
3. Se non esiste → CREO nuovo file
4. Aggiorno riferimenti in README.md e .cursorrules
5. Elimino file obsoleti
```

---

## ✅ CHECKLIST RAPIDA

### Prima di Scrivere Codice
- [ ] Letto `CODING-STANDARDS.md`
- [ ] Capito separazione CSS/JS/PHP
- [ ] Capito NO hardcoding
- [ ] Capito NO inline styles/scripts

### Durante Sviluppo
- [ ] CSS in file `.css` separato
- [ ] JavaScript in file `.js` separato
- [ ] Template PHP pulito (solo HTML + variabili)
- [ ] CSS Variables per colori/spacing
- [ ] Prepared statements per database

### Prima del Commit
- [ ] Nessun hardcoding
- [ ] Nessun CSS/JS inline
- [ ] File separati per CSS/JS/PHP
- [ ] Documentazione aggiornata
- [ ] File obsoleti eliminati
- [ ] Riferimenti aggiornati

---

## 📖 Link Rapidi

### Documentazione Completa
- 🚨 **Standard obbligatori**: `docs/CODING-STANDARDS.md`
- 📚 **Regole complete**: `.cursorrules`
- 🧩 **Sviluppo moduli**: `modules/docs/DEVELOPMENT-GUIDE.md`
- 🎨 **Page Builder**: `admin/docs/PAGE-BUILDER.md`

### Troubleshooting
- 🚨 **Problemi admin**: `admin/docs/TROUBLESHOOTING.md`
- 🔧 **Fix applicati**: `admin/docs/FIXES.md`
- 🗄️ **Database**: `database/docs/SCHEMA-REFERENCE.md`

---

**Quick Reference - Sistema Modulare Bologna Marathon** ⚡

*Le 5 regole fondamentali che DEVI conoscere*
