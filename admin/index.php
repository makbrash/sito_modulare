<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/PageService.php';
require_once __DIR__ . '/includes/ModuleService.php';
require_once __DIR__ . '/includes/ContentService.php';
require_once __DIR__ . '/includes/RaceService.php';

$db = admin_db();
$pageService = new PageService($db);
$moduleService = new ModuleService($db);
$contentService = new ContentService($db);
$raceService = new RaceService($db);

$initialData = [
    'pages' => $pageService->list(),
    'modules' => $moduleService->listRegistry(),
    'content' => $contentService->list(),
    'results' => $raceService->listResults(20),
    'races' => $raceService->listRaces(),
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Sito Modulare</title>
    <link rel="stylesheet" href="../assets/css/core/variables.css">
    <link rel="stylesheet" href="../assets/css/core/reset.css">
    <link rel="stylesheet" href="../assets/css/core/typography.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-7oOcdqUPVHtVHCnRvWmvMRGsiE9zraFMvx6bMpiKFFitvolG/G5hgbf+5Q5e0siJmq9hw3rro3AtxEAsC0RTw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" integrity="sha512-cP6os3OOrZssw4xZp6rLD/mBUK1sOcZIZf5jfXw3J5CVzGc7jqCOUMIqFZ3VAbbYSE7G3Zr1j9YxFf8+h3p1Vg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar" data-section="sidebar">
            <div class="admin-sidebar__brand">
                <span class="admin-sidebar__logo">⚙️</span>
                <div>
                    <strong>Sito Modulare</strong>
                    <small>Control center</small>
                </div>
            </div>
            <nav class="admin-nav">
                <button type="button" class="admin-nav__item" data-action="view" data-view="dashboard">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Dashboard</span>
                </button>
                <button type="button" class="admin-nav__item is-active" data-action="view" data-view="builder">
                    <i class="fa-solid fa-object-group"></i>
                    <span>Page Builder</span>
                </button>
                <button type="button" class="admin-nav__item" data-action="view" data-view="modules">
                    <i class="fa-solid fa-cubes"></i>
                    <span>Moduli</span>
                </button>
                <button type="button" class="admin-nav__item" data-action="view" data-view="content">
                    <i class="fa-solid fa-newspaper"></i>
                    <span>Contenuti</span>
                </button>
                <button type="button" class="admin-nav__item" data-action="view" data-view="results">
                    <i class="fa-solid fa-person-running"></i>
                    <span>Risultati</span>
                </button>
            </nav>
            <footer class="admin-sidebar__footer">
                <span>Versione admin 1.0</span>
                <a href="../index.php" target="_blank" rel="noopener">Apri sito</a>
            </footer>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <div class="admin-topbar__left">
                    <h1 class="admin-topbar__title">Area amministrazione</h1>
                    <p class="admin-topbar__subtitle">Gestisci pagine, moduli e contenuti del sito</p>
                </div>
                <div class="admin-topbar__actions">
                    <select class="admin-select" id="page-selector" aria-label="Seleziona pagina"></select>
                    <button type="button" class="admin-button" id="create-page">
                        <i class="fa-solid fa-plus"></i>
                        Nuova pagina
                    </button>
                    <button type="button" class="admin-button admin-button--ghost" id="refresh-data">
                        <i class="fa-solid fa-rotate"></i>
                        Aggiorna dati
                    </button>
                </div>
            </header>

            <section class="admin-content">
                <div class="admin-view" data-view="dashboard">
                    <div class="dashboard-grid">
                        <article class="metric-card">
                            <h3>Pagine</h3>
                            <p class="metric-card__value" data-metric="pages-count">0</p>
                        </article>
                        <article class="metric-card">
                            <h3>Moduli attivi</h3>
                            <p class="metric-card__value" data-metric="modules-count">0</p>
                        </article>
                        <article class="metric-card">
                            <h3>Contenuti</h3>
                            <p class="metric-card__value" data-metric="content-count">0</p>
                        </article>
                        <article class="metric-card">
                            <h3>Risultati</h3>
                            <p class="metric-card__value" data-metric="results-count">0</p>
                        </article>
                    </div>
                    <div class="dashboard-panels">
                        <section class="panel">
                            <header class="panel__header">
                                <h2>Pagine recenti</h2>
                            </header>
                            <ul class="simple-list" data-dashboard="pages"></ul>
                        </section>
                        <section class="panel">
                            <header class="panel__header">
                                <h2>Ultimi contenuti</h2>
                            </header>
                            <ul class="simple-list" data-dashboard="content"></ul>
                        </section>
                        <section class="panel">
                            <header class="panel__header">
                                <h2>Ultimi risultati</h2>
                            </header>
                            <ul class="simple-list" data-dashboard="results"></ul>
                        </section>
                    </div>
                </div>

                <div class="admin-view is-active" data-view="builder">
                    <div class="builder-layout">
                        <section class="panel panel--catalog">
                            <header class="panel__header">
                                <h2>Catalogo moduli</h2>
                                <p class="panel__subtitle">Trascina i moduli sul canvas o fai doppio click per aggiungerli</p>
                            </header>
                            <div id="modules-catalog" class="catalog"></div>
                        </section>
                        <section class="panel panel--canvas">
                            <header class="panel__header">
                                <h2>Canvas pagina</h2>
                                <p class="panel__subtitle">Ordina i moduli con drag & drop. Usa gli slot per annidare moduli.</p>
                            </header>
                            <div id="canvas-empty" class="empty-state" hidden>
                                <p>Nessun modulo presente nella pagina.</p>
                                <button type="button" class="admin-button" data-action="add-first">Aggiungi un modulo</button>
                            </div>
                            <div id="page-canvas" class="canvas" aria-live="polite"></div>
                        </section>
                        <section class="panel panel--inspector">
                            <header class="panel__header">
                                <h2>Ispettore modulo</h2>
                                <p class="panel__subtitle">Seleziona un modulo per modificarne le proprietà</p>
                            </header>
                            <div id="module-inspector" class="inspector"></div>
                        </section>
                    </div>
                </div>

                <div class="admin-view" data-view="modules">
                    <section class="panel">
                        <header class="panel__header">
                            <h2>Registro moduli</h2>
                            <p class="panel__subtitle">Attiva o disattiva i moduli disponibili</p>
                        </header>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Slug</th>
                                    <th>Classe CSS</th>
                                    <th>Versione</th>
                                    <th>Stato</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody id="modules-table"></tbody>
                        </table>
                    </section>
                </div>

                <div class="admin-view" data-view="content">
                    <section class="panel">
                        <header class="panel__header">
                            <h2>Contenuti dinamici</h2>
                            <button type="button" class="admin-button" id="create-content">Nuovo contenuto</button>
                        </header>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Titolo</th>
                                    <th>Stato</th>
                                    <th>Ultima modifica</th>
                                </tr>
                            </thead>
                            <tbody id="content-table"></tbody>
                        </table>
                    </section>
                </div>

                <div class="admin-view" data-view="results">
                    <section class="panel">
                        <header class="panel__header">
                            <h2>Risultati gare</h2>
                            <button type="button" class="admin-button" id="create-result">Nuovo risultato</button>
                        </header>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Gara</th>
                                    <th>Posizione</th>
                                    <th>Pettorale</th>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Tempo</th>
                                </tr>
                            </thead>
                            <tbody id="results-table"></tbody>
                        </table>
                    </section>
                </div>
            </section>
        </main>
    </div>

    <template id="module-card-template">
        <article class="module-card" draggable="true">
            <header class="module-card__header">
                <span class="module-card__icon"><i class="fa-solid fa-puzzle-piece"></i></span>
                <div class="module-card__info">
                    <strong class="module-card__title"></strong>
                    <small class="module-card__slug"></small>
                </div>
            </header>
            <p class="module-card__description"></p>
        </article>
    </template>

    <template id="canvas-item-template">
        <article class="canvas-item" data-instance-id="">
            <header class="canvas-item__header">
                <div class="canvas-item__title"></div>
                <div class="canvas-item__actions">
                    <button type="button" class="icon-button" data-action="select" title="Modifica">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button type="button" class="icon-button" data-action="duplicate" title="Duplica">
                        <i class="fa-solid fa-copy"></i>
                    </button>
                    <button type="button" class="icon-button" data-action="delete" title="Elimina">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </header>
            <div class="canvas-item__body">
                <div class="canvas-item__info"></div>
                <div class="canvas-item__children" data-slot="default">
                    <div class="canvas-dropzone" data-slot-placeholder>Trascina qui per annidare</div>
                </div>
            </div>
        </article>
    </template>

    <template id="child-item-template">
        <div class="child-item" data-child-index="">
            <span class="child-item__name"></span>
            <div class="child-item__actions">
                <button type="button" class="icon-button" data-action="child-select" title="Modifica">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button type="button" class="icon-button" data-action="child-delete" title="Rimuovi">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    </template>

    <template id="inspector-field-template">
        <label class="inspector-field">
            <span class="inspector-field__label"></span>
            <input class="inspector-field__control" type="text" />
        </label>
    </template>

    <template id="modal-template">
        <dialog class="admin-modal">
            <form method="dialog" class="admin-modal__container">
                <header class="admin-modal__header">
                    <h2 class="admin-modal__title"></h2>
                    <button type="submit" class="icon-button" value="cancel" title="Chiudi">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </header>
                <div class="admin-modal__body"></div>
                <footer class="admin-modal__footer">
                    <button type="submit" class="admin-button admin-button--ghost" value="cancel">Annulla</button>
                    <button type="submit" class="admin-button" value="confirm">Conferma</button>
                </footer>
            </form>
        </dialog>
    </template>

    <script>window.ADMIN_BOOTSTRAP = <?= json_encode($initialData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;</script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js" integrity="sha512-iSiRFM6DPd7OJbRRqtD9h5pz50jdK5Zk90un0nLBKBPXn1HULICwhf66A1VpzwuNFuIBqmoeZaZX6mE6xPD58w==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script type="module" src="assets/app.js"></script>
</body>
</html>
