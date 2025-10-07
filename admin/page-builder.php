<?php
/**
 * Page Builder - Bologna Marathon
 * Gestione drag&drop moduli per pagine dinamiche
 */

require_once '../config/database.php';
require_once '../core/ModuleRenderer.php';

// Inizializza connessione database
$database = new Database();
$db = $database->getConnection();
$renderer = new ModuleRenderer($db);

// Gestione azioni AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Pulisci output buffer per evitare contaminazioni JSON
    ob_clean();
    header('Content-Type: application/json');
    
    // Disabilita error reporting per JSON
    $old_error_reporting = error_reporting(0);
    
    try {
        switch ($_POST['action']) {
            case 'save_instance':
                $pageId = (int)$_POST['page_id'];
                $moduleName = $_POST['module_name'];
                $instanceName = $_POST['instance_name'];
                $config = json_decode($_POST['config'], true);
                $orderIndex = (int)($_POST['order_index'] ?? 0);
                $currentInstanceId = isset($_POST['current_instance_id']) ? (int)$_POST['current_instance_id'] : null;
                
                // Se abbiamo un ID istanza corrente, stiamo aggiornando
                if ($currentInstanceId && $currentInstanceId !== 0) {
                    // Verifica se esiste un'altra istanza con lo stesso nome (escludendo quella corrente)
                    $stmt = $db->prepare("SELECT id FROM module_instances WHERE page_id = ? AND instance_name = ? AND id != ?");
                    $stmt->execute([$pageId, $instanceName, $currentInstanceId]);
                    $duplicate = $stmt->fetch();
                    
                    if ($duplicate) {
                        echo json_encode(['success' => false, 'error' => 'Esiste già un\'istanza con questo nome sulla stessa pagina']);
                        exit;
                    }
                    
                    // Aggiorna istanza esistente
                    $stmt = $db->prepare("UPDATE module_instances SET 
                                        module_name = ?, 
                                        instance_name = ?,
                                        config = ?, 
                                        order_index = ?, 
                                        updated_at = CURRENT_TIMESTAMP 
                                        WHERE id = ?");
                    $stmt->execute([$moduleName, $instanceName, json_encode($config), $orderIndex, $currentInstanceId]);
                    echo json_encode(['success' => true, 'id' => $currentInstanceId]);
                } else {
                    // Nuova istanza - verifica se esiste già un'istanza con lo stesso nome
                    $stmt = $db->prepare("SELECT id FROM module_instances WHERE page_id = ? AND instance_name = ?");
                    $stmt->execute([$pageId, $instanceName]);
                    $existing = $stmt->fetch();
                    
                    if ($existing) {
                        // Log per debug
                        error_log("Tentativo di creare istanza duplicata: page_id=$pageId, instance_name=$instanceName");
                        
                        // Trova un nome alternativo
                        $counter = 1;
                        $baseName = preg_replace('/_\d+$/', '', $instanceName);
                        do {
                            $counter++;
                            $newInstanceName = $baseName . '_' . $counter;
                            $stmt = $db->prepare("SELECT id FROM module_instances WHERE page_id = ? AND instance_name = ?");
                            $stmt->execute([$pageId, $newInstanceName]);
                            $existing = $stmt->fetch();
                        } while ($existing && $counter < 100); // Limite di sicurezza
                        
                        echo json_encode(['success' => false, 'error' => "Esiste già un'istanza con questo nome. Prova con: $newInstanceName"]);
                        exit;
                    }
                    
                    // Inserisci nuova istanza
                    $stmt = $db->prepare("INSERT INTO module_instances (page_id, module_name, instance_name, config, order_index) 
                                        VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$pageId, $moduleName, $instanceName, json_encode($config), $orderIndex]);
                    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
                }
                exit;
                
            case 'update_order':
                $updates = json_decode($_POST['updates'], true);
                $db->beginTransaction();
                
                foreach ($updates as $update) {
                    $stmt = $db->prepare("UPDATE module_instances SET order_index = ? WHERE id = ? AND page_id = ?");
                    $stmt->execute([$update['order_index'], $update['id'], $update['page_id']]);
                }
                
                $db->commit();
                echo json_encode(['success' => true]);
                exit;
                
            case 'delete_instance':
                $stmt = $db->prepare("DELETE FROM module_instances WHERE id = ? AND page_id = ?");
                $stmt->execute([(int)$_POST['instance_id'], (int)$_POST['page_id']]);
                
                echo json_encode(['success' => true]);
                exit;
                
            case 'get_module_preview':
                $moduleName = $_POST['module_name'];
                $config = json_decode($_POST['config'], true);
                
                $output = $renderer->renderModule($moduleName, $config);
                echo json_encode(['success' => true, 'html' => $output]);
                exit;
                
            case 'get_module_config':
                $moduleName = $_POST['module_name'];
                $instanceId = (int)$_POST['instance_id'] ?? null;
                
                // Ottieni configurazione attuale se esiste
                $currentConfig = [];
                if ($instanceId) {
                    $stmt = $db->prepare("SELECT config FROM module_instances WHERE id = ?");
                    $stmt->execute([$instanceId]);
                    $instance = $stmt->fetch();
                    if ($instance) {
                        $currentConfig = json_decode($instance['config'], true) ?? [];
                    }
                }
                
                // Ottieni informazioni modulo dal manifest
                $manifestPath = __DIR__ . "/../modules/$moduleName/module.json";
                $manifest = [];
                if (file_exists($manifestPath)) {
                    $manifest = json_decode(file_get_contents($manifestPath), true) ?? [];
                }
                
                $defaultConfig = $manifest['default_config'] ?? [];
                $config = array_merge($defaultConfig, $currentConfig);
                
                echo json_encode([
                    'success' => true, 
                    'config' => $config,
                    'manifest' => $manifest
                ]);
                exit;
                
            case 'update_page_theme':
                $pageId = (int)$_POST['page_id'];
                $theme = $_POST['theme'];
                
                $success = $renderer->updatePageTheme($pageId, $theme);
                echo json_encode(['success' => $success]);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    } finally {
        // Ripristina error reporting
        error_reporting($old_error_reporting);
    }
}

