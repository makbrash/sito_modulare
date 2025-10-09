# 🚨 Troubleshooting - Page Builder

## 🎯 Panoramica
Questa guida raccoglie i problemi più comuni del Page Builder e le relative soluzioni, organizzate per categoria e livello di difficoltà.

## 🐛 Errori JavaScript

### ReferenceError: temp is not defined
**Sintomi**: Errore console quando clicchi "Applica Modello Selezionato"
```
Uncaught ReferenceError: temp is not defined
at HTMLButtonElement.onclick (page-builder.php:1:23)
```

**Causa**: Template literal JavaScript malformato in HTML generato da PHP

**Soluzione**:
1. Verifica che il fix sia applicato in `admin/page-builder.php`
2. Ricarica la pagina con Ctrl+F5 (hard refresh)
3. Controlla che non ci sia cache PHP attiva

**Prevenzione**: Usa sempre concatenazione JavaScript in HTML generato da PHP

### Doppie chiamate su template-select
**Sintomi**: `updateModulePreview` chiamato due volte quando selezioni un template
```
Field changed: template-select 234
// Chiamata duplicata
Field changed: template-select 234
```

**Causa**: Event listener duplicato su `#template-select`

**Soluzione**: Il fix è già applicato - `#template-select` è escluso da `attachConfigListeners`

### Modulo non inizializzato
**Sintomi**: JavaScript del modulo non funziona, errori console
```
Cannot read property 'init' of undefined
```

**Causa**: Modulo non registrato correttamente o file JS mancante

**Soluzione**:
1. Verifica `module.json` - campo `assets.js` presente
2. Controlla che il file JS esista in `modules/nome-modulo/`
3. Verifica registrazione in `modules_registry` database

## 🎨 Problemi UI/UX

### Ordinamento moduli non persistente
**Sintomi**: Stage ≠ Preview ≠ Pagina Reale

**Causa**: `order_index` non salvato automaticamente

**Soluzione**:
1. Verifica che `updateOrder()` sia chiamato dopo ogni modifica
2. Controlla console per log "🔄 Aggiornamento ordinamento"
3. Testa drag & drop per verificare persistenza

### Immagini non visualizzate in anteprima
**Sintomi**: Immagini mostrano placeholder o 404 in Page Builder

**Causa**: Percorsi relativi risolti da `admin/` invece che root

**Soluzione**:
1. Il MutationObserver corregge automaticamente i percorsi
2. Verifica console per log "Percorso immagine corretto"
3. Se persiste, controlla che l'immagine esista realmente

### Anteprima non aggiornata
**Sintomi**: Modifiche ai parametri non si riflettono nell'anteprima

**Causa**: Event listeners non attaccati o config non raccolta

**Soluzione**:
1. Verifica che `attachConfigListeners()` sia chiamato dopo rendering
2. Controlla che i campi abbiano ID corretti nel `ui_schema`
3. Testa con modulo semplice per isolare il problema

## 🗄️ Problemi Database

### "Istanza non trovata (ID: 0)"
**Sintomi**: Errore quando salvi come modello globale
```
🔧 Risposta save_as_template: {success: false, error: 'Istanza non trovata (ID: 0)'}
```

**Causa**: `instanceId` passato come stringa invece che numero

**Soluzione**:
1. Verifica che il fix template literal sia applicato
2. Controlla che `data-instance-id` sia impostato correttamente
3. Verifica che l'istanza esista nel database

### "Column 'page_id' cannot be null"
**Sintomi**: Errore durante creazione template globale

**Causa**: Schema database non aggiornato per template system

**Soluzione**:
1. Esegui `database/add_module_templates.sql`
2. Verifica che `page_id` sia `DEFAULT NULL` in `module_instances`
3. Riavvia MySQL se necessario

### Modulo non visibile in Page Builder
**Sintomi**: Modulo non appare nel pannello moduli disponibili

**Causa**: Modulo non registrato in `modules_registry`

**Soluzione**:
1. Usa `admin/sync-modules.php` per registrazione automatica
2. Verifica manualmente in `modules_registry` che `is_active = 1`
3. Controlla che `component_path` sia corretto

