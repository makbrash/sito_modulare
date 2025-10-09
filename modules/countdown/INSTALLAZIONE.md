# 🚀 Guida Installazione Rapida - Modulo Countdown

## ⚡ Setup Veloce (3 minuti)

### 1. Registra il Modulo nel Database

```bash
# Opzione A: Via MySQL
mysql -u root -p sito_modulare < modules/countdown/install.sql

# Opzione B: Via browser (consigliato)
http://localhost/sito_modulare/admin/sync-modules.php
```

### 2. Verifica Installazione

```bash
# Testa il modulo
http://localhost/sito_modulare/test-countdown.html
```

### 3. Usa nel Tuo Progetto

```php
// index.php - Dopo l'hero
<?php
$renderer->renderModule('hero', $heroConfig);

// Countdown Banner
$renderer->renderModule('countdown', [
    'variant' => 'banner',
    'target_date' => '2026-03-01T09:00:00',
    'subtitle' => 'Mancano solo',
    'show_logos' => true
]);

$renderer->renderModule('presentation', $presentationConfig);
?>
```

## 🎯 Quick Examples

### Esempio 1: Banner Semplice
```php
$renderer->renderModule('countdown', [
    'variant' => 'banner',
    'target_date' => '2026-03-01T09:00:00'
]);
```

### Esempio 2: Card con Titolo
```php
$renderer->renderModule('countdown', [
    'variant' => 'card',
    'target_date' => '2026-03-01T09:00:00',
    'title' => 'PROSSIMA GARA',
    'subtitle' => 'Inizia tra'
]);
```

### Esempio 3: Tematizzato
```php
$renderer->renderModule('countdown', [
    'variant' => 'banner',
    'target_date' => '2026-03-01T09:00:00',
    'title' => '30KM DEI PORTICI',
    'theme_class' => 'theme-portici',
    'logo_1' => 'assets/images/sponsor/main-sponsor.png'
]);
```

## 🔧 Requisiti Sistema

- ✅ PHP 8.0+
- ✅ MySQL/MariaDB
- ✅ JavaScript abilitato
- ✅ `app.js` caricato nella pagina

## 📂 Struttura Files

```
modules/countdown/
├── countdown.php       # Template modulo ✅
├── countdown.css       # Stili completi ✅
├── module.json         # Manifest ✅
├── install.sql         # Setup database ✅
├── README.md          # Documentazione completa ✅
└── INSTALLAZIONE.md   # Questa guida ✅
```

## 🎨 Varianti Disponibili

### 1. Banner (`variant: "banner"`)
- ✅ Full-width
- ✅ Loghi laterali
- ✅ Separatore sezioni

### 2. Card (`variant: "card"`)
- ✅ Card sovrapposta
- ✅ Effetto depth
- ✅ Border colorato

## 🌈 Temi Colore

```php
// Marathon (Azzurro)
'theme_class' => 'theme-marathon'

// 30K Portici (Rosa)
'theme_class' => 'theme-portici'

// 5K (Giallo)
'theme_class' => 'theme-5k'

// Kids Run (Turchese)
'theme_class' => 'theme-kidsrun'

// Run Tune Up (Verde)
'theme_class' => 'theme-run-tune-up'
```

## 📱 Test Responsive

```bash
# Apri nel browser
http://localhost/sito_modulare/test-countdown.html

# Testa su:
- Desktop (>992px) ✅
- Tablet (768-992px) ✅
- Mobile (480-768px) ✅
- Mobile Small (<480px) ✅
```

## 🐛 Troubleshooting Express

### Countdown non si aggiorna?
```javascript
// Verifica che app.js sia caricato
console.log(window.bolognaMarathon);
```

### Stili non applicati?
```html
<!-- Verifica che CSS sia incluso -->
<link rel="stylesheet" href="modules/countdown/countdown.css">
```

### Loghi non appaiono?
```php
// Solo per variante banner
'variant' => 'banner',
'show_logos' => true,
'logo_1' => 'path/corretto/logo.svg'
```

## 📞 Support

- 📖 **Documentazione Completa**: `modules/countdown/README.md`
- 🧪 **File Test**: `test-countdown.html`
- 💻 **Esempi**: `modules/README.md`

## ✅ Checklist Installazione

- [ ] File modulo presenti in `modules/countdown/`
- [ ] Modulo registrato nel database
- [ ] `app.js` incluso nella pagina
- [ ] CSS incluso nella pagina
- [ ] Test countdown visualizzato correttamente

---

**Ready to go!** 🚀

Modulo installato e funzionante in 3 minuti.