// Gestione richieste GET
$pageId = (int)($_GET['page_id'] ?? 1);
$action = $_GET['action'] ?? 'builder';

// Ottieni lista pagine
$pages = $db->query("SELECT * FROM pages ORDER BY title")->fetchAll();

// Ottieni pagina corrente
$currentPage = null;
if ($pageId) {
    $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$pageId]);
    $currentPage = $stmt->fetch();
}

// Ottieni moduli disponibili
$availableModules = $db->query("SELECT * FROM modules_registry WHERE is_active = 1 ORDER BY name")->fetchAll();

// Ottieni istanze moduli per la pagina corrente
$moduleInstances = [];
if ($currentPage) {
    $stmt = $db->prepare("SELECT * FROM module_instances WHERE page_id = ? ORDER BY order_index");
    $stmt->execute([$pageId]);
    $moduleInstances = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Builder - Bologna Marathon</title>
    
    <!-- CSS Core -->
    <link rel="stylesheet" href="../assets/css/core/variables.css">
    <link rel="stylesheet" href="../assets/css/core/colors.css">
    <link rel="stylesheet" href="../assets/css/core/reset.css">
    <link rel="stylesheet" href="../assets/css/core/typography.css">
    <link rel="stylesheet" href="../assets/css/core/fonts.css">
    
    <!-- CSS Moduli -->
    <?php
    // Carica CSS di tutti i moduli disponibili
    foreach ($availableModules as $module) {
        $moduleName = $module['name'];
        $cssPath = "../modules/$moduleName/$moduleName.css";
        if (file_exists($cssPath)) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars($cssPath) . '">';
        }
    }
    ?>
    
    <!-- CSS Page Builder -->
    <link rel="stylesheet" href="../assets/css/admin/page-builder.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css">
    
    <!-- Font Awesome per icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Page Builder Overrides - Disabilita classi fixed */
        .page-canvas [class*="fixed"],
        .page-canvas .sticky{
            position: static !important;
            top: auto !important;
            left: auto !important;
            right: auto !important;
            bottom: auto !important;
            z-index: auto !important;
            transform: none !important;
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
            
            <div class="theme-selector" style="margin-bottom: 1rem;">
                <label for="theme-selector" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Tema Pagina</label>
                <select id="theme-selector" onchange="updatePageTheme(this.value)">
                    <?php 
                    $availableThemes = $renderer->getAvailableThemes();
                    $currentTheme = $renderer->getPageTheme($pageId);
                    foreach ($availableThemes as $theme): ?>
                        <option value="<?= htmlspecialchars($theme['class_name']) ?>" 
                                <?= $theme['class_name'] == $currentTheme ? 'selected' : '' ?>>
                            <?= htmlspecialchars($theme['alias']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="theme-indicator" style="margin-top: 0.5rem; padding: 0.25rem 0.5rem; background: #e9ecef; border-radius: 4px; font-size: 0.8rem; color: #495057;">
                    <i class="fas fa-palette"></i> Tema attivo: <span id="current-theme-name"><?= htmlspecialchars($currentTheme) ?></span>
                </div>
            </div>
            
            <div style="margin: 1rem 0;">
                <button class="btn-small btn-preview" onclick="previewPage()" style="width: 100%; margin-bottom: 0.5rem;">
                    <i class="fas fa-eye"></i> Anteprima
                </button>
                <a href="../index.php?id_pagina=<?= $pageId ?>" target="_blank" class="btn-small btn-preview" style="width: 100%; display: block; text-align: center; margin-bottom: 0.5rem;">
                    <i class="fas fa-external-link-alt"></i> Vedi Pagina
                </a>
                <a href="theme-editor.php" class="btn-small btn-secondary" style="width: 100%; display: block; text-align: center; margin-bottom: 0.5rem;">
                    <i class="fas fa-palette"></i> Gestisci Temi
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
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
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
        
        // Inizializza tema al caricamento
        function initializeTheme() {
            // Prima pulisci completamente tutte le classi tema dal page-canvas
            const pageCanvas = document.getElementById('page-canvas');
            const allThemeClasses = [
                'race-marathon', 'race-portici', 'race-run-tune-up', 'race-5k',
                'theme-marathon', 'theme-portici', 'theme-run-tune-up', 'theme-5k',
                'marathon', 'portici', 'run-tune-up', '5k'
            ];
            
            // Pulisci classi tema dal page-canvas
            if (pageCanvas) {
                allThemeClasses.forEach(className => {
                    pageCanvas.classList.remove(className);
                });
            }
            
            console.log('Pulizia iniziale classi tema completata');
            console.log('Classi page-canvas dopo pulizia:', pageCanvas ? pageCanvas.className : 'N/A');
            
            const themeSelector = document.getElementById('theme-selector');
            if (themeSelector) {
                const currentTheme = themeSelector.value;
                applyThemeToBody(currentTheme);
                console.log(`Tema inizializzato: ${currentTheme}`);
            }
        }
        
        // Inizializza tema quando il DOM è pronto
        document.addEventListener('DOMContentLoaded', function() {
            initializeTheme();
        });
        
        // Funzione di debug per verificare le classi tema
        function debugThemeClasses() {
            const pageCanvas = document.getElementById('page-canvas');
            
            const canvasThemeClasses = pageCanvas ? (pageCanvas.className.match(/race-\w+|theme-\w+|\b(marathon|portici|run-tune-up|5k)\b/g) || []) : [];
            
            console.log('Classi tema attive sul page-canvas:', canvasThemeClasses);
            console.log('Classi complete del page-canvas:', pageCanvas ? pageCanvas.className : 'N/A');
            
            return {
                canvas: canvasThemeClasses
            };
        }
        
        // Aggiungi funzione globale per debug
        window.debugThemeClasses = debugThemeClasses;
        
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
            formData.append('action', 'get_module_config');
            formData.append('module_name', moduleName);
            if (instanceId && instanceId !== 'temp') {
                formData.append('instance_id', instanceId);
            }
            
            fetch('', {
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
                        
                    case 'datetime':
                        html += `<input type="datetime-local" id="config-${fieldName}" value="${value}" placeholder="${fieldConfig.placeholder || ''}">`;
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
                    } else if (input.type === 'datetime-local') {
                        // Converti datetime-local in formato ISO
                        if (input.value) {
                            const date = new Date(input.value);
                            config[fieldName] = date.toISOString();
                        } else {
                            config[fieldName] = '';
                        }
                    } else if (input.type === 'url') {
                        // Validazione URL
                        if (input.value && !isValidUrl(input.value)) {
                            console.warn(`URL non valido per ${fieldName}:`, input.value);
                        }
                        config[fieldName] = input.value;
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
            formData.append('action', 'save_instance');
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
            
            fetch('', {
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
            formData.append('action', 'update_order');
            formData.append('updates', JSON.stringify(updates));
            
            fetch('', {
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
            formData.append('action', 'delete_instance');
            formData.append('instance_id', instanceId);
            formData.append('page_id', currentPageId);
            
            fetch('', {
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
            formData.append('action', 'get_module_preview');
            formData.append('module_name', moduleName);
            formData.append('config', JSON.stringify(config));
            
            fetch('', {
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
                        
                        // Applica il tema corrente alla preview
                        const themeSelector = document.getElementById('theme-selector');
                        if (themeSelector) {
                            applyThemeToBody(themeSelector.value);
                        }
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
        
        // Aggiorna tema pagina
        function updatePageTheme(theme) {
            console.log(`Cambio tema richiesto: ${theme}`);
            
            // Applica il tema al body in tempo reale per la preview
            applyThemeToBody(theme);
            
            // Verifica che il tema sia stato applicato correttamente
            setTimeout(() => {
                const themeDebug = debugThemeClasses();
                const canvasClasses = themeDebug.canvas;
                
                if (canvasClasses.length > 1) {
                    console.warn('ATTENZIONE: Multiple classi tema rilevate sul page-canvas:', canvasClasses);
                } else if (canvasClasses.length === 0 && theme !== 'marathon') {
                    console.warn('ATTENZIONE: Nessuna classe tema applicata al page-canvas per:', theme);
                } else {
                    console.log('Tema applicato correttamente al page-canvas:', canvasClasses);
                }
            }, 100);
            
            const formData = new FormData();
            formData.append('action', 'update_page_theme');
            formData.append('page_id', currentPageId);
            formData.append('theme', theme);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostra messaggio di successo
                    const themeSelector = document.getElementById('theme-selector');
                    const successMsg = document.createElement('div');
                    successMsg.style.cssText = 'background: #d4edda; color: #155724; padding: 0.5rem; border-radius: 4px; margin-top: 0.5rem; font-size: 0.9rem;';
                    successMsg.innerHTML = '<i class="fas fa-check"></i> Tema aggiornato con successo!';
                    themeSelector.parentNode.insertBefore(successMsg, themeSelector.nextSibling);
                    
                    // Rimuovi messaggio dopo 3 secondi
                    setTimeout(() => {
                        if (successMsg.parentNode) {
                            successMsg.remove();
                        }
                    }, 3000);
                } else {
                    alert('Errore: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore durante l\'aggiornamento del tema: ' + error.message);
            });
        }
        
        // Applica tema SOLO al page-canvas per preview in tempo reale
        function applyThemeToBody(theme) {
            const pageCanvas = document.getElementById('page-canvas');
            
            // Lista completa di tutte le possibili classi tema
            const allThemeClasses = [
                'race-marathon', 'race-portici', 'race-run-tune-up', 'race-5k',
                'theme-marathon', 'theme-portici', 'theme-run-tune-up', 'theme-5k',
                'marathon', 'portici', 'run-tune-up', '5k'
            ];
            
            console.log(`Pulizia classi tema per: ${theme}`);
            console.log('Classi page-canvas prima della pulizia:', pageCanvas ? pageCanvas.className : 'N/A');
            
            // Rimuovi TUTTE le classi tema esistenti dal page-canvas
            if (pageCanvas) {
                allThemeClasses.forEach(className => {
                    pageCanvas.classList.remove(className);
                });
            }
            
            // Aggiungi la nuova classe tema al page-canvas per l'anteprima
            if (pageCanvas) {
                if (theme && theme !== 'marathon') {
                    // Converti il nome tema in classe CSS corretta
                    const cssClass = theme.startsWith('race-') ? theme : `race-${theme}`;
                    pageCanvas.classList.add(cssClass);
                    console.log(`Tema applicato al page-canvas: ${cssClass}`);
                    console.log(`Classi page-canvas dopo applicazione:`, pageCanvas.className);
                } else {
                    console.log('Tema page-canvas reset a default (marathon)');
                    console.log(`Classi page-canvas dopo reset:`, pageCanvas.className);
                }
            }
            
            // Aggiorna indicatore tema
            updateThemeIndicator(theme);
        }
        
        // Aggiorna indicatore tema
        function updateThemeIndicator(theme) {
            const themeIndicator = document.getElementById('current-theme-name');
            if (themeIndicator) {
                const themeName = theme || 'marathon';
                themeIndicator.textContent = themeName;
                
                // Aggiorna anche il colore dell'indicatore per feedback visivo
                const indicator = document.getElementById('theme-indicator');
                if (indicator) {
                    // Rimuovi classi colore esistenti
                    indicator.classList.remove('theme-portici', 'theme-run-tune-up', 'theme-5k');
                    
                    // Aggiungi classe colore se non è marathon
                    if (theme && theme !== 'marathon') {
                        indicator.classList.add(theme);
                    }
                }
            }
        }
        
        // Validazione URL
        function isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }
    </script>
</body>
</html>
