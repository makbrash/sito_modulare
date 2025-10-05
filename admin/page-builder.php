<?php
/**
 * Page Builder - Interfaccia amministrativa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/ModuleRenderer.php';

$database = new Database();
$db = $database->getConnection();
$renderer = new ModuleRenderer($db);

$pageId = (int)($_GET['page_id'] ?? 0);

$pagesStmt = $db->query('SELECT id, title, slug FROM pages ORDER BY title');
$pages = $pagesStmt->fetchAll() ?: [];

if ($pageId <= 0 && count($pages) > 0) {
    $pageId = (int)$pages[0]['id'];
}

$currentPage = null;
if ($pageId > 0) {
    $pageStmt = $db->prepare('SELECT id, title, slug FROM pages WHERE id = ?');
    $pageStmt->execute([$pageId]);
    $currentPage = $pageStmt->fetch();
}

$modulesStmt = $db->query('SELECT name FROM modules_registry WHERE is_active = 1 ORDER BY name');
$availableModules = [];
while ($module = $modulesStmt->fetch()) {
    $manifest = $renderer->getModuleManifest($module['name']) ?? [];
    $availableModules[] = [
        'name' => $module['name'],
        'label' => $manifest['name'] ?? $module['name'],
        'description' => $manifest['description'] ?? '',
        'category' => $manifest['category'] ?? 'Generico',
        'has_ui' => !empty($manifest['ui_schema']),
        'tags' => $manifest['tags'] ?? [],
    ];
}

$instances = [];
if ($pageId > 0) {
    $instancesStmt = $db->prepare('SELECT id, module_name, instance_name, config, order_index, is_active FROM module_instances WHERE page_id = ? ORDER BY order_index');
    $instancesStmt->execute([$pageId]);
    while ($instance = $instancesStmt->fetch()) {
        if ((int)$instance['is_active'] !== 1) {
            continue;
        }
        $config = json_decode($instance['config'], true) ?? [];
        $merged = $renderer->mergeConfigWithDefaults($instance['module_name'], $config);
        $html = '';
        try {
            $html = $renderer->renderModule($instance['module_name'], $merged);
        } catch (Throwable $exception) {
            $html = '<div class="pb-module-error">' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
        }

        $instances[] = [
            'id' => (int)$instance['id'],
            'module' => $instance['module_name'],
            'instance_name' => $instance['instance_name'],
            'order_index' => (int)$instance['order_index'],
            'html' => $html,
        ];
    }
}

$initialPayload = [
    'pages' => $pages,
    'currentPageId' => $pageId,
    'currentPage' => $currentPage,
    'moduleInstances' => $instances,
    'availableModules' => $availableModules,
];

$initialJson = htmlspecialchars(json_encode($initialPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Builder - Bologna Marathon</title>
    
    <!-- CSS: usa core CSS in admin per maggiore stabilità -->
    <link rel="stylesheet" href="../assets/css/core/variables.css">
    <link rel="stylesheet" href="../assets/css/core/reset.css">
    <link rel="stylesheet" href="../assets/css/core/typography.css">
    <link rel="stylesheet" href="../assets/css/core/fonts.css">
    
    <!-- Font Awesome per icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .page-builder {
            display: grid;
            grid-template-columns: 300px 1fr 300px;
            height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        .sidebar {
            background: #ffffff;
            border-right: 1px solid #dee2e6;
            padding: 1rem;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 2px 0 4px rgba(0,0,0,0.1);
            max-width: 300px;
            box-sizing: border-box;
        }
        
        .sidebar h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.5rem;
        }
        
        .sidebar p {
            color: #495057;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .main-content {
            display: flex;
            flex-direction: column;
            background: white;
        }
        
        .page-canvas {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            overflow-x: hidden;
            background: #f5f5f5;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .module-instance {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 1rem;
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .module-instance:hover {
            border-color: #007bff;
            box-shadow: 0 4px 12px rgba(0,123,255,0.15);
        }
        
        .module-instance:hover .module-header {
            opacity: 1;
            transform: translateY(0);
        }
        
        .module-header {
            background: rgba(0,123,255,0.9);
            color: white;
            padding: 0.5rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: move;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10;
            opacity: 0;
            transform: translateY(-100%);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            z-index: 1000000;
        }
        
        .module-content {
            padding: 0;
            min-height: 80px;
            max-width: 100%;
            overflow: hidden;
        }
        
        .module-content * {
            max-width: 100% !important;
            box-sizing: border-box !important;
        }
        
        .module-controls {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-small {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-edit { background: #28a745; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-preview { background: #007bff; color: white; }
        
        .btn-small:hover { 
            opacity: 0.9; 
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .module-list {
            margin-bottom: 2rem;
        }
        
        .module-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #495057;
            font-weight: 500;
        }
        
        .module-item:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(0,123,255,0.3);
        }
        
        .module-item i {
            color: #6c757d;
            transition: color 0.3s ease;
        }
        
        .module-item:hover i {
            color: white;
        }
        
        .config-panel {
            background: #ffffff;
            border-left: 1px solid #dee2e6;
            padding: 1rem;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: -2px 0 4px rgba(0,0,0,0.1);
            max-width: 300px;
            box-sizing: border-box;
        }
        
        .config-panel h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.25rem;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.9rem;
            background: white;
            color: #495057;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
        
        .drag-placeholder {
            border: 2px dashed #007bff;
            background: rgba(0,123,255,0.1);
            border-radius: 8px;
            height: 100px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #007bff;
            font-weight: 500;
        }
        
        .selected-instance {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 3px rgba(40,167,69,0.25);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .page-selector {
            margin-bottom: 1rem;
        }
        
        .page-selector select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.9rem;
            background: white;
            color: #495057;
            font-weight: 500;
        }
        
        .page-selector select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        .preview-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .preview-content {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            max-width: 80%;
            max-height: 80%;
            overflow: auto;
            position: relative;
        }
        
        .preview-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        /* Stili per campi array dinamici */
        .array-field {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
        }
        
        .array-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .btn-add {
            background: #28a745;
            color: white;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .array-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            overflow: hidden;
        }
        
        .array-item-header {
            background: #e9ecef;
            padding: 0.5rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
            color: #495057;
        }
        
        .array-item-fields {
            padding: 1rem;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: normal;
            margin-bottom: 0;
        }
        
        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="page-builder">
        <!-- Sidebar Sinistra - Pagine e Moduli -->
        <div class="sidebar">
            <h3><i class="fas fa-edit"></i> Page Builder</h3>
            <?php if ($currentPage): ?>
                <p><strong><?= htmlspecialchars($currentPage['title']) ?></strong></p>
                <p>ID: <?= $currentPage['id'] ?></p>
            <?php endif; ?>
            
            <div class="page-selector">
                <select id="page-selector" onchange="loadPage(this.value)">
                    <?php foreach ($pages as $page): ?>
                        <option value="<?= $page['id'] ?>" <?= $page['id'] == $pageId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($page['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="margin: 1rem 0;">
                <button class="btn-small btn-preview" onclick="previewPage()" style="width: 100%; margin-bottom: 0.5rem;">
                    <i class="fas fa-eye"></i> Anteprima
                </button>
                <a href="../index.php?id_pagina=<?= $pageId ?>" target="_blank" class="btn-small btn-preview" style="width: 100%; display: block; text-align: center; margin-bottom: 0.5rem;">
                    <i class="fas fa-external-link-alt"></i> Vedi Pagina
                </a>
            </div>
            
            <h3><i class="fas fa-puzzle-piece"></i> Moduli Disponibili</h3>
            <div class="module-list">
                <?php foreach ($availableModules as $module): ?>
                    <div class="module-item" data-module="<?= htmlspecialchars($module['name']) ?>" 
                         onclick="addModule('<?= htmlspecialchars($module['name']) ?>')">
                        <span>
                            <i class="fas fa-cube"></i>
                            <?= htmlspecialchars($module['name']) ?>
                        </span>
                        <i class="fas fa-plus"></i>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Contenuto Principale -->
        <div class="main-content">
            <div class="page-canvas" id="page-canvas">
                <?php if (empty($moduleInstances)): ?>
                    <div class="empty-state">
                        <i class="fas fa-plus-circle"></i>
                        <h3>Nessun modulo aggiunto</h3>
                        <p>Trascina i moduli dalla barra laterale per iniziare a costruire la pagina</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($moduleInstances as $instance): ?>
                        <div class="module-instance" data-instance-id="<?= $instance['id'] ?>" 
                             data-module-name="<?= htmlspecialchars($instance['module_name']) ?>"
                             data-instance-name="<?= htmlspecialchars($instance['instance_name']) ?>">
                            <div class="module-header">
                                <div>
                                    <i class="fas fa-cube"></i>
                                    <?= htmlspecialchars($instance['module_name']) ?> - <?= htmlspecialchars($instance['instance_name']) ?>
                                </div>
                                <div class="module-controls">
                                    <button class="btn-small btn-preview" data-action="preview" data-instance-id="<?= $instance['id'] ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-small btn-edit" data-action="edit" data-instance-id="<?= $instance['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-small btn-delete" data-action="delete" data-instance-id="<?= $instance['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="module-content">
                                <?php
                                try {
                                    $config = json_decode($instance['config'], true) ?? [];
                                    echo $renderer->renderModule($instance['module_name'], $config);
                                } catch (Exception $e) {
                                    echo '<div style="color: #dc3545; padding: 1rem;">Errore rendering: ' . htmlspecialchars($e->getMessage()) . '</div>';
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sidebar Destra - Configurazione -->
        <div class="config-panel" id="config-panel">
            <h3><i class="fas fa-cog"></i> Configurazione</h3>
            <div id="config-content">
                <p>Seleziona un modulo per configurarlo</p>
            </div>
        </div>
    </div>
    
    <!-- Modal Anteprima -->
    <div class="preview-modal" id="preview-modal">
        <div class="preview-content">
            <button class="preview-close" onclick="closePreview()">&times;</button>
            <div id="preview-body"></div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="../node_modules/sortablejs/Sortable.min.js"></script>
    <script>
        let currentPageId = <?= $pageId ?>;
        let selectedInstance = null;
        let instanceCounter = {};
        
        // Utility per debounce
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // Inizializza drag & drop
        const canvas = document.getElementById('page-canvas');
        const sortable = Sortable.create(canvas, {
            animation: 150,
            ghostClass: 'drag-placeholder',
            onEnd: function(evt) {
                updateOrder();
            }
        });
        
        // Inizializza contatori moduli
        function initializeModuleCounters() {
            const instances = document.querySelectorAll('.module-instance');
            instanceCounter = {};
            
            console.log('Inizializzazione contatori per', instances.length, 'moduli');
            
            instances.forEach((instance, index) => {
                const moduleName = instance.getAttribute('data-module-name');
                const instanceName = instance.getAttribute('data-instance-name');
                const instanceId = instance.getAttribute('data-instance-id');
                
                console.log(`Modulo ${index + 1}: ${moduleName} - ${instanceName} (ID: ${instanceId})`);
                
                if (moduleName && instanceName) {
                    if (!instanceCounter[moduleName]) {
                        instanceCounter[moduleName] = 0;
                    }
                    
                    // Estrai numero dal nome istanza (es. button_2 -> 2)
                    const match = instanceName.match(/_(\d+)$/);
                    if (match) {
                        const num = parseInt(match[1]);
                        if (num > instanceCounter[moduleName]) {
                            instanceCounter[moduleName] = num;
                        }
                    }
                }
            });
            
            console.log('Contatori moduli aggiornati:', instanceCounter);
        }
        
        // Inizializza contatori al caricamento
        initializeModuleCounters();
        
        // Funzione per pulire moduli fantasma (moduli con attributi mancanti)
        function cleanupGhostModules() {
            const instances = document.querySelectorAll('.module-instance');
            let cleanedCount = 0;
            
            instances.forEach(instance => {
                const moduleName = instance.getAttribute('data-module-name');
                const instanceName = instance.getAttribute('data-instance-name');
                const instanceId = instance.getAttribute('data-instance-id');
                
                // Se mancano attributi essenziali, rimuovi il modulo
                if (!moduleName || !instanceName || !instanceId) {
                    console.warn('Rimosso modulo fantasma:', instance);
                    instance.remove();
                    cleanedCount++;
                }
            });
            
            if (cleanedCount > 0) {
                console.log(`Puliti ${cleanedCount} moduli fantasma`);
                // Reinizializza contatori dopo la pulizia
                initializeModuleCounters();
            }
        }
        
        // Pulisci moduli fantasma al caricamento
        setTimeout(cleanupGhostModules, 500);
        
        // Funzione per forzare il refresh dei contatori
        function forceRefreshCounters() {
            console.log('Forzando refresh contatori...');
            cleanupGhostModules();
            initializeModuleCounters();
            console.log('Refresh contatori completato');
        }
        
        // Aggiungi funzione globale per debug
        window.refreshCounters = forceRefreshCounters;
        
        // Riattacca event listener a tutti i moduli
        function reattachModuleListeners() {
            const moduleInstances = document.querySelectorAll('.module-instance');
            console.log(`Riattaccando event listener a ${moduleInstances.length} moduli`);
            
            moduleInstances.forEach((instance, index) => {
                // Rimuovi eventuali listener esistenti
                instance.removeEventListener('click', handleModuleClick);
                
                // Aggiungi nuovo listener per il modulo
                instance.addEventListener('click', handleModuleClick);
                
                // Gestisci i pulsanti di controllo
                const controlButtons = instance.querySelectorAll('.module-controls button');
                controlButtons.forEach(button => {
                    // Rimuovi eventuali listener esistenti
                    button.removeEventListener('click', handleControlButtonClick);
                    
                    // Aggiungi nuovo listener
                    button.addEventListener('click', handleControlButtonClick);
                });
                
                // Debug: verifica che l'event listener sia attivo
                const moduleName = instance.getAttribute('data-module-name');
                const instanceName = instance.getAttribute('data-instance-name');
                const instanceId = instance.getAttribute('data-instance-id');
                console.log(`Event listener riattaccato al modulo ${index + 1}: ${moduleName} (${instanceName}) [ID: ${instanceId}]`);
            });
        }
        
        // Gestisce il click sui moduli
        function handleModuleClick(event) {
            event.stopPropagation();
            console.log('Click rilevato su modulo:', this.getAttribute('data-module-name'));
            selectInstance(this);
        }
        
        // Gestisce il click sui pulsanti di controllo
        function handleControlButtonClick(event) {
            event.stopPropagation();
            
            const action = this.getAttribute('data-action');
            const instanceId = this.getAttribute('data-instance-id');
            
            console.log(`Click su pulsante ${action} per istanza ${instanceId}`);
            
            // Trova il modulo padre
            const moduleInstance = this.closest('.module-instance');
            if (!moduleInstance) {
                console.error('Modulo padre non trovato');
                return;
            }
            
            console.log('Modulo padre trovato:', moduleInstance.getAttribute('data-module-name'), moduleInstance.getAttribute('data-instance-name'));
            
            switch(action) {
                case 'preview':
                    previewInstance(instanceId);
                    break;
                case 'edit':
                    selectInstance(moduleInstance);
                    break;
                case 'delete':
                    // Per moduli temporanei, assicurati che selectedInstance sia corretto
                    if (instanceId === 'temp') {
                        selectedInstance = moduleInstance;
                        console.log('Impostato selectedInstance per eliminazione modulo temporaneo');
                    }
                    deleteInstance(instanceId);
                    break;
                default:
                    console.error('Azione sconosciuta:', action);
            }
        }
        
        // Inizializza event listener al caricamento
        reattachModuleListeners();
        
        // Riattacca event listener quando il DOM è pronto
        document.addEventListener('DOMContentLoaded', function() {
            reattachModuleListeners();
        });
        
        // Riattacca event listener anche quando la finestra è ridimensionata (per sicurezza)
        window.addEventListener('resize', function() {
            setTimeout(reattachModuleListeners, 100);
        });
        
        // Carica pagina
        function loadPage(pageId) {
            window.location.href = `?page_id=${pageId}`;
        }
        
        // Aggiungi modulo
        function addModule(moduleName) {
            if (!currentPageId) {
                alert('Seleziona prima una pagina');
                return;
            }
            
            // Verifica se esiste già un modulo temporaneo dello stesso tipo
            const existingTempModule = document.querySelector(`[data-module-name="${moduleName}"][data-instance-id="temp"]`);
            if (existingTempModule) {
                alert('Esiste già un modulo temporaneo di questo tipo. Configuralo prima di aggiungerne un altro.');
                existingTempModule.scrollIntoView({ behavior: 'smooth' });
                selectInstance(existingTempModule);
                return;
            }
            
            // Genera nome istanza unico basato sui moduli esistenti
            const existingInstances = document.querySelectorAll(`[data-module-name="${moduleName}"]`);
            let counter = 1;
            let instanceName = `${moduleName}_${counter}`;
            
            // Verifica che il nome sia davvero unico
            while (document.querySelector(`[data-instance-name="${instanceName}"]`)) {
                counter++;
                instanceName = `${moduleName}_${counter}`;
            }
            
            console.log(`Generato nome istanza: ${instanceName} per modulo ${moduleName} (contatore: ${counter})`);
            console.log(`Moduli esistenti di tipo ${moduleName}:`, existingInstances.length);
            
            // Debug: mostra tutti i moduli esistenti di questo tipo
            existingInstances.forEach((instance, index) => {
                console.log(`  Modulo ${index + 1}: ${instance.getAttribute('data-instance-name')} (ID: ${instance.getAttribute('data-instance-id')})`);
            });
            
            const orderIndex = document.querySelectorAll('.module-instance').length;
            
            // Crea elemento temporaneo
            const tempDiv = document.createElement('div');
            tempDiv.className = 'module-instance';
            tempDiv.setAttribute('data-instance-id', 'temp');
            tempDiv.setAttribute('data-module-name', moduleName);
            tempDiv.setAttribute('data-instance-name', instanceName);
            tempDiv.innerHTML = `
                <div class="module-header">
                    <div><i class="fas fa-cube"></i> ${moduleName} - ${instanceName}</div>
                    <div class="module-controls">
                        <button class="btn-small btn-edit" data-action="edit" data-instance-id="temp">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-small btn-delete" data-action="delete" data-instance-id="temp">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="module-content">
                    <div style="padding: 1rem; color: #6c757d;">
                        <i class="fas fa-cog"></i> Configura il modulo per vedere l'anteprima
                    </div>
                </div>
            `;
            
            // Rimuovi empty state se presente
            const emptyState = canvas.querySelector('.empty-state');
            if (emptyState) {
                emptyState.remove();
            }
            
            canvas.appendChild(tempDiv);
            
            // Seleziona prima il modulo
            selectInstance(tempDiv);
            
            // Riattacca event listener dopo aver aggiunto il nuovo modulo
            setTimeout(() => {
                reattachModuleListeners();
            }, 100);
            
            // NON salvare automaticamente i moduli temporanei nel database
            // Il salvataggio avverrà solo da "Salva Configurazione"
            // Manteniamo l'istanza come bozza nel DOM (data-instance-id = "temp")
        }
        
        // Seleziona istanza
        function selectInstance(element) {
            console.log('Selezionando istanza:', element.getAttribute('data-module-name'), element.getAttribute('data-instance-name'));
            
            // Rimuovi selezione precedente
            document.querySelectorAll('.module-instance').forEach(el => {
                el.classList.remove('selected-instance');
            });
            
            // Seleziona nuova
            element.classList.add('selected-instance');
            selectedInstance = element;
            
            console.log('Istanza selezionata con successo');
            
            // Carica configurazione
            loadInstanceConfig(element);
        }
        
        // Carica configurazione istanza
        function loadInstanceConfig(element) {
            const moduleName = element.getAttribute('data-module-name');
            const instanceId = element.getAttribute('data-instance-id');
            const instanceName = element.getAttribute('data-instance-name');
            
            const configPanel = document.getElementById('config-content');
            
            // Mostra loading
            configPanel.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin"></i> Caricamento configurazione...</div>';
            
            // Carica configurazione dal server
            const formData = new FormData();
            formData.append('action', 'get-module-config');
            formData.append('module_name', moduleName);
            if (instanceId && instanceId !== 'temp') {
                formData.append('instance_id', instanceId);
            }
            
            fetch('api/page_builder.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON Parse Error:', text);
                        throw new Error('Risposta non valida dal server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    renderModuleConfig(moduleName, instanceName, instanceId, data.config, data.manifest);
                } else {
                    configPanel.innerHTML = '<div style="color: #dc3545; padding: 1rem;">Errore: ' + (data.error || 'Errore sconosciuto') + '</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                configPanel.innerHTML = '<div style="color: #dc3545; padding: 1rem;">Errore durante il caricamento: ' + error.message + '</div>';
            });
        }
        
        // Renderizza configurazione modulo dinamica basata sul manifest
        function renderModuleConfig(moduleName, instanceName, instanceId, config, manifest) {
            const configPanel = document.getElementById('config-content');
            
            let html = `
                <div class="form-group">
                    <label>Nome Istanza</label>
                    <input type="text" id="instance-name" value="${instanceName}">
                </div>
            `;
            
            // Genera campi dinamicamente dal manifest
            if (manifest.ui_schema) {
                html += generateDynamicFields(manifest.ui_schema, config);
            } else {
                // Modulo senza ui_schema - usa configurazione generica
                html += `
                    <div class="form-group">
                        <label>Titolo</label>
                        <input type="text" id="config-title" value="${config.title || ''}" placeholder="Titolo del modulo">
                    </div>
                    <div class="form-group">
                        <label>Contenuto</label>
                        <textarea id="config-content" placeholder="Contenuto del modulo">${config.content || ''}</textarea>
                    </div>
                `;
            }
            
            html += `
                <button class="btn-small btn-edit" onclick="saveInstanceConfig()" style="width: 100%; margin-top: 1rem;">
                    <i class="fas fa-save"></i> Salva Configurazione
                </button>
            `;
            
            configPanel.innerHTML = html;
            
            // Aggiungi event listeners per aggiornamento in tempo reale
            setTimeout(() => {
                const inputs = configPanel.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    // Controllo speciale per il nome istanza
                    if (input.id === 'instance-name') {
                        input.addEventListener('input', debounce(() => {
                            validateInstanceName(input.value);
                        }, 300));
                    }
                    
                    input.addEventListener('input', debounce(() => {
                        try {
                            updateModulePreview();
                        } catch (e) {
                            console.warn('Preview update skipped:', e.message);
                        }
                    }, 500));
                    input.addEventListener('change', () => {
                        try {
                            updateModulePreview();
                        } catch (e) {
                            console.warn('Preview update skipped:', e.message);
                        }
                    });
                });
            }, 100);
        }
        
        // Genera campi dinamicamente dal manifest
        function generateDynamicFields(uiSchema, config) {
            let html = '';
            
            for (const [fieldName, fieldConfig] of Object.entries(uiSchema)) {
                const value = config[fieldName] || fieldConfig.default || '';
                
                html += `<div class="form-group" data-field="${fieldName}">`;
                html += `<label>${fieldConfig.label}${fieldConfig.required ? ' *' : ''}</label>`;
                
                switch (fieldConfig.type) {
                    case 'text':
                        html += `<input type="text" id="config-${fieldName}" value="${value}" placeholder="${fieldConfig.placeholder || ''}">`;
                        break;
                        
                    case 'textarea':
                        html += `<textarea id="config-${fieldName}" placeholder="${fieldConfig.placeholder || ''}">${value}</textarea>`;
                        break;
                        
                    case 'select':
                        html += `<select id="config-${fieldName}">`;
                        for (const option of fieldConfig.options) {
                            const selected = value === option.value ? 'selected' : '';
                            html += `<option value="${option.value}" ${selected}>${option.label}</option>`;
                        }
                        html += '</select>';
                        break;
                        
                    case 'boolean':
                        const checked = value ? 'checked' : '';
                        html += `<label class="checkbox-label"><input type="checkbox" id="config-${fieldName}" ${checked}> ${fieldConfig.label}</label>`;
                        break;
                        
                    case 'color':
                        html += `<input type="color" id="config-${fieldName}" value="${value}">`;
                        break;
                        
                    case 'image':
                        html += `<input type="url" id="config-${fieldName}" value="${value}" placeholder="${fieldConfig.placeholder || 'URL dell\'immagine'}">`;
                        break;
                        
                    case 'array':
                        html += generateArrayField(fieldName, fieldConfig, value);
                        break;
                        
                    default:
                        html += `<input type="text" id="config-${fieldName}" value="${value}" placeholder="${fieldConfig.placeholder || ''}">`;
                }
                
                html += '</div>';
            }
            
            return html;
        }
        
        // Genera campo array (es. voci menu)
        function generateArrayField(fieldName, fieldConfig, value) {
            const items = Array.isArray(value) ? value : [];
            
            let html = `
                <div class="array-field" id="config-${fieldName}-container">
                    <div class="array-header">
                        <span>${fieldConfig.label}</span>
                        <button type="button" class="btn-small btn-add" onclick="addArrayItem('${fieldName}')">
                            <i class="fas fa-plus"></i> Aggiungi
                        </button>
                    </div>
                    <div class="array-items" id="config-${fieldName}-items">
            `;
            
            items.forEach((item, index) => {
                html += generateArrayItem(fieldName, fieldConfig.item_schema, item, index);
            });
            
            html += `
                    </div>
                </div>
            `;
            
            return html;
        }
        
        // Genera singolo item dell'array
        function generateArrayItem(fieldName, itemSchema, item, index) {
            let html = `
                <div class="array-item" data-index="${index}">
                    <div class="array-item-header">
                        <span>Elemento ${index + 1}</span>
                        <button type="button" class="btn-small btn-delete" onclick="removeArrayItem('${fieldName}', ${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="array-item-fields">
            `;
            
            for (const [subFieldName, subFieldConfig] of Object.entries(itemSchema)) {
                const subValue = item[subFieldName] || '';
                const subFieldId = `config-${fieldName}-${index}-${subFieldName}`;
                
                html += `<div class="form-group">`;
                html += `<label>${subFieldConfig.label}</label>`;
                
                switch (subFieldConfig.type) {
                    case 'text':
                        html += `<input type="text" id="${subFieldId}" value="${subValue}" placeholder="${subFieldConfig.placeholder || ''}">`;
                        break;
                    case 'url':
                        html += `<input type="url" id="${subFieldId}" value="${subValue}" placeholder="${subFieldConfig.placeholder || ''}">`;
                        break;
                    case 'select':
                        html += `<select id="${subFieldId}">`;
                        for (const option of subFieldConfig.options) {
                            const selected = subValue === option.value ? 'selected' : '';
                            html += `<option value="${option.value}" ${selected}>${option.label}</option>`;
                        }
                        html += '</select>';
                        break;
                    default:
                        html += `<input type="text" id="${subFieldId}" value="${subValue}">`;
                }
                
                html += '</div>';
            }
            
            html += `
                    </div>
                </div>
            `;
            
            return html;
        }
        
        // Aggiungi item all'array
        function addArrayItem(fieldName) {
            const container = document.getElementById(`config-${fieldName}-items`);
            const itemSchema = getItemSchema(fieldName);
            const newIndex = container.children.length;
            
            const newItem = generateArrayItem(fieldName, itemSchema, {}, newIndex);
            container.insertAdjacentHTML('beforeend', newItem);
            
            // Riattacca event listeners
            reattachModuleListeners();
        }
        
        // Rimuovi item dall'array
        function removeArrayItem(fieldName, index) {
            const container = document.getElementById(`config-${fieldName}-items`);
            const item = container.querySelector(`[data-index="${index}"]`);
            if (item) {
                item.remove();
                
                // Rinumera gli indici
                container.querySelectorAll('.array-item').forEach((el, newIndex) => {
                    el.setAttribute('data-index', newIndex);
                    el.querySelector('.array-item-header span').textContent = `Elemento ${newIndex + 1}`;
                });
            }
        }
        
        // Ottieni schema dell'item (da implementare)
        function getItemSchema(fieldName) {
            // Per ora hardcoded per menu_items, poi si può migliorare
            return {
                label: { type: 'text', label: 'Etichetta', required: true },
                url: { type: 'url', label: 'URL', required: true },
                target: { 
                    type: 'select', 
                    label: 'Target',
                    options: [
                        { value: '_self', label: 'Stessa Finestra' },
                        { value: '_blank', label: 'Nuova Finestra' }
                    ]
                }
            };
        }
        
        // Salva configurazione istanza
        function saveInstanceConfig() {
            if (!selectedInstance) return;
            
            const moduleName = selectedInstance.getAttribute('data-module-name');
            const instanceId = selectedInstance.getAttribute('data-instance-id');
            const instanceNameEl = document.getElementById('instance-name');
            
            if (!instanceNameEl) {
                alert('Errore: campo nome istanza non trovato');
                return;
            }
            
            const instanceName = instanceNameEl.value.trim();
            if (!instanceName) {
                alert('Il nome dell\'istanza non può essere vuoto');
                return;
            }
            
            // Verifica duplicazione lato client
            const currentInstanceId = selectedInstance.getAttribute('data-instance-id');
            const existingInstances = document.querySelectorAll('.module-instance');
            let duplicateFound = false;
            
            for (let instance of existingInstances) {
                const otherInstanceId = instance.getAttribute('data-instance-id');
                const otherInstanceName = instance.getAttribute('data-instance-name');
                
                // Salta l'istanza corrente
                if (otherInstanceId === currentInstanceId) continue;
                
                // Se troviamo un'altra istanza con lo stesso nome, blocca
                if (otherInstanceName === instanceName) {
                    duplicateFound = true;
                    break;
                }
            }
            
            if (duplicateFound) {
                alert('Esiste già un\'istanza con questo nome sulla stessa pagina. Scegli un nome diverso.');
                return;
            }
            
            console.log(`Nome istanza validato: ${instanceName} per istanza ${currentInstanceId}`);
            
            // Raccogli configurazione dinamicamente
            let config = {};
            
            try {
                // Raccogli configurazione dinamicamente
                config = collectDynamicConfig();
                
                // Salva nel database
                saveInstance(moduleName, instanceName, config, null, instanceId);
                
            } catch (error) {
                console.error('Errore durante il salvataggio:', error);
                alert('Errore durante il salvataggio: ' + error.message);
            }
        }
        
        // Raccogli configurazione dinamicamente dai campi
        function collectDynamicConfig() {
            const config = {};
            const formGroups = document.querySelectorAll('.form-group[data-field]');
            
            formGroups.forEach(group => {
                const fieldName = group.getAttribute('data-field');
                const input = group.querySelector('input, select, textarea');
                
                if (input) {
                    if (input.type === 'checkbox') {
                        config[fieldName] = input.checked;
                    } else {
                        config[fieldName] = input.value;
                    }
                }
            });
            
            // Gestisci campi array (es. menu_items)
            const arrayFields = document.querySelectorAll('.array-field');
            arrayFields.forEach(arrayField => {
                const fieldName = arrayField.id.replace('config-', '').replace('-container', '');
                const items = [];
                
                arrayField.querySelectorAll('.array-item').forEach((item, index) => {
                    const itemData = {};
                    item.querySelectorAll('.form-group input, .form-group select').forEach(input => {
                        const subFieldName = input.id.split('-').pop();
                        itemData[subFieldName] = input.value;
                    });
                    items.push(itemData);
                });
                
                config[fieldName] = items;
            });
            
            return config;
        }
        
        // Salva istanza nel database
        function saveInstance(moduleName, instanceName, config, orderIndex, instanceId) {
            const formData = new FormData();
            formData.append('action', 'save-instance');
            formData.append('page_id', currentPageId);
            formData.append('module_name', moduleName);
            formData.append('instance_name', instanceName);
            formData.append('config', JSON.stringify(config));
            if (orderIndex !== null) {
                formData.append('order_index', orderIndex);
            }
            
            // Passa l'ID dell'istanza corrente se disponibile
            if (instanceId && instanceId !== 'temp') {
                formData.append('current_instance_id', instanceId);
            }
            
            // Mostra loading
            const saveButton = document.querySelector('button[onclick="saveInstanceConfig()"]');
            if (saveButton) {
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvataggio...';
                saveButton.disabled = true;
            }
            
            fetch('api/page_builder.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON Parse Error:', text);
                        throw new Error('Risposta non valida dal server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    if (instanceId === 'temp') {
                        selectedInstance.setAttribute('data-instance-id', data.id);
                        // Aggiorna il titolo dell'header
                        const headerTitle = selectedInstance.querySelector('.module-header div');
                        if (headerTitle) {
                            headerTitle.innerHTML = `<i class="fas fa-cube"></i> ${moduleName} - ${instanceName}`;
                        }
                        // Aggiorna i data-attribute dei pulsanti di controllo con il nuovo ID
                        const controlButtons = selectedInstance.querySelectorAll('.module-controls button');
                        controlButtons.forEach(btn => {
                            btn.setAttribute('data-instance-id', data.id);
                        });
                    }
                    
                    // Aggiorna preview
                    updateModulePreview();
                    
                    // Riattacca event listener dopo il salvataggio
                    setTimeout(() => {
                        reattachModuleListeners();
                    }, 100);
                    
                    // Mostra messaggio di successo
                    const configPanel = document.getElementById('config-content');
                    const successMsg = document.createElement('div');
                    successMsg.style.cssText = 'background: #d4edda; color: #155724; padding: 0.5rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.9rem;';
                    successMsg.innerHTML = '<i class="fas fa-check"></i> Configurazione salvata con successo!';
                    configPanel.insertBefore(successMsg, configPanel.firstChild);
                    
                    // Rimuovi messaggio dopo 3 secondi
                    setTimeout(() => {
                        if (successMsg.parentNode) {
                            successMsg.remove();
                        }
                    }, 3000);
                    
                } else {
                    const errorMsg = data.error || 'Errore sconosciuto';
                    
                    // Mostra errore nel pannello di configurazione se è un errore di duplicazione
                    if (errorMsg.includes('Esiste già un\'istanza')) {
                        const configPanel = document.getElementById('config-content');
                        const errorDiv = document.createElement('div');
                        errorDiv.style.cssText = 'background: #f8d7da; color: #721c24; padding: 0.5rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.9rem;';
                        errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + errorMsg;
                        configPanel.insertBefore(errorDiv, configPanel.firstChild);
                        
                        // Rimuovi messaggio dopo 5 secondi
                        setTimeout(() => {
                            if (errorDiv.parentNode) {
                                errorDiv.remove();
                            }
                        }, 5000);
                    } else {
                        alert('Errore: ' + errorMsg);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore durante il salvataggio: ' + error.message);
            })
            .finally(() => {
                // Ripristina pulsante
                if (saveButton) {
                    saveButton.innerHTML = '<i class="fas fa-save"></i> Salva Configurazione';
                    saveButton.disabled = false;
                }
            });
        }
        
        // Aggiorna ordine
        function updateOrder() {
            const instances = document.querySelectorAll('.module-instance');
            const updates = [];
            
            instances.forEach((instance, index) => {
                const instanceId = instance.getAttribute('data-instance-id');
                if (instanceId !== 'temp') {
                    updates.push({
                        id: instanceId,
                        page_id: currentPageId,
                        order_index: index
                    });
                }
            });
            
            if (updates.length === 0) return;
            
            const formData = new FormData();
            formData.append('action', 'update-order');
            formData.append('updates', JSON.stringify(updates));
            
            fetch('api/page_builder.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Errore aggiornamento ordine:', data.error);
                }
            });
        }
        
        // Elimina istanza
        function deleteInstance(instanceId) {
            if (!confirm('Sei sicuro di voler eliminare questo modulo?')) return;
            
            console.log('Tentativo eliminazione istanza:', instanceId);
            
            // Per moduli temporanei, usa l'istanza selezionata
            let instanceElement;
            if (instanceId === 'temp') {
                instanceElement = selectedInstance;
                console.log('Eliminazione modulo temporaneo, uso selectedInstance');
            } else {
                instanceElement = document.querySelector(`[data-instance-id="${instanceId}"]`);
            }
            
            if (!instanceElement) {
                console.error('Elemento modulo non trovato per ID:', instanceId);
                console.log('Moduli disponibili:');
                document.querySelectorAll('.module-instance').forEach((el, index) => {
                    console.log(`${index + 1}: ID=${el.getAttribute('data-instance-id')}, Modulo=${el.getAttribute('data-module-name')}, Nome=${el.getAttribute('data-instance-name')}`);
                });
                console.log('selectedInstance:', selectedInstance);
                return;
            }
            
            console.log('Elemento modulo trovato:', instanceElement.getAttribute('data-module-name'), instanceElement.getAttribute('data-instance-name'));
            
            if (instanceId === 'temp') {
                // Rimuovi elemento temporaneo
                instanceElement.remove();
                selectedInstance = null;
                document.getElementById('config-content').innerHTML = '<p>Seleziona un modulo per configurarlo</p>';
                
                // Riattacca event listener dopo l'eliminazione
                reattachModuleListeners();
                
                // Aggiorna contatori dopo l'eliminazione
                initializeModuleCounters();
                
                // Verifica se rimangono altri moduli
                checkEmptyState();
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete-instance');
            formData.append('instance_id', instanceId);
            formData.append('page_id', currentPageId);
            
            fetch('api/page_builder.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON Parse Error:', text);
                        throw new Error('Risposta non valida dal server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    instanceElement.remove();
                    selectedInstance = null;
                    document.getElementById('config-content').innerHTML = '<p>Seleziona un modulo per configurarlo</p>';
                    
                    // Riattacca event listener dopo l'eliminazione
                    reattachModuleListeners();
                    
                    // Aggiorna contatori dopo l'eliminazione
                    initializeModuleCounters();
                    
                    // Verifica se rimangono altri moduli
                    checkEmptyState();
                } else {
                    alert('Errore: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore durante l\'eliminazione: ' + error.message);
            });
        }
        
        // Verifica se la pagina è vuota e mostra empty state
        function checkEmptyState() {
            const canvas = document.getElementById('page-canvas');
            const instances = canvas.querySelectorAll('.module-instance');
            
            if (instances.length === 0) {
                const emptyState = document.createElement('div');
                emptyState.className = 'empty-state';
                emptyState.innerHTML = `
                    <i class="fas fa-plus-circle"></i>
                    <h3>Nessun modulo aggiunto</h3>
                    <p>Trascina i moduli dalla barra laterale per iniziare a costruire la pagina</p>
                `;
                canvas.appendChild(emptyState);
            }
        }
        
        // Valida nome istanza in tempo reale
        function validateInstanceName(instanceName) {
            if (!instanceName || !instanceName.trim()) {
                removeValidationMessage();
                return;
            }
            
            const currentInstanceId = selectedInstance?.getAttribute('data-instance-id');
            const existingInstances = document.querySelectorAll('.module-instance');
            let duplicateFound = false;
            
            for (let instance of existingInstances) {
                const otherInstanceId = instance.getAttribute('data-instance-id');
                const otherInstanceName = instance.getAttribute('data-instance-name');
                
                // Salta l'istanza corrente
                if (otherInstanceId === currentInstanceId) continue;
                
                // Se troviamo un'altra istanza con lo stesso nome
                if (otherInstanceName === instanceName.trim()) {
                    duplicateFound = true;
                    break;
                }
            }
            
            if (duplicateFound) {
                showValidationMessage('warning', 'Esiste già un\'istanza con questo nome');
            } else {
                removeValidationMessage();
            }
        }
        
        // Mostra messaggio di validazione
        function showValidationMessage(type, message) {
            removeValidationMessage();
            
            const instanceNameInput = document.getElementById('instance-name');
            if (!instanceNameInput) return;
            
            const validationDiv = document.createElement('div');
            validationDiv.id = 'instance-name-validation';
            validationDiv.style.cssText = type === 'warning' 
                ? 'color: #856404; background: #fff3cd; border: 1px solid #ffeaa7; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; margin-top: 0.25rem;'
                : 'color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; margin-top: 0.25rem;';
            
            validationDiv.innerHTML = `<i class="fas fa-${type === 'warning' ? 'exclamation-triangle' : 'check'}"></i> ${message}`;
            
            instanceNameInput.parentNode.insertBefore(validationDiv, instanceNameInput.nextSibling);
        }
        
        // Rimuovi messaggio di validazione
        function removeValidationMessage() {
            const existingValidation = document.getElementById('instance-name-validation');
            if (existingValidation) {
                existingValidation.remove();
            }
        }
        
        // Aggiorna preview modulo
        function updateModulePreview() {
            if (!selectedInstance) return;
            
            const moduleName = selectedInstance.getAttribute('data-module-name');
            let config = {};
            
            // Raccogli configurazione attuale dal form
            try {
                // Raccogli configurazione dinamicamente
                config = collectDynamicConfig();
            } catch (e) {
                console.warn('Config non ancora caricata:', e);
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'preview-module');
            formData.append('module_name', moduleName);
            formData.append('config', JSON.stringify(config));
            
            fetch('api/page_builder.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON Parse Error:', text);
                        throw new Error('Risposta non valida dal server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    const moduleContent = selectedInstance.querySelector('.module-content');
                    if (moduleContent) {
                        moduleContent.innerHTML = data.html;
                        
                        // Aggiorna anche il nome dell'istanza se modificato
                        const instanceNameEl = document.getElementById('instance-name');
                        if (instanceNameEl) {
                            selectedInstance.setAttribute('data-instance-name', instanceNameEl.value);
                            const headerTitle = selectedInstance.querySelector('.module-header div');
                            if (headerTitle) {
                                headerTitle.innerHTML = `<i class="fas fa-cube"></i> ${moduleName} - ${instanceNameEl.value}`;
                            }
                        }
                        
                        // Riattacca gli event listener dopo l'aggiornamento del DOM
                        reattachModuleListeners();
                    }
                } else {
                    console.error('Errore preview:', data.error);
                    const moduleContent = selectedInstance.querySelector('.module-content');
                    if (moduleContent) {
                        moduleContent.innerHTML = '<div style="color: #dc3545; padding: 1rem;">Errore rendering: ' + (data.error || 'Errore sconosciuto') + '</div>';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const moduleContent = selectedInstance.querySelector('.module-content');
                if (moduleContent) {
                    moduleContent.innerHTML = '<div style="color: #dc3545; padding: 1rem;">Errore durante il caricamento: ' + error.message + '</div>';
                }
            });
        }
        
        // Anteprima pagina
        function previewPage() {
            window.open(`../index.php?id_pagina=${currentPageId}`, '_blank');
        }
        
        // Anteprima istanza
        function previewInstance(instanceId) {
            // Per ora apre la pagina completa
            previewPage();
        }
        
        // Chiudi modal anteprima
        function closePreview() {
            document.getElementById('preview-modal').style.display = 'none';
        }
    </script>
</body>
</html>
