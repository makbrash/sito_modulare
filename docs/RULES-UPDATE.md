# 🚨 Aggiornamento Regole - Bologna Marathon

## ✅ Regole Aggiunte

### 📋 Data Aggiornamento
**Data**: 2025-01-09
**Versione**: 2.0.0

### 🎯 Obiettivo
Aggiungere regole chiare e vincolanti per:
1. **Eliminare hardcoding** e codice spaghetti
2. **Gestire correttamente** la documentazione
3. **Mantenere separazione** CSS/JS/PHP

## 🚨 NUOVE REGOLE FONDAMENTALI

### 1. NO HARDCODING
**Regola**: MAI valori hardcoded nel codice

**Cosa significa**:
- ❌ NO colori hardcoded: `<div style="color: #23a8eb">`
- ❌ NO URL hardcoded: `$url = "https://example.com"`
- ❌ NO stringhe hardcoded: `$message = "Errore generico"`
- ✅ USA CSS Variables: `color: var(--primary)`
- ✅ USA configurazioni: `$url = config('api.url')`
- ✅ USA file di lingua: `$message = __('errors.generic')`

### 2. NO CODICE SPAGHETTI
**Regola**: Separa SEMPRE CSS, JS e logica PHP

**Cosa significa**:
- ❌ NO CSS inline: `<div style="padding: 20px">`
- ❌ NO JavaScript inline: `<button onclick="doStuff()">`
- ❌ NO logica in template: Query SQL dirette in `.php`
- ✅ CSS in file `.css` separati
- ✅ JavaScript in file `.js` separati
- ✅ Logica in `ModuleRenderer.php` o classi dedicate

### 3. SEPARAZIONE DELLE RESPONSABILITÀ
**Regola**: Ogni file ha UNA responsabilità

**Struttura obbligatoria**:
```
module.php     → SOLO template HTML + variabili PHP
module.css     → TUTTI gli stili del modulo
module.js      → TUTTA la logica JavaScript
module.json    → Configurazione e manifest
```

**❌ MAI mescolare**:
```php
// ❌ PESSIMO ESEMPIO - Tutto mescolato
<div>
    <style>.hero { color: red; }</style>
    <script>function doStuff() {}</script>
    <?php $db = new PDO(...); ?>
</div>
```

## 📚 GESTIONE DOCUMENTAZIONE

### Regole Aggiunte

#### 1. Organizzazione File `.md`
- ✅ Usa struttura gerarchica: `docs/`, `admin/docs/`, `modules/docs/`
- ✅ Nomenclatura chiara: `PAGE-BUILDER.md` non `PB.md`
- ✅ Posiziona in cartelle strategiche per categoria
- ✅ Mantieni un solo `README.md` per cartella

#### 2. Eliminazione File Obsoleti
- ✅ Elimina file dopo consolidamento
- ✅ Elimina fix temporanei dopo risoluzione
- ✅ Mantieni MAX 1 file per argomento
- ❌ MAI accumulare `FIX-*.md` o `QUICK-*.md`

#### 3. Modifica vs Creazione
- ✅ **PRIMA** cerca file esistente simile
- ✅ **AGGIORNA** file esistente se possibile
- ✅ **CONSOLIDA** informazioni duplicate
- ✅ **CREA nuovo** solo se argomento diverso
- ❌ **MAI** creare `FILE-v2.md`, `FILE-final.md`

#### 4. Aggiornamento Riferimenti
- ✅ Aggiorna `README.md` quando sposti file
- ✅ Aggiorna `.cursorrules` quando cambi struttura
- ✅ Aggiorna link interni nei file `.md`
- ✅ Verifica che tutti i link funzionino

## 📝 FILE CREATI/MODIFICATI

### File Creati
1. **`docs/CODING-STANDARDS.md`** - Standard di codifica completi
2. **`docs/RULES-UPDATE.md`** - Questo file

### File Modificati
1. **`.cursorrules`** - Aggiunte regole fondamentali
2. **`docs/README.md`** - Aggiunto riferimento a CODING-STANDARDS

### Sezioni Aggiunte a `.cursorrules`

#### 🚨 REGOLE FONDAMENTALI
- ❌ ASSOLUTAMENTE VIETATO (hardcoding, spaghetti code)
- ✅ REGOLE OBBLIGATORIE (CSS, JavaScript, PHP)
- 📝 Esempi Pratici (codice buono vs cattivo)

