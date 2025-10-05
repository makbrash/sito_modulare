<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/ModuleRenderer.php';

$database = new Database();
$db = $database->getConnection();
$renderer = new ModuleRenderer($db);

$pageId = (int)($_GET['page_id'] ?? 0);

$pagesStmt = $db->query('SELECT id, title, slug, status FROM pages ORDER BY title');
$pages = $pagesStmt->fetchAll() ?: [];

if ($pageId <= 0 && count($pages) > 0) {
    $pageId = (int)$pages[0]['id'];
}

$currentPage = null;
if ($pageId > 0) {
    $pageStmt = $db->prepare('SELECT id, title, slug, status FROM pages WHERE id = ?');
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
    $instancesStmt = $db->prepare('SELECT id, module_name, instance_name, config, order_index, parent_instance_id, is_active FROM module_instances WHERE page_id = ? ORDER BY order_index');
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
            'parent_instance_id' => $instance['parent_instance_id'] !== null ? (int)$instance['parent_instance_id'] : null,
            'html' => $html,
            'config' => $merged,
        ];
    }
}

$moduleTree = $renderer->buildModuleInstanceTree($instances);

$initialPayload = [
    'pages' => $pages,
    'currentPageId' => $pageId,
    'currentPage' => $currentPage,
    'moduleInstances' => $instances,
    'moduleTree' => $moduleTree,
    'availableModules' => $availableModules,
];

