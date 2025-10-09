# 🎨 Guida Visuale - Sistema Bologna Marathon

## 🚨 LE 5 REGOLE FONDAMENTALI

### 1️⃣ NO HARDCODING
```
❌ <div style="color: #23a8eb">
✅ <div class="hero" style="color: var(--primary)">

❌ $url = "https://example.com";
✅ $url = config('api.url');
```

### 2️⃣ NO CODICE SPAGHETTI
```
❌ hero.php:
   <style>.hero { color: red; }</style>
   <script>alert('hi');</script>
   <?php $db = new PDO(...); ?>

✅ hero.php     → Template
   hero.css     → Stili
   hero.js      → Logica
```

### 3️⃣ NO CSS/JS INLINE
```
❌ <div style="padding: 20px">
❌ <button onclick="alert('hi')">

✅ <div class="hero hero--primary">
✅ <button class="hero__button" data-action="cta">
```

### 4️⃣ SEPARAZIONE RESPONSABILITÀ
```
hero.php    → 📄 Solo template HTML + variabili
hero.css    → 🎨 TUTTI gli stili
hero.js     → ⚙️ TUTTA la logica JavaScript
module.json → ⚙️ Configurazione
```

### 5️⃣ DOCUMENTAZIONE ORGANIZZATA
```
docs/          → Sistema generale
admin/docs/    → Admin specifico
modules/docs/  → Moduli
database/docs/ → Database
```

---

## 📁 STRUTTURA FILE MODULO

### ✅ STRUTTURA CORRETTA
```
modules/hero/
├── hero.php              ← Template pulito
├── hero.css              ← Tutti gli stili
├── hero.js               ← Tutta la logica
├── module.json           ← Configurazione
├── install.sql           ← Setup database
└── README.md             ← Documentazione
```

### File Contenuti

#### hero.php - Template Pulito
```php
<?php
$moduleData = $renderer->getModuleData('hero', $config);
$title = htmlspecialchars($config['title'] ?? 'Default');
?>
<div class="hero hero--primary">
    <h1 class="hero__title"><?= $title ?></h1>
</div>
```

#### hero.css - Tutti gli Stili
```css
.hero {
    color: var(--primary);
    padding: var(--spacing-lg);
}
.hero__title {
    font-size: 2.5rem;
}
```

#### hero.js - Tutta la Logica
```javascript
class Hero {
    constructor(element) {
        this.element = element;
        this.bindEvents();
    }
    bindEvents() {
        this.element.addEventListener('click', this.handleClick.bind(this));
    }
}
```

---

## 🎨 CSS VARIABLES SYSTEM

### Colori
```css
/* ✅ USA SEMPRE le variabili */
.hero {
    color: var(--primary);           /* #23a8eb */
    background: var(--color-white);  /* #ffffff */
    border: 1px solid var(--border-color);
}

/* ❌ MAI hardcoded */
.hero {
    color: #23a8eb;                  /* NO! */
    background: #ffffff;             /* NO! */
}
```

### Spacing
```css
/* ✅ CORRETTO */
.hero {
    padding: var(--spacing-lg);      /* 1.5rem */
    margin: var(--spacing-md);       /* 1rem */
}

/* ❌ SBAGLIATO */
.hero {
    padding: 1.5rem;                 /* NO! */
    margin: 16px;                    /* NO! */
}
```

### Typography
```css
/* ✅ CORRETTO */
.hero__title {
    font-family: var(--font-display);
    font-size: var(--font-size-xxl);
    line-height: var(--line-height-tight);
}

/* ❌ SBAGLIATO */
.hero__title {
    font-family: 'Bebas Neue';       /* NO! */
    font-size: 2.5rem;               /* NO! */
}
```

---

## 🔧 DATABASE QUERIES

### ✅ CORRETTO - Prepared Statements
```php
// In ModuleRenderer.php
private function getHeroData($config) {
    $stmt = $this->db->prepare("
        SELECT * FROM heroes 
        WHERE id = ? AND status = ?
    ");
    $stmt->execute([$config['hero_id'], 'published']);
    return $stmt->fetch();
}

// Nel template
$moduleData = $renderer->getModuleData('hero', $config);
```

### ❌ SBAGLIATO - Query Dirette
```php
// ❌ MAI fare questo nel template
<?php
$db = new PDO(...);
$query = "SELECT * FROM heroes WHERE id = " . $_GET['id'];
$result = $db->query($query);
?>
```

---

## 🎯 WORKFLOW SVILUPPO