## 🎯 Problemi Template Globali

### Template non applicato
**Sintomi**: Click "Applica Modello" non ha effetto

**Causa**: Configurazione non salvata prima dell'applicazione

**Soluzione**:
1. Salva sempre la configurazione con "Salva Configurazione"
2. Verifica che il template selezionato esista
3. Controlla console per errori AJAX

### Anteprima template non corrisponde
**Sintomi**: Anteprima Page Builder ≠ "Vedi Pagina"

**Causa**: `get_module_preview` non legge config dal template master

**Soluzione**: Il fix è già applicato - anteprima ora usa config del template

### Template non condiviso
**Sintomi**: Modifiche template non si riflettono su altre pagine

**Causa**: Template non collegato correttamente

**Soluzione**:
1. Verifica che `template_instance_id` sia impostato
2. Controlla che il template master esista e sia `is_template = TRUE`
3. Testa modifica template master

## 🔧 Problemi Performance

### Page Builder lento
**Sintomi**: Interfaccia rallenta con molti moduli

**Causa**: Troppi event listeners o DOM queries inefficienti

**Soluzione**:
1. Verifica che event listeners siano rimossi correttamente
2. Usa `querySelector` specifico invece di query generiche
3. Considera lazy loading per moduli complessi

### Memory leak
**Sintomi**: Browser rallenta dopo uso prolungato

**Causa**: Event listeners non rimossi o oggetti non liberati

**Soluzione**:
1. Implementa `destroy()` method nei moduli JS
2. Rimuovi event listeners quando moduli sono eliminati
3. Usa `WeakMap` per riferimenti deboli

## 🧪 Debug e Diagnostica

### Console Debug
```javascript
// Verifica stato modulo selezionato
const instance = document.querySelector('.module-instance.selected-instance');
console.log('Modulo selezionato:', instance);

// Verifica template selezionato
const select = document.getElementById('template-select');
console.log('Template selezionato:', select.value);

// Debug istanze database
debugInstances();
```

### Network Debug
1. Apri DevTools → Network
2. Filtra per XHR/Fetch
3. Verifica richieste AJAX del Page Builder
4. Controlla response per errori

### Database Debug
```sql
-- Verifica moduli registrati
SELECT * FROM modules_registry WHERE is_active = 1;

-- Verifica istanze pagina
SELECT * FROM module_instances WHERE page_id = ?;

-- Verifica template globali
SELECT * FROM module_instances WHERE is_template = TRUE;
```

## 📋 Checklist Diagnostica

### Problema JavaScript
- [ ] Console aperta e errori visibili
- [ ] Hard refresh (Ctrl+F5) applicato
- [ ] Cache browser pulita
- [ ] PHP cache disabilitata

### Problema Database
- [ ] Connessione database funzionante
- [ ] Schema aggiornato per template system
- [ ] Permessi database corretti
- [ ] Log MySQL controllati

### Problema UI
- [ ] Browser supportato (Chrome/Firefox/Safari/Edge)
- [ ] JavaScript abilitato
- [ ] CSS caricato correttamente
- [ ] Responsive design testato

## 🆘 Escalation

### Quando Contattare Sviluppatore
- Errori JavaScript persistenti dopo fix
- Problemi database non risolvibili
- Performance degradate
- Funzionalità core non funzionanti

### Informazioni da Fornire
1. **Browser e versione**
2. **Screenshot errori console**
3. **Passi per riprodurre**
4. **Log database se applicabile**
5. **Configurazione sistema**

## 📚 Riferimenti

### File di Supporto
- `admin/docs/FIXES.md` - Fix tecnici applicati
- `admin/docs/PAGE-BUILDER.md` - Guida completa
- `modules/docs/DEVELOPMENT-GUIDE.md` - Sviluppo moduli

### Strumenti Debug
- `admin/sync-modules.php` - Sincronizzazione moduli
- `admin/test-setup.php` - Setup database
- `debug.php` - Debug generale sistema

---

**Troubleshooting Page Builder - Sistema Modulare Bologna Marathon** 🚨

*Guida completa per risoluzione problemi*
