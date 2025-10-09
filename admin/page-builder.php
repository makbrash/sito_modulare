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
                
            case 'debug_instances':
                // Debug: lista tutte le istanze per la pagina corrente
                $pageId = (int)$_GET['page_id'] ?? 0;
                $stmt = $db->prepare("SELECT id, page_id, module_name, instance_name, is_template, template_name FROM module_instances WHERE page_id = ? OR is_template = 1 ORDER BY id DESC");
                $stmt->execute([$pageId]);
                $instances = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'page_id' => $pageId,
                    'instances' => $instances,
                    'count' => count($instances)
                ]);
                exit;
                
            case 'get_module_preview':
                $moduleName = $_POST['module_name'];
                $instanceId = (int)$_POST['instance_id'] ?? null;
                
                // Se abbiamo un instanceId, usa la logica del template globale
                if ($instanceId) {
                    $currentConfig = [];
                    $usesTemplate = false;
                    
                    $stmt = $db->prepare("SELECT mi.*, template.config as tpl_config 
                        FROM module_instances mi
                        LEFT JOIN module_instances template ON mi.template_instance_id = template.id
                        WHERE mi.id = ?");
                    $stmt->execute([$instanceId]);
                    $instance = $stmt->fetch();
                    
                    if ($instance) {
                        // Se usa un template, prendi config dal master
                        if ($instance['template_instance_id']) {
                            $usesTemplate = true;
                            $currentConfig = json_decode($instance['tpl_config'], true) ?? [];
                        } else {
                            $currentConfig = json_decode($instance['config'], true) ?? [];
                        }
                    }
                    
                    // Se usa template, usa la config del master invece di quella inviata
                    if ($usesTemplate) {
                        $config = $currentConfig;
                    } else {
                        // Modulo normale, usa la config inviata dal form
                        $config = json_decode($_POST['config'], true);
                    }
                } else {
                    // Nessun instanceId, usa la config inviata
                    $config = json_decode($_POST['config'], true);
                }
                
                $output = $renderer->renderModule($moduleName, $config);
                echo json_encode(['success' => true, 'html' => $output]);
                exit;
                
            case 'get_module_config':
                $moduleName = $_POST['module_name'];
                $instanceId = (int)$_POST['instance_id'] ?? null;
                
                // Ottieni configurazione attuale e info template
                $currentConfig = [];
                $usesTemplate = false;
                $templateId = null;
                $templateName = null;
                
                if ($instanceId) {
                    $stmt = $db->prepare("SELECT mi.*, template.id as tpl_id, template.template_name, template.config as tpl_config 
                        FROM module_instances mi
                        LEFT JOIN module_instances template ON mi.template_instance_id = template.id
                        WHERE mi.id = ?");
                    $stmt->execute([$instanceId]);
                    $instance = $stmt->fetch();
                    
                    if ($instance) {
                        // Se usa un template, prendi config dal master
                        if ($instance['template_instance_id']) {
                            $usesTemplate = true;
                            $templateId = $instance['tpl_id'];
                            $templateName = $instance['template_name'];
                            $currentConfig = json_decode($instance['tpl_config'], true) ?? [];
                        } else {
                            $currentConfig = json_decode($instance['config'], true) ?? [];
                        }
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
                    'manifest' => $manifest,
                    'uses_template' => $usesTemplate,
                    'template_id' => $templateId,
                    'template_name' => $templateName
                ]);
                exit;
                
            case 'update_page_theme':
                $pageId = (int)$_POST['page_id'];
                $theme = $_POST['theme'];
                
                $success = $renderer->updatePageTheme($pageId, $theme);
                echo json_encode(['success' => $success]);
                exit;
                
            case 'create_page':
                $title = trim($_POST['title'] ?? '');
                $slug = trim($_POST['slug'] ?? '');
                $status = $_POST['status'] ?? 'draft';
                
                if (empty($title)) {
                    echo json_encode(['success' => false, 'error' => 'Il titolo è obbligatorio']);
                    exit;
                }
                
                // Genera slug se non fornito
                if (empty($slug)) {
                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
                    $slug = trim($slug, '-');
                }
                
                // Verifica slug unico
                $stmt = $db->prepare("SELECT id FROM pages WHERE slug = ?");
                $stmt->execute([$slug]);
                if ($stmt->fetch()) {
                    // Aggiungi timestamp per rendere unico
                    $slug .= '-' . time();
                }
                
                // Crea nuova pagina con colonne corrette dello schema
                $stmt = $db->prepare("INSERT INTO pages (slug, title, description, template, layout_config, css_variables, status) 
                                     VALUES (?, ?, '', 'default', '{}', '{}', ?)");
                $stmt->execute([$slug, $title, $status]);
                $newPageId = $db->lastInsertId();
                
                echo json_encode(['success' => true, 'page_id' => $newPageId, 'message' => 'Pagina creata con successo']);
                exit;
                
            case 'duplicate_page':
                $sourcePageId = (int)$_POST['page_id'];
                $newTitle = trim($_POST['title'] ?? '');
                
                if (empty($newTitle)) {
                    echo json_encode(['success' => false, 'error' => 'Il titolo è obbligatorio']);
                    exit;
                }
                
                // Ottieni pagina sorgente
                $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
                $stmt->execute([$sourcePageId]);
                $sourcePage = $stmt->fetch();
                
                if (!$sourcePage) {
                    echo json_encode(['success' => false, 'error' => 'Pagina sorgente non trovata']);
                    exit;
                }
                
                // Genera slug unico
                $baseSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $newTitle));
                $baseSlug = trim($baseSlug, '-');
                $slug = $baseSlug;
                $counter = 1;
                
                while (true) {
                    $stmt = $db->prepare("SELECT id FROM pages WHERE slug = ?");
                    $stmt->execute([$slug]);
                    if (!$stmt->fetch()) break;
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                
                // Duplica pagina con colonne corrette dello schema
                $stmt = $db->prepare("INSERT INTO pages (slug, title, description, template, layout_config, css_variables, meta_data, status) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, 'draft')");
                $stmt->execute([
                    $slug,
                    $newTitle,
                    $sourcePage['description'] ?? '',
                    $sourcePage['template'] ?? 'default',
                    $sourcePage['layout_config'] ?? '{}',
                    $sourcePage['css_variables'] ?? '{}',
                    $sourcePage['meta_data'] ?? '{}'
                ]);
                $newPageId = $db->lastInsertId();
                
                // Duplica moduli associati
                $stmt = $db->prepare("SELECT * FROM module_instances WHERE page_id = ? ORDER BY order_index");
                $stmt->execute([$sourcePageId]);
                $modules = $stmt->fetchAll();
                
                $stmtInsert = $db->prepare("INSERT INTO module_instances (page_id, module_name, instance_name, config, order_index) 
                                           VALUES (?, ?, ?, ?, ?)");
                
                foreach ($modules as $module) {
                    // Genera nome istanza unico per la nuova pagina
                    $baseInstanceName = $module['instance_name'];
                    $instanceName = $baseInstanceName . '_copy';
                    $instanceCounter = 1;
                    
                    // Verifica unicità nome istanza
                    while (true) {
                        $stmt = $db->prepare("SELECT id FROM module_instances WHERE page_id = ? AND instance_name = ?");
                        $stmt->execute([$newPageId, $instanceName]);
                        if (!$stmt->fetch()) break;
                        $instanceName = $baseInstanceName . '_copy_' . $instanceCounter;
                        $instanceCounter++;
                    }
                    
                    // Inserisci modulo duplicato
                    $stmtInsert->execute([
                        $newPageId,
                        $module['module_name'],
                        $instanceName,
                        $module['config'],
                        $module['order_index']
                    ]);
                }
                
                echo json_encode([
                    'success' => true, 
                    'page_id' => $newPageId,
                    'modules_duplicated' => count($modules),
                    'message' => 'Pagina duplicata con successo'
                ]);
                exit;
                
            case 'delete_page':
                $pageId = (int)$_POST['page_id'];
                
                // Verifica che non sia l'ultima pagina
                $stmt = $db->query("SELECT COUNT(*) FROM pages");
                $pageCount = $stmt->fetchColumn();
                
                if ($pageCount <= 1) {
                    echo json_encode(['success' => false, 'error' => 'Non puoi eliminare l\'ultima pagina']);
                    exit;
                }
                
                // Elimina moduli associati
                $stmt = $db->prepare("DELETE FROM module_instances WHERE page_id = ?");
                $stmt->execute([$pageId]);
                
                // Elimina pagina
                $stmt = $db->prepare("DELETE FROM pages WHERE id = ?");
                $stmt->execute([$pageId]);
                
                echo json_encode(['success' => true, 'message' => 'Pagina eliminata con successo']);
                exit;
                
            case 'toggle_page_status':
                $pageId = (int)$_POST['page_id'];
                
                // Ottieni status corrente
                $stmt = $db->prepare("SELECT status FROM pages WHERE id = ?");
                $stmt->execute([$pageId]);
                $page = $stmt->fetch();
                
                if (!$page) {
                    echo json_encode(['success' => false, 'error' => 'Pagina non trovata']);
                    exit;
                }
                
                // Toggle status
                $newStatus = $page['status'] === 'published' ? 'draft' : 'published';
                
                // Aggiorna status
                $stmt = $db->prepare("UPDATE pages SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $pageId]);
                
                echo json_encode([
                    'success' => true, 
                    'status' => $newStatus,
                    'message' => $newStatus === 'published' ? 'Pagina pubblicata' : 'Pagina impostata come bozza'
                ]);
                exit;
                
            case 'save_as_template':
                $instanceId = (int)$_POST['instance_id'];
                $templateName = trim($_POST['template_name'] ?? '');
                
                error_log("DEBUG save_as_template: instanceId = $instanceId, templateName = $templateName");
                
                if (empty($templateName)) {
                    echo json_encode(['success' => false, 'error' => 'Il nome del modello è obbligatorio']);
                    exit;
                }
                
                // Ottieni istanza corrente
                $stmt = $db->prepare("SELECT * FROM module_instances WHERE id = ?");
                $stmt->execute([$instanceId]);
                $instance = $stmt->fetch();
                
                error_log("DEBUG save_as_template: instance found = " . ($instance ? 'YES' : 'NO'));
                if ($instance) {
                    error_log("DEBUG save_as_template: instance data = " . json_encode($instance));
                }
                
                if (!$instance) {
                    // Debug: controlla se l'istanza esiste con altri criteri
                    $stmt = $db->prepare("SELECT id, page_id, module_name, instance_name FROM module_instances WHERE id = ? OR page_id = ?");
                    $stmt->execute([$instanceId, $instanceId]);
                    $debugInstances = $stmt->fetchAll();
                    error_log("DEBUG save_as_template: debug instances = " . json_encode($debugInstances));
                    
                    echo json_encode(['success' => false, 'error' => 'Istanza non trovata (ID: ' . $instanceId . ')']);
                    exit;
                }
                
                // Crea istanza master (template)
                $stmt = $db->prepare("INSERT INTO module_instances 
                    (page_id, module_name, instance_name, config, is_template, template_name, order_index, is_active) 
                    VALUES (NULL, ?, ?, ?, TRUE, ?, 0, TRUE)");
                $templateInstanceName = 'template_' . $instance['module_name'] . '_' . time();
                $stmt->execute([
                    $instance['module_name'],
                    $templateInstanceName,
                    $instance['config'],
                    $templateName
                ]);
                $templateId = $db->lastInsertId();
                
                // Aggiorna istanza corrente per usare il template
                $stmt = $db->prepare("UPDATE module_instances SET template_instance_id = ? WHERE id = ?");
                $stmt->execute([$templateId, $instanceId]);
                
                echo json_encode([
                    'success' => true, 
                    'template_id' => $templateId,
                    'template_name' => $templateName,
                    'message' => 'Modello creato con successo'
                ]);
                exit;
                
            case 'get_templates':
                $moduleName = $_POST['module_name'] ?? '';
                
                if (empty($moduleName)) {
                    echo json_encode(['success' => false, 'error' => 'Nome modulo obbligatorio']);
                    exit;
                }
                
                // Ottieni tutti i template per questo tipo di modulo
                $stmt = $db->prepare("SELECT id, template_name, config, created_at 
                    FROM module_instances 
                    WHERE is_template = TRUE AND module_name = ? 
                    ORDER BY template_name");
                $stmt->execute([$moduleName]);
                $templates = $stmt->fetchAll();
                
                echo json_encode(['success' => true, 'templates' => $templates]);
                exit;
                
            case 'apply_template':
                $instanceId = (int)$_POST['instance_id'];
                $templateId = (int)$_POST['template_id'];
                
                // Verifica che il template esista
                $stmt = $db->prepare("SELECT * FROM module_instances WHERE id = ? AND is_template = TRUE");
                $stmt->execute([$templateId]);
                $template = $stmt->fetch();
                
                if (!$template) {
                    echo json_encode(['success' => false, 'error' => 'Template non trovato']);
                    exit;
                }
                
                // Applica template all'istanza
                $stmt = $db->prepare("UPDATE module_instances SET template_instance_id = ?, config = ? WHERE id = ?");
                $stmt->execute([$templateId, '{}', $instanceId]);
                
                echo json_encode([
                    'success' => true,
                    'template_name' => $template['template_name'],
                    'message' => 'Template applicato con successo'
                ]);
                exit;
                
            case 'detach_from_template':
                $instanceId = (int)$_POST['instance_id'];
                
                // Ottieni config dal template
                $stmt = $db->prepare("SELECT mi.*, template.config as template_config 
                    FROM module_instances mi
                    LEFT JOIN module_instances template ON mi.template_instance_id = template.id
                    WHERE mi.id = ?");
                $stmt->execute([$instanceId]);
                $instance = $stmt->fetch();
                
                if (!$instance) {
                    echo json_encode(['success' => false, 'error' => 'Istanza non trovata']);
                    exit;
                }
                
                // Copia config dal template e stacca
                $newConfig = $instance['template_config'] ?? $instance['config'];
                $stmt = $db->prepare("UPDATE module_instances SET template_instance_id = NULL, config = ? WHERE id = ?");
                $stmt->execute([$newConfig, $instanceId]);
                
                echo json_encode([
                    'success' => true,
                    'config' => json_decode($newConfig, true),
                    'message' => 'Modulo scollegato dal template'
                ]);
                exit;
                
            case 'update_template':
                $templateId = (int)$_POST['template_id'];
                $config = json_decode($_POST['config'], true);
                
                // Verifica che sia un template
                $stmt = $db->prepare("SELECT * FROM module_instances WHERE id = ? AND is_template = TRUE");
                $stmt->execute([$templateId]);
                $template = $stmt->fetch();
                
                if (!$template) {
                    echo json_encode(['success' => false, 'error' => 'Template non trovato']);
                    exit;
                }
                
                // Aggiorna config del template master
                $stmt = $db->prepare("UPDATE module_instances SET config = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([json_encode($config), $templateId]);
                
                // Conta quante istanze usano questo template
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM module_instances WHERE template_instance_id = ?");
                $stmt->execute([$templateId]);
                $count = $stmt->fetchColumn();
                
                echo json_encode([
                    'success' => true,
                    'affected_instances' => $count,
                    'message' => "Template aggiornato! Modifiche applicate a $count pagine"
                ]);
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
    <link rel="stylesheet" href="../assets/css/core/layout.css">
    
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
                <p>
                    <span class="page-status page-status--<?= $currentPage['status'] ?>">
                        <i class="fas fa-<?= $currentPage['status'] === 'published' ? 'check-circle' : 'clock' ?>"></i>
                        <?= $currentPage['status'] === 'published' ? 'Pubblicata' : 'Bozza' ?>
                    </span>
                </p>
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
            
            <div class="page-actions" style="margin-top: 1rem;">
                <?php if ($currentPage): ?>
                    <?php if ($currentPage['status'] === 'draft'): ?>
                        <button id="publish-btn" class="btn-small btn-success" onclick="togglePageStatus()" style="width: 100%; margin-bottom: 0.5rem;">
                            <i class="fas fa-check-circle"></i> Pubblica Pagina
                        </button>
                    <?php else: ?>
                        <button id="publish-btn" class="btn-small btn-warning" onclick="togglePageStatus()" style="width: 100%; margin-bottom: 0.5rem;">
                            <i class="fas fa-clock"></i> Imposta come Bozza
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                
                <button class="btn-small btn-edit" onclick="showCreatePageModal()" style="width: 100%; margin-bottom: 0.5rem;">
                    <i class="fas fa-plus"></i> Nuova Pagina
                </button>
                <button class="btn-small btn-secondary" onclick="showDuplicatePageModal()" style="width: 100%; margin-bottom: 0.5rem;">
                    <i class="fas fa-copy"></i> Duplica Pagina
                </button>
                <button class="btn-small btn-delete" onclick="confirmDeletePage()" style="width: 100%;">
                    <i class="fas fa-trash"></i> Elimina Pagina
                </button>
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
    
    <!-- Modal Crea Pagina -->
    <div class="modal" id="create-page-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Crea Nuova Pagina</h3>
                <button class="modal-close" onclick="closeModal('create-page-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="new-page-title">Titolo Pagina *</label>
                    <input type="text" id="new-page-title" placeholder="Es: Home, Chi Siamo, Contatti" required>
                </div>
                <div class="form-group">
                    <label for="new-page-slug">Slug (opzionale)</label>
                    <input type="text" id="new-page-slug" placeholder="es: chi-siamo (lascia vuoto per auto-generazione)">
                    <small style="display: block; margin-top: 0.25rem; color: #6c757d;">Lo slug sarà utilizzato nell'URL della pagina</small>
                </div>
                <div class="form-group">
                    <label for="new-page-status">Stato</label>
                    <select id="new-page-status">
                        <option value="draft">Bozza</option>
                        <option value="published">Pubblicata</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-small btn-secondary" onclick="closeModal('create-page-modal')">Annulla</button>
                <button class="btn-small btn-edit" onclick="createPage()">
                    <i class="fas fa-check"></i> Crea Pagina
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal Duplica Pagina -->
    <div class="modal" id="duplicate-page-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-copy"></i> Duplica Pagina</h3>
                <button class="modal-close" onclick="closeModal('duplicate-page-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 1rem; color: #6c757d;">
                    Duplicando la pagina "<strong><?= htmlspecialchars($currentPage['title'] ?? '') ?></strong>" verranno copiati anche tutti i moduli associati.
                </p>
                <div class="form-group">
                    <label for="duplicate-page-title">Titolo Nuova Pagina *</label>
                    <input type="text" id="duplicate-page-title" placeholder="Es: <?= htmlspecialchars($currentPage['title'] ?? 'Home') ?> - Copia" required>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-small btn-secondary" onclick="closeModal('duplicate-page-modal')">Annulla</button>
                <button class="btn-small btn-edit" onclick="duplicatePage()">
                    <i class="fas fa-copy"></i> Duplica Pagina
                </button>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        let currentPageId = <?= $pageId ?>;
        let selectedInstance = null;
        let instanceCounter = {};
        
        // === FIX PERCORSI IMMAGINI ===
        
        /**
         * Corregge i percorsi relativi delle immagini nel page canvas
         * Converte percorsi relativi in percorsi assoluti dalla root del progetto
         */
        function fixImagePaths(container) {
            if (!container) return;
            
            // Base path del progetto (dall'admin alla root)
            const basePath = '../';
            
            // Seleziona tutte le immagini nel container
            const images = container.querySelectorAll('img');
            
            images.forEach(img => {
                const src = img.getAttribute('src');
                
                // Salta se già assoluto (http/https) o data URI
                if (!src || src.startsWith('http') || src.startsWith('data:') || src.startsWith('//')) {
                    return;
                }
                
                // Se il percorso è relativo e non inizia con ../
                if (!src.startsWith('../')) {
                    // Aggiungi il base path
                    img.setAttribute('src', basePath + src);
                    console.log('Percorso immagine corretto:', src, '->', basePath + src);
                }
            });
            
            // Gestisci anche i background-image nel CSS inline
            const elementsWithBg = container.querySelectorAll('[style*="background-image"]');
            elementsWithBg.forEach(el => {
                const style = el.getAttribute('style');
                if (style && style.includes('url(')) {
                    const fixedStyle = style.replace(/url\(['"]?([^'")]+)['"]?\)/g, (match, url) => {
                        // Salta se già assoluto
                        if (url.startsWith('http') || url.startsWith('data:') || url.startsWith('//') || url.startsWith('../')) {
                            return match;
                        }
                        console.log('Background-image corretto:', url, '->', basePath + url);
                        return `url('${basePath}${url}')`;
                    });
                    el.setAttribute('style', fixedStyle);
                }
            });
        }
        
        /**
         * Observer per correggere le immagini quando il DOM cambia
         */
        function observeImageChanges() {
            const pageCanvas = document.getElementById('page-canvas');
            if (!pageCanvas) return;
            
            // Correggi immagini esistenti
            fixImagePaths(pageCanvas);
            
            // Osserva cambiamenti futuri
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    // Se sono stati aggiunti nuovi nodi
                    if (mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach((node) => {
                            if (node.nodeType === 1) { // Element node
                                fixImagePaths(node);
                            }
                        });
                    }
                    
                    // Se sono stati modificati attributi (es. src di img)
                    if (mutation.type === 'attributes' && mutation.attributeName === 'src') {
                        if (mutation.target.tagName === 'IMG') {
                            fixImagePaths(mutation.target.parentElement);
                        }
                    }
                });
            });
            
            // Inizia ad osservare
            observer.observe(pageCanvas, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['src', 'style']
            });
            
            console.log('Observer attivo per correzione percorsi immagini');
        }
        
        // Inizializza observer quando il DOM è pronto
        document.addEventListener('DOMContentLoaded', function() {
            observeImageChanges();
        });
        
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
                // Salva automaticamente l'ordinamento dopo aggiunta modulo
                updateOrder();
            }, 100);
            
            // AUTO-SAVE: Salva immediatamente il modulo con config default
            // Così è subito disponibile per "Salva come Modello Globale"
            setTimeout(() => {
                console.log('Auto-save modulo con config default:', moduleName, instanceName);
                autoSaveNewModule(tempDiv, moduleName, instanceName, orderIndex);
            }, 500);
        }
        
        // Auto-save nuovo modulo con config default
        function autoSaveNewModule(element, moduleName, instanceName, orderIndex) {
            // Carica config default dal manifest
            const formData = new FormData();
            formData.append('action', 'get_module_config');
            formData.append('module_name', moduleName);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const defaultConfig = data.config;
                    
                    console.log('Config default caricata:', defaultConfig);
                    
                    // Salva nel database
                    const saveFormData = new FormData();
                    saveFormData.append('action', 'save_instance');
                    saveFormData.append('page_id', currentPageId);
                    saveFormData.append('module_name', moduleName);
                    saveFormData.append('instance_name', instanceName);
                    saveFormData.append('config', JSON.stringify(defaultConfig));
                    saveFormData.append('order_index', orderIndex);
                    
                    return fetch('', {
                        method: 'POST',
                        body: saveFormData
                    });
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Modulo auto-salvato con ID:', data.id);
                    
                    // Aggiorna elemento da temp a ID reale
                    element.setAttribute('data-instance-id', data.id);
                    
                    // Aggiorna pulsanti controllo
                    const controlButtons = element.querySelectorAll('.module-controls button');
                    controlButtons.forEach(btn => {
                        btn.setAttribute('data-instance-id', data.id);
                    });
                    
                    // Aggiorna preview con config default
                    selectedInstance = element;
                    updateModulePreview();
                    
                    // Salva automaticamente l'ordinamento dopo auto-save
                    updateOrder();
                    
                    console.log('Auto-save completato! Modulo pronto per essere trasformato in globale.');
                } else {
                    console.error('Errore auto-save:', data.error);
                }
            })
            .catch(error => {
                console.error('Errore durante auto-save:', error);
            });
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
                    renderModuleConfig(moduleName, instanceName, instanceId, data.config, data.manifest, {
                        usesTemplate: data.uses_template,
                        templateId: data.template_id,
                        templateName: data.template_name
                    });
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
        function renderModuleConfig(moduleName, instanceName, instanceId, config, manifest, templateInfo = {}) {
            const configPanel = document.getElementById('config-content');
            const usesTemplate = templateInfo.usesTemplate || false;
            const templateId = templateInfo.templateId;
            const templateName = templateInfo.templateName;
            
            // Inizia HTML
            let html = '<div class="form-group">' +
                '<label>Nome Istanza</label>' +
                '<input type="text" id="instance-name" value="' + instanceName + '">' +
                '</div>';
            
            // Se usa un template, mostra badge e UI speciale
            if (usesTemplate) {
                html += '<div class="template-badge template-badge--active">' +
                    '<i class="fas fa-link"></i>' +
                    '<strong>Modello Globale:</strong> ' + templateName +
                    '<p style="margin: 0.5rem 0 0 0; font-size: 0.875rem;">' +
                    'Questo modulo è collegato a un modello condiviso' +
                    '</p>' +
                    '</div>' +
                    '<div class="template-actions" style="margin-top: 1rem;">' +
                    '<button class="btn-small btn-warning" onclick="editGlobalTemplate(' + templateId + ', \'' + moduleName + '\')" style="width: 100%; margin-bottom: 0.5rem;">' +
                    '<i class="fas fa-edit"></i> Modifica Modello Generale' +
                    '</button>' +
                    '<button class="btn-small btn-secondary" onclick="detachFromTemplate(' + instanceId + ')" style="width: 100%;">' +
                    '<i class="fas fa-unlink"></i> Salva come Modulo di Pagina' +
                    '</button>' +
                    '</div>' +
                    '<div class="template-preview" style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 4px;">' +
                    '<strong style="display: block; margin-bottom: 0.5rem;">Anteprima Configurazione:</strong>';
                
                // Mostra campi readonly
                if (manifest.ui_schema) {
                    for (const [fieldName, fieldConfig] of Object.entries(manifest.ui_schema)) {
                        const value = config[fieldName] || '';
                        const displayValue = value || '(non impostato)';
                        html += '<div style="margin-bottom: 0.5rem;">' +
                            '<small style="color: #6c757d;">' + (fieldConfig.label || fieldName) + ':</small>' +
                            '<div style="padding: 0.25rem 0; color: #495057;">' + displayValue + '</div>' +
                            '</div>';
                    }
                }
                
                html += '</div>';
                
            } else {
                // Modulo normale - mostra select template e pulsante salva
                html += '<div class="template-selector" style="margin-bottom: 1rem; padding: 1rem; background: #e7f3ff; border-radius: 4px;">' +
                    '<label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">' +
                    '<i class="fas fa-layer-group"></i> Gestione Modelli' +
                    '</label>' +
                    '<div style="margin-bottom: 0.5rem;">' +
                    '<select id="template-select" class="template-select" style="width: 100%; padding: 0.5rem; margin-bottom: 0.5rem;">' +
                    '<option value="">-- Seleziona modello --</option>' +
                    '</select>' +
                    '<button class="btn-small btn-secondary" onclick="applySelectedTemplate(\'' + instanceId + '\')" style="width: 100%; margin-bottom: 0.5rem;">' +
                    '<i class="fas fa-check"></i> Applica Modello Selezionato' +
                    '</button>' +
                    '</div>' +
                    '<button class="btn-small btn-success" onclick="saveAsTemplate(\'' + instanceId + '\', \'' + moduleName + '\')" style="width: 100%;">' +
                    '<i class="fas fa-save"></i> Salva come Modello Globale' +
                    '</button>' +
                    '</div>';
                
                // Carica template disponibili
                loadAvailableTemplates(moduleName);
                
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
            }
            
            configPanel.innerHTML = html;
            
            // Flagga sul DOM se il modulo usa un modello globale (per la preview)
            if (typeof selectedInstance !== 'undefined' && selectedInstance) {
                selectedInstance.setAttribute('data-uses-template', usesTemplate ? '1' : '0');
            }
            
            // Aggiungi event listeners per aggiornamento in tempo reale
            setTimeout(() => {
                attachConfigListeners();
            }, 100);
        }
        
        // Attacca event listeners ai campi di configurazione per preview tempo reale
        function attachConfigListeners() {
            const configPanel = document.getElementById('config-content');
            // ESCLUDI template-select dai listener di preview per evitare loop
            const inputs = configPanel.querySelectorAll('input:not([readonly]), select:not([disabled]):not(#template-select), textarea:not([readonly])');
            
            console.log(`Attaccando event listeners a ${inputs.length} campi per preview tempo reale`);
            
            inputs.forEach((input, index) => {
                // Rimuovi eventuali listener esistenti (previene duplicati)
                const newInput = input.cloneNode(true);
                input.parentNode.replaceChild(newInput, input);
                
                // Controllo speciale per il nome istanza
                if (newInput.id === 'instance-name') {
                    newInput.addEventListener('input', debounce(() => {
                        validateInstanceName(newInput.value);
                    }, 300));
                }
                
                // Event listener per input
                newInput.addEventListener('input', debounce(() => {
                    try {
                        console.log('Input changed:', newInput.id, newInput.value);
                        updateModulePreview();
                    } catch (e) {
                        console.warn('Preview update skipped:', e.message);
                    }
                }, 500));
                
                // Event listener per change (select, checkbox)
                newInput.addEventListener('change', () => {
                    try {
                        console.log('Field changed:', newInput.id, newInput.value || newInput.checked);
                        updateModulePreview();
                    } catch (e) {
                        console.warn('Preview update skipped:', e.message);
                    }
                });
                
                console.log(`Event listener ${index + 1} attaccato a: ${newInput.id || newInput.name || 'campo senza ID'}`);
            });
            
            console.log('Event listeners per preview tempo reale attaccati con successo!');
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
                        // Converti ISO in datetime-local format se necessario
                        let datetimeValue = value;
                        if (value && value.includes('T') && !value.includes(':00.')) {
                            // Formato già corretto YYYY-MM-DDTHH:mm:ss o YYYY-MM-DDTHH:mm
                            datetimeValue = value.substring(0, 16); // Prendi solo YYYY-MM-DDTHH:mm
                        } else if (value && value.includes('Z')) {
                            // Formato ISO completo, rimuovi Z e millisecondi
                            datetimeValue = value.substring(0, 16);
                        }
                        html += `<input type="datetime-local" id="config-${fieldName}" value="${datetimeValue}" placeholder="${fieldConfig.placeholder || ''}">`;
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
                        // Mantieni formato compatibile datetime-local
                        if (input.value) {
                            // Mantieni formato YYYY-MM-DDTHH:mm:ss (senza millisecondi e Z)
                            config[fieldName] = input.value.includes(':') ? input.value + ':00' : input.value;
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
                        // Salva automaticamente l'ordinamento dopo salvataggio configurazione
                        updateOrder();
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
            
            console.log(`🔄 Aggiornamento ordinamento: ${instances.length} moduli trovati`);
            
            instances.forEach((instance, index) => {
                const instanceId = instance.getAttribute('data-instance-id');
                const moduleName = instance.getAttribute('data-module-name');
                const instanceName = instance.getAttribute('data-instance-name');
                
                if (instanceId !== 'temp') {
                    updates.push({
                        id: instanceId,
                        page_id: currentPageId,
                        order_index: index
                    });
                    console.log(`  📍 Posizione ${index}: ${moduleName} (${instanceName}) [ID: ${instanceId}]`);
                } else {
                    console.log(`  ⏳ Modulo temporaneo saltato: ${moduleName} (${instanceName})`);
                }
            });
            
            if (updates.length === 0) {
                console.log('❌ Nessun aggiornamento ordinamento necessario');
                return;
            }
            
            console.log(`💾 Salvando ordinamento per ${updates.length} moduli...`);
            
            const formData = new FormData();
            formData.append('action', 'update_order');
            formData.append('updates', JSON.stringify(updates));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('✅ Ordinamento salvato con successo!');
                } else {
                    console.error('❌ Errore aggiornamento ordine:', data.error);
                }
            })
            .catch(error => {
                console.error('❌ Errore durante aggiornamento ordine:', error);
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
                    
                    // Salva automaticamente l'ordinamento dopo eliminazione
                    updateOrder();
                    
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
            
            // Per i moduli con template, la config viene presa dal server
            // Per i moduli normali, raccogli dal form
            const usesTemplate = selectedInstance.getAttribute('data-uses-template') === '1';
            if (!usesTemplate) {
                // Modulo normale - raccogli configurazione attuale dal form
                try {
                    config = collectDynamicConfig();
                } catch (e) {
                    console.warn('Config non ancora caricata:', e);
                    return;
                }
            }
            // Se usa template, config sarà {} e il server userà quella del master
            
            const formData = new FormData();
            formData.append('action', 'get_module_preview');
            formData.append('module_name', moduleName);
            formData.append('instance_id', selectedInstance.getAttribute('data-instance-id'));
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
                        fixImagePaths(moduleContent);
                        const instanceNameEl = document.getElementById('instance-name');
                        if (instanceNameEl) {
                            selectedInstance.setAttribute('data-instance-name', instanceNameEl.value);
                            const headerTitle = selectedInstance.querySelector('.module-header div');
                            if (headerTitle) {
                                headerTitle.innerHTML = `<i class="fas fa-cube"></i> ${moduleName} - ${instanceNameEl.value}`;
                            }
                        }
                        reattachModuleListeners();
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
        
        // Salvataggio sicuro prima di operazioni template (RIMOSSO per evitare loop)
        // Ora l'utente deve salvare manualmente prima delle operazioni template
        
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
        
        // === GESTIONE PAGINE ===
        
        // Mostra modal crea pagina
        function showCreatePageModal() {
            document.getElementById('create-page-modal').style.display = 'flex';
            document.getElementById('new-page-title').focus();
        }
        
        // Mostra modal duplica pagina
        function showDuplicatePageModal() {
            document.getElementById('duplicate-page-modal').style.display = 'flex';
            document.getElementById('duplicate-page-title').focus();
        }
        
        // Chiudi modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            
            // Reset form fields
            if (modalId === 'create-page-modal') {
                document.getElementById('new-page-title').value = '';
                document.getElementById('new-page-slug').value = '';
                document.getElementById('new-page-status').value = 'draft';
            } else if (modalId === 'duplicate-page-modal') {
                document.getElementById('duplicate-page-title').value = '';
            }
        }
        
        // Crea nuova pagina
        function createPage() {
            const title = document.getElementById('new-page-title').value.trim();
            const slug = document.getElementById('new-page-slug').value.trim();
            const status = document.getElementById('new-page-status').value;
            
            if (!title) {
                alert('Il titolo della pagina è obbligatorio');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'create_page');
            formData.append('title', title);
            formData.append('slug', slug);
            formData.append('status', status);
            
            // Mostra loading
            const btn = event.target;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creazione...';
            btn.disabled = true;
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('create-page-modal');
                    alert(data.message || 'Pagina creata con successo!');
                    // Ricarica sulla nuova pagina
                    window.location.href = `?page_id=${data.page_id}`;
                } else {
                    alert('Errore: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore durante la creazione della pagina: ' + error.message);
            })
            .finally(() => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            });
        }
        
        // Duplica pagina corrente
        function duplicatePage() {
            const title = document.getElementById('duplicate-page-title').value.trim();
            
            if (!title) {
                alert('Il titolo della nuova pagina è obbligatorio');
                return;
            }
            
            if (!confirm('Sei sicuro di voler duplicare questa pagina e tutti i suoi moduli?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'duplicate_page');
            formData.append('page_id', currentPageId);
            formData.append('title', title);
            
            // Mostra loading
            const btn = event.target;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Duplicazione...';
            btn.disabled = true;
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('duplicate-page-modal');
                    const message = `Pagina duplicata con successo!\nModuli copiati: ${data.modules_duplicated || 0}`;
                    alert(message);
                    // Ricarica sulla nuova pagina
                    window.location.href = `?page_id=${data.page_id}`;
                } else {
                    alert('Errore: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore durante la duplicazione della pagina: ' + error.message);
            })
            .finally(() => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            });
        }
        
        // Conferma eliminazione pagina
        function confirmDeletePage() {
            if (!confirm('Sei sicuro di voler eliminare questa pagina?\n\nQuesta azione eliminerà anche tutti i moduli associati e non può essere annullata.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_page');
            formData.append('page_id', currentPageId);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Pagina eliminata con successo!');
                    // Ricarica sulla prima pagina disponibile
                    window.location.href = '?page_id=1';
                } else {
                    alert('Errore: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore durante l\'eliminazione della pagina: ' + error.message);
            });
        }
        
        // Chiudi modal con ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal('create-page-modal');
                closeModal('duplicate-page-modal');
            }
        });
        
        // Chiudi modal cliccando fuori
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal(event.target.id);
            }
        });
        
        // === GESTIONE MODELLI MODULI ===
        
        // Carica template disponibili per un tipo di modulo
        function loadAvailableTemplates(moduleName) {
            const formData = new FormData();
            formData.append('action', 'get_templates');
            formData.append('module_name', moduleName);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('template-select');
                    if (select) {
                        // Popola select con template
                        data.templates.forEach(template => {
                            const option = document.createElement('option');
                            option.value = template.id;
                            option.textContent = template.template_name;
                            select.appendChild(option);
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error loading templates:', error);
            });
        }
        
        // Debug: verifica stato istanza selezionata
        function debugInstances() {
            console.log('🔍 Debug istanze nel database...');
            fetch(`?action=debug_instances&page_id=${currentPageId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('🔍 Istanze nel database:', data);
                })
                .catch(error => {
                    console.error('❌ Errore debug:', error);
                });
        }
        
        // Salva modulo come template globale
        function saveAsTemplate(instanceId, moduleName) {
            console.log('🔧 saveAsTemplate chiamata con:', { instanceId, moduleName });
            
            // Debug: verifica stato istanza selezionata
            if (selectedInstance) {
                const actualId = selectedInstance.getAttribute('data-instance-id');
                const actualModule = selectedInstance.getAttribute('data-module-name');
                const actualName = selectedInstance.getAttribute('data-instance-name');
                console.log('🔧 selectedInstance stato:', { actualId, actualModule, actualName });
            } else {
                console.log('❌ selectedInstance è null!');
            }
            
            const templateName = prompt('Nome del modello globale:');
            if (!templateName || !templateName.trim()) {
                return;
            }
            
            console.log('🔧 Salvando come template:', { instanceId, templateName });
            
            const formData = new FormData();
            formData.append('action', 'save_as_template');
            formData.append('instance_id', instanceId);
            formData.append('template_name', templateName.trim());
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('🔧 Risposta save_as_template:', data);
                if (data.success) {
                    alert(data.message || 'Modello creato con successo!');
                    // Ricarica configurazione per mostrare nuova UI
                    loadInstanceConfig(selectedInstance);
                } else {
                    console.error('❌ Errore save_as_template:', data.error);
                    alert('Errore: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                console.error('❌ Errore durante il salvataggio del template:', error);
                alert('Errore durante la creazione del modello: ' + error.message);
            });
        }
        
        // Applica template selezionato
        function applySelectedTemplate(instanceId) {
            console.log('🔧 applySelectedTemplate chiamata con instanceId:', instanceId);
            console.log('🔧 Tipo di instanceId:', typeof instanceId);
            console.log('🔧 Valore di instanceId:', instanceId);
            
            // Debug: verifica se instanceId è definito
            if (typeof instanceId === 'undefined') {
                console.error('❌ ERRORE: instanceId è undefined!');
                alert('Errore: instanceId non definito');
                return;
            }
            
            // Debug: verifica selectedInstance
            if (selectedInstance) {
                const actualId = selectedInstance.getAttribute('data-instance-id');
                console.log('🔧 selectedInstance ID:', actualId);
            }
            
            const select = document.getElementById('template-select');
            const templateId = select ? select.value : null;
            
            console.log('🔧 Template selezionato ID:', templateId);
            
            if (!templateId) {
                alert('Seleziona prima un modello dalla lista');
                return;
            }
            
            if (!confirm('Applicare questo modello? La configurazione corrente verrà sostituita.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'apply_template');
            formData.append('instance_id', instanceId);
            formData.append('template_id', templateId);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('🔧 Risposta apply_template:', data);
                if (data.success) {
                    alert(data.message || 'Template applicato con successo!');
                    // Ricarica configurazione e preview
                    loadInstanceConfig(selectedInstance);
                    updateModulePreview();
                } else {
                    console.error('❌ Errore apply_template:', data.error);
                    alert('Errore: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                console.error('❌ Errore durante l\'applicazione del template:', error);
                alert('Errore durante l\'applicazione del modello: ' + error.message);
            });
        }
        
        // Modifica template globale (tutte le pagine)
        function editGlobalTemplate(templateId, moduleName) {
            if (!confirm('ATTENZIONE: Stai per modificare il modello globale.\n\nLe modifiche saranno applicate a TUTTE le pagine che usano questo modello.\n\nContinuare?')) {
                return;
            }
            
            // Mostra modal di modifica con campi abilitati
            showEditTemplateModal(templateId, moduleName);
        }
        
        // Modal per modificare template globale
        function showEditTemplateModal(templateId, moduleName) {
            // Per ora uso un prompt temporaneo
            // TODO: Creare modal dedicato con tutti i campi
            alert('Modal di modifica template in implementazione...\n\nPer ora, puoi:\n1. Staccare dal template\n2. Modificare\n3. Salvare come nuovo modello');
        }
        
        // Stacca modulo da template (crea istanza locale)
        function detachFromTemplate(instanceId) {
            if (!confirm('Vuoi scollegare questo modulo dal modello globale?\n\nIl modulo diventerà indipendente e personalizzabile solo per questa pagina.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'detach_from_template');
            formData.append('instance_id', instanceId);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Modulo scollegato con successo!');
                    // Ricarica configurazione per mostrare campi editabili
                    loadInstanceConfig(selectedInstance);
                } else {
                    alert('Errore: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore durante lo scollegamento: ' + error.message);
            });
        }
        
        // Toggle status pagina (Pubblica/Bozza)
        function togglePageStatus() {
            if (!currentPageId) {
                alert('Nessuna pagina selezionata');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'toggle_page_status');
            formData.append('page_id', currentPageId);
            
            // Mostra loading
            const btn = document.getElementById('publish-btn');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aggiornamento...';
            btn.disabled = true;
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Status aggiornato con successo!');
                    // Ricarica la pagina per aggiornare l'interfaccia
                    window.location.reload();
                } else {
                    alert('Errore: ' + (data.error || 'Errore sconosciuto'));
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore durante l\'aggiornamento dello status: ' + error.message);
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>