#### 📚 REGOLE GESTIONE DOCUMENTAZIONE
- ✅ Organizzazione file `.md`
- ✅ Eliminazione file obsoleti
- ✅ Modifica vs creazione
- ✅ Aggiornamento riferimenti
- 📝 Template documentazione standard

#### ⚠️ CHECKLIST PRE-COMMIT
- 🚨 Codice Pulito
- 📚 Documentazione
- 🧹 Pulizia

## 🎯 ENFASI AGGIUNTA

### Nei File
1. **`.cursorrules`**:
   - Sezione "🚨 REGOLE FONDAMENTALI" all'inizio
   - Esempi pratici con codice ❌ cattivo vs ✅ buono
   - Checklist pre-commit

2. **`docs/CODING-STANDARDS.md`**:
   - Documento completo con esempi
   - Standard per PHP, CSS, JavaScript
   - Regole database
   - Gestione documentazione

3. **`docs/README.md`**:
   - Link prominente a CODING-STANDARDS
   - "🚨 INIZIA QUI" per sviluppatori

## 🚀 COME USARE LE NUOVE REGOLE

### Per Sviluppatori
1. **Prima di scrivere codice**: Leggi `docs/CODING-STANDARDS.md`
2. **Durante sviluppo**: Segui esempi pratici
3. **Prima del commit**: Usa checklist in `.cursorrules`
4. **Dopo il commit**: Verifica che tutto sia pulito

### Per AI Models
1. **Sempre consulta**: `.cursorrules` per regole generali
2. **Per codice**: `docs/CODING-STANDARDS.md` per standard
3. **Per moduli**: `modules/docs/DEVELOPMENT-GUIDE.md`
4. **Per troubleshooting**: `admin/docs/TROUBLESHOOTING.md`

### Per Code Review
Verifica che il codice rispetti:
- [ ] ❌ Nessun hardcoding
- [ ] ❌ Nessun codice spaghetti
- [ ] ✅ Separazione CSS/JS/PHP
- [ ] ✅ CSS Variables usate
- [ ] ✅ Prepared statements per database
- [ ] ✅ Sanitizzazione output
- [ ] ✅ Documentazione organizzata

## 📊 IMPATTO

### Prima delle Regole
- ⚠️ Hardcoding frequente
- ⚠️ CSS/JS inline sparsi
- ⚠️ Logica mista nei template
- ⚠️ Documentazione disorganizzata
- ⚠️ File obsoleti accumulati

### Dopo le Regole
- ✅ NO hardcoding - tutto configurabile
- ✅ Separazione netta CSS/JS/PHP
- ✅ Template puliti e leggibili
- ✅ Documentazione organizzata
- ✅ File consolidati e puliti

## 🎓 Esempi Chiave

### Esempio 1: Template Pulito
```php
// ✅ CORRETTO
<?php
$moduleData = $renderer->getModuleData('hero', $config);
$title = htmlspecialchars($config['title'] ?? 'Default');
?>
<div class="hero hero--primary">
    <h1 class="hero__title"><?= $title ?></h1>
</div>
```

### Esempio 2: CSS Separato
```css
/* ✅ CORRETTO - hero.css */
.hero {
    color: var(--primary);
    padding: var(--spacing-lg);
}
```

### Esempio 3: JavaScript Separato
```javascript
// ✅ CORRETTO - hero.js
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

## ✅ CHECKLIST COMPLETAMENTO

- [x] Regole hardcoding aggiunte
- [x] Regole codice spaghetti aggiunte
- [x] Esempi pratici aggiunti
- [x] Regole documentazione aggiunte
- [x] Checklist pre-commit aggiunta
- [x] File CODING-STANDARDS creato
- [x] .cursorrules aggiornato
- [x] docs/README.md aggiornato
- [x] Riferimenti aggiornati

## 📚 Riferimenti

### File Aggiornati
- `.cursorrules` - Regole complete
- `docs/CODING-STANDARDS.md` - Standard codifica
- `docs/README.md` - Panoramica documentazione

### File Correlati
- `modules/docs/DEVELOPMENT-GUIDE.md` - Sviluppo moduli
- `admin/docs/PAGE-BUILDER.md` - Page Builder
- `database/docs/SCHEMA-REFERENCE.md` - Database

---

**Aggiornamento Regole - Sistema Modulare Bologna Marathon** 🚨

*Versione 2.0.0 - 2025-01-09*

**Regole ora chiarissime**: NO hardcoding, NO spaghetti code, separazione delle responsabilità, documentazione organizzata.
