# 🔧 Fix Applicati - Page Builder

## 📋 Panoramica
Questo documento raccoglie tutti i fix tecnici applicati al Page Builder, organizzati per categoria e con dettagli implementativi.

## 🐛 Fix JavaScript

### 1. Template Literal Bug
**File**: `admin/page-builder.php`
**Data**: 2025-01-XX
**Problema**: `ReferenceError: temp is not defined`

#### Causa
Uso di template literal JavaScript (`${variable}`) dentro stringhe HTML generate da PHP, causando interpolazione errata.

#### Soluzione
Conversione da template literal a concatenazione JavaScript:
```javascript
// ❌ PRIMA (ERRATO)
onclick="applySelectedTemplate(${instanceId})"

// ✅ DOPO (CORRETTO)
onclick="applySelectedTemplate(' + instanceId + ')"
```

#### File Modificati
- Linea ~1447: `applySelectedTemplate` onclick
- Linea ~1452: `saveAsTemplate` onclick
- Linea ~1407: `editGlobalTemplate` onclick
- Linea ~1410: `detachFromTemplate` onclick

### 2. Doppie Chiamate Event Listeners
**File**: `admin/page-builder.php`
**Data**: 2025-01-XX
**Problema**: `updateModulePreview` chiamato due volte su template-select

#### Causa
`attachConfigListeners()` attaccava listener anche su `#template-select`, causando doppie chiamate.

#### Soluzione
Esclusione `#template-select` da `attachConfigListeners`:
```javascript
// Esclude template-select per evitare doppie chiamate
const inputs = container.querySelectorAll('input, textarea, select:not(#template-select)');
```

## 🎯 Fix Funzionalità

### 3. Ordinamento Moduli Non Persistente
**File**: `admin/page-builder.php`
**Data**: 2025-01-XX
**Problema**: Stage ≠ Preview ≠ Pagina Reale

#### Causa
`order_index` non veniva salvato automaticamente dopo ogni modifica.

#### Soluzione
Aggiunta chiamate `updateOrder()` in 5 punti critici:
1. **Dopo aggiunta modulo**: `addModuleToCanvas()`
2. **Dopo eliminazione**: `deleteInstance()`
3. **Dopo salvataggio**: `saveInstance()`
4. **Dopo auto-save**: `autoSaveNewModule()`
5. **Drag & Drop**: `Sortable.onEnd`

#### Implementazione
```javascript
function updateOrder() {
    console.log(`🔄 Aggiornamento ordinamento: ${instances.length} moduli trovati`);
    
    instances.forEach((instance, index) => {
        console.log(`  📍 Posizione ${index}: ${moduleName} [ID: ${instanceId}]`);
    });
    
    // Salvataggio ordinamento nel database
    fetch('', {
        method: 'POST',
        body: formData
    });
}
```

### 4. Percorsi Immagini in Anteprima
**File**: `admin/page-builder.php`
**Data**: 2025-01-XX
**Problema**: Immagini non visualizzate nell'anteprima Page Builder

#### Causa
Percorsi relativi (`assets/images/...`) risolti da `admin/page-builder.php` invece che dalla root.

#### Soluzione
MutationObserver per correzione automatica percorsi:
```javascript
function fixImagePaths(container) {
    const basePath = '../';
    
    // Corregge tag <img>
    const images = container.querySelectorAll('img');
    images.forEach(img => {
        const src = img.getAttribute('src');
        if (!src.startsWith('http') && !src.startsWith('../')) {
            img.setAttribute('src', basePath + src);
        }
    });
    
    // Corregge background-image inline
    const elementsWithBg = container.querySelectorAll('[style*="background-image"]');
    elementsWithBg.forEach(el => {
        const style = el.getAttribute('style');
        const correctedStyle = style.replace(/url\(['"]?([^'"]*)['"]?\)/g, (match, url) => {
            if (!url.startsWith('http') && !url.startsWith('../')) {
                return `url('${basePath}${url}')`;
            }
            return match;
        });
        el.setAttribute('style', correctedStyle);
    });
}
```

### 5. Anteprima Template Globali
**File**: `admin/page-builder.php`
**Data**: 2025-01-XX
**Problema**: Anteprima non mostrava valori del template globale

#### Causa
`get_module_preview` usava solo config inviata dal form, non considerava template globali.

