# 🎯 Sistema Modelli Globali - Guida Completa

## 🎯 Panoramica
Il Sistema Modelli Globali permette di creare istanze master di moduli che possono essere condivise tra più pagine, modificandole una volta e applicando le modifiche ovunque.

## 🏗️ Architettura

### Concetto Base
- **Modello Master**: Unica istanza nel database con `is_template = TRUE`
- **Istanze Collegate**: Riferiscono al master tramite `template_instance_id`
- **Modifica Globale**: Cambi al master si applicano a tutte le istanze
- **Stacco**: Possibilità di convertire in istanza locale indipendente

### Database Schema
```sql
-- Colonne aggiunte a module_instances
ALTER TABLE module_instances ADD COLUMN is_template BOOLEAN DEFAULT FALSE;
ALTER TABLE module_instances ADD COLUMN template_name VARCHAR(100);
ALTER TABLE module_instances ADD COLUMN template_instance_id INT DEFAULT NULL;
ALTER TABLE module_instances MODIFY COLUMN page_id INT DEFAULT NULL;

-- Indici per performance
ALTER TABLE module_instances ADD INDEX idx_is_template (is_template);
ALTER TABLE module_instances ADD INDEX idx_template_instance (template_instance_id);
```

## 🔄 Workflow Utente

### 1. Creazione Modello Globale
```
1. Aggiungi modulo alla pagina
2. Configura parametri desiderati
3. Click "Salva come Modello Globale"
4. Inserisci nome del modello
5. Modello creato e disponibile per altre pagine
```

### 2. Applicazione Modello
```
1. Aggiungi modulo alla pagina
2. Seleziona modello da dropdown
3. Click "Applica Modello Selezionato"
4. Modulo collegato al modello master
5. Configurazione sincronizzata
```

### 3. Modifica Modello Globale
```
1. Seleziona modulo collegato al modello
2. Click "Modifica Modello Generale"
3. Modifica parametri
4. Salva configurazione
5. Modifiche applicate a tutte le pagine
```

### 4. Stacco da Modello
```
1. Seleziona modulo collegato al modello
2. Click "Salva come Modulo di Pagina"
3. Modulo diventa istanza locale
4. Configurazione indipendente dal modello
```

## 🎨 UI/UX

### Badge Template Attivo
```html
<div class="template-badge template-badge--active">
    <i class="fas fa-link"></i>
    <strong>Modello Globale:</strong> Nome Modello
    <p>Questo modulo è collegato a un modello condiviso</p>
</div>
```

### Azioni Template
```html
<div class="template-actions">
    <button onclick="editGlobalTemplate(templateId, 'moduleName')">
        <i class="fas fa-edit"></i> Modifica Modello Generale
    </button>
    <button onclick="detachFromTemplate(instanceId)">
        <i class="fas fa-unlink"></i> Salva come Modulo di Pagina
    </button>
</div>
```

### Anteprima Configurazione
```html
<div class="template-preview">
    <strong>Anteprima Configurazione:</strong>
    <div>
        <small>Titolo:</small>
        <div>Valore attuale</div>
    </div>
</div>
```

## 🔧 Implementazione Tecnica

### Endpoint AJAX

#### save_as_template
```php
case 'save_as_template':
    $instanceId = (int)$_POST['instance_id'];
    $templateName = $_POST['template_name'];
    
    // Recupera istanza esistente
    $stmt = $db->prepare("SELECT * FROM module_instances WHERE id = ?");
    $stmt->execute([$instanceId]);
    $instance = $stmt->fetch();
    
    // Crea template master
    $stmt = $db->prepare("INSERT INTO module_instances 
        (module_name, config, is_template, template_name, page_id) 
        VALUES (?, ?, TRUE, ?, NULL)");
    $stmt->execute([$instance['module_name'], $instance['config'], $templateName]);
    
    $templateId = $db->lastInsertId();
    
    // Collega istanza originale al template
    $stmt = $db->prepare("UPDATE module_instances 
        SET template_instance_id = ? WHERE id = ?");
    $stmt->execute([$templateId, $instanceId]);
    
    echo json_encode(['success' => true, 'template_id' => $templateId]);
```

#### apply_template
```php
case 'apply_template':
    $instanceId = (int)$_POST['instance_id'];
    $templateId = (int)$_POST['template_id'];
    
    // Recupera config del template
    $stmt = $db->prepare("SELECT config FROM module_instances WHERE id = ? AND is_template = TRUE");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch();
    
    // Aggiorna istanza con config del template
    $stmt = $db->prepare("UPDATE module_instances 
        SET config = ?, template_instance_id = ? WHERE id = ?");
    $stmt->execute([$template['config'], $templateId, $instanceId]);
    
    echo json_encode(['success' => true]);
```

#### update_template
```php
case 'update_template':
    $templateId = (int)$_POST['template_id'];
    $config = $_POST['config'];
    
    // Aggiorna template master
    $stmt = $db->prepare("UPDATE module_instances 
        SET config = ? WHERE id = ? AND is_template = TRUE");
    $stmt->execute([$config, $templateId]);
    
    // Aggiorna tutte le istanze collegate
    $stmt = $db->prepare("UPDATE module_instances 
        SET config = ? WHERE template_instance_id = ?");
    $stmt->execute([$config, $templateId]);
    
    echo json_encode(['success' => true]);
```

#### detach_from_template
```php
case 'detach_from_template':
    $instanceId = (int)$_POST['instance_id'];
    
    // Rimuovi collegamento al template
    $stmt = $db->prepare("UPDATE module_instances 
        SET template_instance_id = NULL WHERE id = ?");
    $stmt->execute([$instanceId]);
    
    echo json_encode(['success' => true]);
```