### Crea Nuovo Modulo
```
1. mkdir modules/mio-modulo/
2. Crea file base:
   - mio-modulo.php
   - mio-modulo.css
   - mio-modulo.js
   - module.json
3. Implementa template pulito
4. Stili in CSS (no inline)
5. Logica in JS (no inline)
6. Testa in Page Builder
7. Registra in database
```

### Checklist Pre-Commit
```
[ ] ❌ Nessun hardcoding
[ ] ❌ Nessun CSS inline
[ ] ❌ Nessun JavaScript inline
[ ] ❌ Nessuna query SQL in template
[ ] ✅ File separati: PHP + CSS + JS
[ ] ✅ CSS Variables usate
[ ] ✅ Prepared statements
[ ] ✅ Sanitizzazione output
```

---

## 📚 NAVIGAZIONE RAPIDA

### START HERE (5 minuti)
```
1. README.md (2 min)
   ↓
2. docs/QUICK-REFERENCE.md (2 min)
   ↓
3. docs/CODING-STANDARDS.md (10 min)
   ↓
4. Inizia a sviluppare! 🚀
```

### Per Moduli (20 minuti)
```
1. modules/docs/DEVELOPMENT-GUIDE.md (20 min)
   ↓
2. modules/README.md (esempi)
   ↓
3. Studia moduli esistenti
   ↓
4. Crea il tuo modulo! 🧩
```

### Per Page Builder (15 minuti)
```
1. admin/docs/PAGE-BUILDER.md (15 min)
   ↓
2. admin/page-builder.php (prova)
   ↓
3. Configura e testa! 🎨
```

---

## 🎯 ESEMPI VISIVI

### Template PHP
```php
✅ CORRETTO:
<?php
$title = htmlspecialchars($config['title'] ?? 'Default');
?>
<div class="hero">
    <h1><?= $title ?></h1>
</div>

❌ SBAGLIATO:
<div style="color: red">
    <style>.hero { padding: 20px; }</style>
    <script>alert('hi');</script>
    <?php
        $db = new PDO(...);
        echo "Titolo: $title";
    ?>
</div>
```

### CSS
```css
✅ CORRETTO:
.hero {
    color: var(--primary);
    padding: var(--spacing-lg);
}
.hero:hover {
    background: var(--primary-light);
}

❌ SBAGLIATO:
.hero {
    color: #23a8eb;           /* hardcoded */
    padding: 20px;            /* hardcoded */
    &:hover {                 /* nested */
        background: blue;
    }
}
```

### JavaScript
```javascript
✅ CORRETTO (hero.js):
class Hero {
    constructor(element) {
        this.element = element;
        this.bindEvents();
    }
    bindEvents() {
        this.element.addEventListener('click', 
            this.handleClick.bind(this)
        );
    }
}

❌ SBAGLIATO (inline in PHP):
<script>
    function doStuff() {
        alert('hi');
    }
</script>
<button onclick="doStuff()">
```

---

## 🗺️ MAPPA DOCUMENTAZIONE

### Sistema Generale
```
docs/
├─ 📖 README.md                    Panoramica
├─ ⚡ QUICK-REFERENCE.md           5 regole (2 min)
├─ 🚨 CODING-STANDARDS.md          Standard (10 min)
├─ 🔧 BUILD-SYSTEM.md              Build/deploy
├─ 📐 LAYOUT-SYSTEM.md             Layout
└─ 🎨 THEME-SYSTEM-FINAL.md        Temi
```

### Admin
```
admin/docs/
├─ 🎨 PAGE-BUILDER.md              Guida completa
├─ 🔧 FIXES.md                     Fix tecnici
└─ 🚨 TROUBLESHOOTING.md           Problemi
```

### Moduli
```
modules/docs/
├─ 🧩 DEVELOPMENT-GUIDE.md         Sviluppo
└─ 🎯 TEMPLATES-SYSTEM.md          Template globali
```

### Database
```
database/docs/
├─ 🗄️ SCHEMA-REFERENCE.md          Schema completo
└─ 🔄 MIGRATIONS.md                Migrazioni
```

---

## ✅ CHECKLIST RAPIDA

### Prima di Iniziare
- [ ] Letto README.md
- [ ] Letto QUICK-REFERENCE.md
- [ ] Letto CODING-STANDARDS.md

### Durante Sviluppo
- [ ] CSS in file .css separato
- [ ] JS in file .js separato
- [ ] Nessun hardcoding
- [ ] CSS Variables usate
- [ ] Prepared statements per DB

### Prima del Commit
- [ ] Nessun CSS/JS inline
- [ ] File separati verificati
- [ ] Documentazione aggiornata
- [ ] File obsoleti eliminati

---

**Guida Visuale - Sistema Modulare Bologna Marathon** 🎨

*Le regole fondamentali in formato visuale e facile da ricordare*