#### Soluzione
Modifica endpoint per leggere config dal template master:
```php
case 'get_module_preview':
    $instanceId = (int)$_POST['instance_id'] ?? null;
    
    if ($instanceId) {
        $stmt = $db->prepare("SELECT mi.*, template.config as tpl_config 
            FROM module_instances mi
            LEFT JOIN module_instances template ON mi.template_instance_id = template.id
            WHERE mi.id = ?");
        $stmt->execute([$instanceId]);
        $instance = $stmt->fetch();
        
        if ($instance['template_instance_id']) {
            $config = json_decode($instance['tpl_config'], true) ?? [];
        } else {
            $config = json_decode($_POST['config'], true);
        }
    }
```

## 🗄️ Fix Database

### 6. Colonna 'page_id' Cannot be Null
**File**: `database/add_module_templates.sql`
**Data**: 2025-01-XX
**Problema**: Template master richiede `page_id = NULL`

#### Causa
Colonna `page_id` era `NOT NULL`, ma template master instances richiedono `page_id = NULL`.

#### Soluzione
```sql
ALTER TABLE module_instances 
MODIFY COLUMN page_id INT DEFAULT NULL;
```

### 7. Undefined Variable $db
**File**: `admin/install-templates.php`
**Data**: 2025-01-XX
**Problema**: `Call to a member function query() on null`

#### Causa
Variabile `$db` non inizializzata nel file di installazione.

#### Soluzione
```php
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
```

## 🎨 Fix UI/UX

### 8. Auto-Save Moduli Nuovi
**File**: `admin/page-builder.php`
**Data**: 2025-01-XX
**Problema**: Moduli nuovi non immediatamente disponibili per template globali

#### Causa
Moduli aggiunti avevano solo ID temporaneo, non persistevano nel database.

#### Soluzione
Auto-save automatico con configurazione default:
```javascript
function autoSaveNewModule() {
    const moduleName = selectedInstance.getAttribute('data-module-name');
    const instanceName = selectedInstance.getAttribute('data-instance-name');
    
    // Fetch default config
    const formData = new FormData();
    formData.append('action', 'get_module_config');
    formData.append('module_name', moduleName);
    
    fetch('', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            // Salva con config default
            const saveData = new FormData();
            saveData.append('action', 'save_instance');
            saveData.append('instance_id', 'temp');
            saveData.append('config', JSON.stringify(data.config));
            
            return fetch('', { method: 'POST', body: saveData });
        })
        .then(response => response.json())
        .then(result => {
            // Aggiorna DOM con ID reale
            selectedInstance.setAttribute('data-instance-id', result.instance_id);
            selectedInstance.id = result.instance_id;
        });
}
```

## 📊 Risultati

### Prima dei Fix
- ❌ Errori JavaScript su template globali
- ❌ Ordinamento non persistente
- ❌ Immagini non visualizzate
- ❌ Anteprima template non funzionante
- ❌ Workflow modelli globali incompleto

### Dopo i Fix
- ✅ Sistema template globali completamente funzionante
- ✅ Ordinamento sempre sincronizzato
- ✅ Immagini visualizzate correttamente
- ✅ Anteprima real-time funzionante
- ✅ Workflow completo: crea → applica → modifica → stacca

## 🧪 Testing

### Test Applicati
1. **Template Globali**: Creazione, applicazione, modifica, stacco
2. **Ordinamento**: Drag & drop, auto-save, persistenza
3. **Immagini**: Percorsi relativi, background-image, URL assoluti
4. **Anteprima**: Real-time, template globali, configurazioni
5. **JavaScript**: Nessun errore console, eventi corretti

### Browser Testati
- Chrome 120+ ✅
- Firefox 120+ ✅
- Safari 17+ ✅
- Edge 120+ ✅

## 📚 Riferimenti

### File Modificati
- `admin/page-builder.php` - Fix principali
- `admin/install-templates.php` - Fix installazione
- `database/add_module_templates.sql` - Schema template

### Guide Correlate
- `PAGE-BUILDER.md` - Guida completa Page Builder
- `TROUBLESHOOTING.md` - Risoluzione problemi
- `modules/docs/TEMPLATES-SYSTEM.md` - Sistema modelli

---

**Fix Page Builder - Sistema Modulare Bologna Marathon** 🔧

*Documentazione tecnica per sviluppatori e AI models*