### JavaScript Integration

#### Gestione Template Selector
```javascript
function loadAvailableTemplates(moduleName) {
    const formData = new FormData();
    formData.append('action', 'get_templates');
    formData.append('module_name', moduleName);
    
    fetch('', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('template-select');
            select.innerHTML = '<option value="">-- Seleziona modello --</option>';
            
            data.templates.forEach(template => {
                const option = document.createElement('option');
                option.value = template.id;
                option.textContent = template.template_name;
                select.appendChild(option);
            });
        });
}
```

#### Salvataggio come Template
```javascript
function saveAsTemplate(instanceId, moduleName) {
    const templateName = prompt('Inserisci nome del modello:');
    if (!templateName) return;
    
    const formData = new FormData();
    formData.append('action', 'save_as_template');
    formData.append('instance_id', instanceId);
    formData.append('template_name', templateName);
    
    fetch('', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Modello creato con successo!');
                location.reload(); // Ricarica per aggiornare UI
            } else {
                alert('Errore: ' + data.error);
            }
        });
}
```

#### Applicazione Template
```javascript
function applySelectedTemplate(instanceId) {
    const select = document.getElementById('template-select');
    const templateId = select.value;
    
    if (!templateId) {
        alert('Seleziona un modello');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'apply_template');
    formData.append('instance_id', instanceId);
    formData.append('template_id', templateId);
    
    fetch('', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Modello applicato con successo!');
                location.reload();
            } else {
                alert('Errore: ' + data.error);
            }
        });
}
```

## 🎯 Rendering e Anteprima

### Logica Rendering
Il sistema deve distinguere tra:
- **Moduli normali**: Usano `config` locale
- **Moduli con template**: Usano `config` del template master

### Nel ModuleRenderer.php
```php
public function getModuleInstance($instanceId) {
    $stmt = $this->db->prepare("
        SELECT mi.*, template.config as template_config
        FROM module_instances mi
        LEFT JOIN module_instances template ON mi.template_instance_id = template.id
        WHERE mi.id = ?
    ");
    $stmt->execute([$instanceId]);
    $instance = $stmt->fetch();
    
    if ($instance && $instance['template_instance_id']) {
        // Usa config del template master
        $instance['config'] = $instance['template_config'];
    }
    
    return $instance;
}
```

### Nel Page Builder Preview
```php
case 'get_module_preview':
    $instanceId = (int)$_POST['instance_id'] ?? null;
    
    if ($instanceId) {
        // Recupera config corretta (locale o template)
        $instance = $renderer->getModuleInstance($instanceId);
        $config = json_decode($instance['config'], true);
    } else {
        $config = json_decode($_POST['config'], true);
    }
    
    $output = $renderer->renderModule($moduleName, $config);
    echo json_encode(['success' => true, 'html' => $output]);
```

## 🧪 Testing

### Test Funzionalità
1. **Creazione modello**: Verifica inserimento database
2. **Applicazione modello**: Controlla collegamento istanza
3. **Modifica globale**: Verifica propagazione modifiche
4. **Stacco modello**: Controlla indipendenza istanza
5. **Anteprima**: Verifica rendering corretto

### Test Database
```sql
-- Verifica template creati
SELECT * FROM module_instances WHERE is_template = TRUE;

-- Verifica istanze collegate
SELECT * FROM module_instances WHERE template_instance_id IS NOT NULL;

-- Verifica configurazioni sincronizzate
SELECT mi.id, mi.config, t.config as template_config
FROM module_instances mi
JOIN module_instances t ON mi.template_instance_id = t.id
WHERE mi.config != t.config; -- Dovrebbe essere vuoto
```

## 🚨 Troubleshooting

### Problemi Comuni

#### Template non applicato
**Causa**: Configurazione non salvata prima dell'applicazione
**Soluzione**: Salva sempre la configurazione con "Salva Configurazione"

#### Modifiche non propagate
**Causa**: Template master non aggiornato correttamente
**Soluzione**: Verifica che `update_template` aggiorni sia master che istanze

#### Anteprima non corretta
**Causa**: `get_module_preview` non legge config dal template
**Soluzione**: Implementare logica template nel preview endpoint

#### Performance degradata
**Causa**: Troppi JOIN nel rendering
**Soluzione**: Cache template config e ottimizza query

## 📊 Performance

### Ottimizzazioni
1. **Cache template config**: Evita query ripetute
2. **Indici database**: Per `is_template` e `template_instance_id`
3. **Lazy loading**: Carica template solo quando necessario
4. **Batch updates**: Aggiorna multiple istanze in una query

### Monitoraggio
```sql
-- Query lente da monitorare
EXPLAIN SELECT mi.*, template.config 
FROM module_instances mi
LEFT JOIN module_instances template ON mi.template_instance_id = template.id
WHERE mi.page_id = ?;
```

## 📚 Riferimenti

### File Principali
- `admin/page-builder.php` - UI e AJAX endpoints
- `core/ModuleRenderer.php` - Logica rendering
- `database/add_module_templates.sql` - Schema database

### Guide Correlate
- `DEVELOPMENT-GUIDE.md` - Sviluppo moduli
- `../admin/docs/PAGE-BUILDER.md` - Page Builder completo
- `../admin/docs/FIXES.md` - Fix applicati

---

**Sistema Modelli Globali - Sistema Modulare Bologna Marathon** 🎯

*Guida completa per sviluppatori e AI models*