$initialJson = htmlspecialchars(json_encode($initialPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Builder Pagine · Bologna Marathon</title>
    <link rel="stylesheet" href="../assets/css/core/variables.css">
    <link rel="stylesheet" href="../assets/css/core/reset.css">
    <link rel="stylesheet" href="../assets/css/core/typography.css">
    <link rel="stylesheet" href="../assets/css/core/fonts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --pb-bg: #0d1117;
            --pb-surface: rgba(255, 255, 255, 0.04);
            --pb-surface-strong: rgba(255, 255, 255, 0.08);
            --pb-border: rgba(255, 255, 255, 0.1);
            --pb-text: #d6d7dc;
            --pb-text-strong: #ffffff;
            --pb-accent: #23a8eb;
            --pb-accent-soft: rgba(35, 168, 235, 0.15);
            --pb-danger: #ff5678;
            --pb-success: #2ecc71;
            --pb-warning: #f1c40f;
            --pb-radius: 18px;
            --pb-sidebar-width: 260px;
            --pb-inspector-width: 320px;
        }

        body {
            background: var(--pb-bg);
            color: var(--pb-text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        .pb-shell {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .pb-header {
            padding: 1.5rem 2rem 1rem;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1.5rem;
            align-items: end;
        }

        .pb-header__titles h1 {
            font-size: 1.75rem;
            color: var(--pb-text-strong);
            margin-bottom: 0.35rem;
        }

        .pb-header__titles p {
            margin: 0;
            color: rgba(255, 255, 255, 0.65);
            font-size: 0.95rem;
        }

        .pb-header__actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            align-items: flex-end;
        }

        .pb-page-switcher {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .pb-page-switcher select {
            background: var(--pb-surface);
            border: 1px solid var(--pb-border);
            color: var(--pb-text-strong);
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            min-width: 240px;
            font-size: 0.95rem;
        }

        .pb-page-switcher button {
            border: none;
            background: var(--pb-surface-strong);
            color: var(--pb-text);
            padding: 0.6rem 0.85rem;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .pb-page-switcher button:hover {
            background: var(--pb-accent-soft);
            color: var(--pb-text-strong);
        }

        .pb-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .pb-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.75rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            font-size: 0.8rem;
            letter-spacing: 0.02em;
        }

        .pb-badge i { font-size: 0.85rem; }

        .pb-main {
            flex: 1;
            display: grid;
            grid-template-columns: var(--pb-sidebar-width) 1fr var(--pb-inspector-width);
            gap: 1.25rem;
            padding: 0 2rem 2rem;
        }

        .pb-sidebar,
        .pb-inspector {
            background: var(--pb-surface);
            border: 1px solid var(--pb-border);
            border-radius: var(--pb-radius);
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            backdrop-filter: blur(12px);
        }

        .pb-sidebar__header,
        .pb-inspector__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--pb-text-strong);
        }

        .pb-module-search {
            position: relative;
        }

        .pb-module-search input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--pb-border);
            border-radius: 10px;
            padding: 0.6rem 0.85rem 0.6rem 2.25rem;
            color: var(--pb-text-strong);
            font-size: 0.9rem;
        }

        .pb-module-search i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
        }

        .pb-modules__list {
            flex: 1;
            overflow-y: auto;
            padding-right: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .pb-module-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px dashed transparent;
            border-radius: 14px;
            padding: 0.9rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            cursor: grab;
            transition: transform 0.2s ease, border 0.2s ease, background 0.2s ease;
        }

        .pb-module-card:hover {
            border-color: rgba(35, 168, 235, 0.5);
            background: rgba(35, 168, 235, 0.08);
            transform: translateX(4px);
        }

        .pb-module-card strong {
            color: var(--pb-text-strong);
        }

        .pb-stage-panel {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .pb-stage-toolbar {
            background: var(--pb-surface);
            border: 1px solid var(--pb-border);
            border-radius: var(--pb-radius);
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .pb-stage-toolbar__actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .pb-stage-toolbar__actions button {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--pb-border);
            color: var(--pb-text);
            padding: 0.5rem 0.85rem;
            border-radius: 10px;
            cursor: pointer;
        }

        .pb-stage-toolbar__actions button.is-active {
            background: var(--pb-accent-soft);
            color: var(--pb-text-strong);
            border-color: rgba(35, 168, 235, 0.6);
        }

        .pb-stage {
            flex: 1;
            background: rgba(13, 17, 23, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: var(--pb-radius);
            padding: 1.5rem;
            overflow-y: auto;
            min-height: 60vh;
        }

        .pb-stage-empty {
            border: 1px dashed rgba(255, 255, 255, 0.2);
            border-radius: var(--pb-radius);
            padding: 3rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.55);
        }

        .pb-node {
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 18px;
            margin-bottom: 1.25rem;
            background: rgba(13, 17, 23, 0.65);
            overflow: hidden;
            position: relative;
        }

        .pb-node__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.04);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .pb-node__title {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .pb-node__title span {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .pb-node__controls {
            display: flex;
            gap: 0.5rem;
        }

        .pb-node__controls button {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid transparent;
            color: var(--pb-text);
            padding: 0.35rem 0.6rem;
            border-radius: 8px;
            cursor: pointer;
        }

        .pb-node__controls button:hover {
            border-color: rgba(35, 168, 235, 0.6);
            color: var(--pb-text-strong);
        }

        .pb-node__preview {
            padding: 1rem;
            background: rgba(13, 17, 23, 0.85);
        }

        .pb-node__preview[contenteditable="true"] {
            outline: 2px dashed rgba(35, 168, 235, 0.75);
            outline-offset: 6px;
        }

        .pb-children {
            padding: 1rem 1rem 0.5rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .pb-drop-target {
            min-height: 30px;
        }

        .pb-drop-placeholder {
            border: 2px dashed rgba(35, 168, 235, 0.75);
            border-radius: 12px;
            height: 60px;
            margin: 0.5rem 0;
        }

        .pb-node.is-selected {
            border-color: rgba(35, 168, 235, 0.8);
            box-shadow: 0 0 0 2px rgba(35, 168, 235, 0.2);
        }

        .pb-inspector__body {
            flex: 1;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .pb-form-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            margin-bottom: 0.85rem;
        }

        .pb-form-group label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.75);
        }

        .pb-form-group input,
        .pb-form-group textarea,
        .pb-form-group select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--pb-border);
            border-radius: 10px;
            color: var(--pb-text-strong);
            padding: 0.6rem 0.8rem;
            font-size: 0.9rem;
        }

        .pb-form-group textarea {
            min-height: 90px;
            resize: vertical;
        }

        .pb-inspector__footer {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: auto;
        }

        .pb-inspector__footer button {
            background: var(--pb-accent);
            color: #fff;
            border: none;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }

        .pb-inspector__footer button.secondary {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--pb-border);
            color: var(--pb-text);
        }

        .pb-toast {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            background: rgba(18, 23, 32, 0.92);
            border: 1px solid rgba(35, 168, 235, 0.45);
            color: #fff;
            padding: 0.85rem 1.1rem;
            border-radius: 12px;
            display: flex;
            gap: 0.65rem;
            align-items: center;
            box-shadow: 0 18px 32px rgba(0, 0, 0, 0.35);
            z-index: 2000;
        }

        .pb-toast.pb-toast--error {
            border-color: rgba(255, 86, 120, 0.7);
        }

        .pb-toast.pb-toast--success {
            border-color: rgba(46, 204, 113, 0.65);
        }

        .pb-status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.3rem;
        }

        .pb-status-dot.success { background: var(--pb-success); }
        .pb-status-dot.warning { background: var(--pb-warning); }
        .pb-status-dot.danger { background: var(--pb-danger); }

        @media (max-width: 1360px) {
            .pb-main {
                grid-template-columns: var(--pb-sidebar-width) 1fr;
                grid-template-rows: auto auto;
            }
            .pb-inspector {
                grid-column: 1 / -1;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="pb-shell">
        <header class="pb-header">
            <div class="pb-header__titles">
                <h1><i class="fa-solid fa-object-group"></i> Builder visivo</h1>
                <p>Organizza moduli con drag & drop, modifica inline e gestisci le pagine del sito</p>
            </div>
            <div class="pb-header__actions">
                <div class="pb-page-switcher">
                    <select id="pb-page-selector"></select>
                    <button id="pb-create-page" title="Nuova pagina"><i class="fa-solid fa-plus"></i></button>
                    <button id="pb-rename-page" title="Rinomina pagina"><i class="fa-solid fa-pen"></i></button>
                    <button id="pb-delete-page" title="Elimina pagina"><i class="fa-solid fa-trash"></i></button>
                </div>
                <div class="pb-badges" id="pb-change-badges"></div>
            </div>
        </header>

        <main class="pb-main">
            <aside class="pb-sidebar">
                <div class="pb-sidebar__header">
                    <strong>Libreria moduli</strong>
                    <span id="pb-module-count"></span>
                </div>
                <div class="pb-module-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" id="pb-module-search" placeholder="Cerca modulo per nome, tag o categoria">
                </div>
                <div class="pb-modules__list" id="pb-modules"></div>
            </aside>

            <section class="pb-stage-panel">
                <div class="pb-stage-toolbar">
                    <div class="pb-stage-toolbar__info">
                        <strong id="pb-current-page-name"></strong>
                        <span id="pb-current-page-slug" style="display:block;font-size:0.85rem;color:rgba(255,255,255,0.5);"></span>
                    </div>
                    <div class="pb-stage-toolbar__actions">
                        <button id="pb-inline-toggle"><i class="fa-solid fa-i-cursor"></i> Inline edit</button>
                        <button id="pb-preview-page"><i class="fa-solid fa-eye"></i> Anteprima</button>
                    </div>
                </div>
                <div class="pb-stage pb-drop-target" id="pb-stage" data-parent="root"></div>
            </section>

            <aside class="pb-inspector">
                <div class="pb-inspector__header">
                    <strong>Inspector</strong>
                    <span id="pb-selected-label" style="font-size:0.85rem;color:rgba(255,255,255,0.5);"></span>
                </div>
                <div class="pb-inspector__body" id="pb-inspector-body">
                    <p>Seleziona un modulo sullo stage per modificarne le proprietà oppure attiva l'inline editing.</p>
                </div>
                <div class="pb-inspector__footer">
                    <button id="pb-save-config" style="display:none;"><i class="fa-solid fa-floppy-disk"></i> Salva configurazione</button>
                    <button id="pb-cancel-inline" class="secondary" style="display:none;">Annulla modifiche inline</button>
                </div>
            </aside>
        </main>
    </div>

    <div id="pb-toast" class="pb-toast" style="display:none;"></div>

    <script src="../node_modules/sortablejs/Sortable.min.js"></script>
    <script>
        const PB_INITIAL_STATE = JSON.parse('<?php echo $initialJson; ?>');
    </script>
    <script src="assets/js/page-builder.js"></script>
</body>
</html>
