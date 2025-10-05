<?php
declare(strict_types=1);

/**
 * Pannello di amministrazione - Bologna Marathon
 * Gestione modulare di pagine, contenuti e moduli riutilizzabili
 */

$db = require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/module_sync.php';

$view = $_GET['view'] ?? 'dashboard';
$selectedRaceId = isset($_GET['race']) ? max(0, (int)$_GET['race']) : null;
$flash = admin_get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $intent = $_POST['intent'] ?? '';
    $redirectView = $_POST['return_view'] ?? $view;

    try {
        switch ($intent) {
            case 'add_result':
                $raceId = (int)($_POST['race_id'] ?? 0);
                $position = (int)($_POST['position'] ?? 0);
                $bib = trim((string)($_POST['bib_number'] ?? ''));
                $runner = trim((string)($_POST['runner_name'] ?? ''));
                $category = trim((string)($_POST['category'] ?? ''));
                $time = trim((string)($_POST['time_result'] ?? ''));

                if ($raceId <= 0) {
                    throw new InvalidArgumentException('Seleziona una gara valida prima di salvare un risultato.');
                }

                if ($position <= 0 || $runner === '' || $time === '') {
                    throw new InvalidArgumentException('Compila tutti i campi obbligatori per il risultato.');
                }

                $stmt = $db->prepare('INSERT INTO race_results (race_id, position, bib_number, runner_name, category, time_result) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$raceId, $position, $bib, $runner, $category, $time]);

                admin_set_flash('success', 'Risultato inserito correttamente.');
                break;

            case 'add_content':
                $contentType = trim((string)($_POST['content_type'] ?? ''));
                $title = trim((string)($_POST['title'] ?? ''));
                $content = trim((string)($_POST['content'] ?? ''));
                $featured = admin_bool_from_request($_POST['featured'] ?? false);
                $metadata = admin_parse_metadata($_POST['metadata'] ?? null, $featured);

                if ($contentType === '' || $content === '') {
                    throw new InvalidArgumentException('Specificare almeno il tipo di contenuto e il corpo testuale.');
                }

                $stmt = $db->prepare('INSERT INTO dynamic_content (content_type, title, content, metadata) VALUES (?, ?, ?, ?)');
                $stmt->execute([$contentType, $title, $content, json_encode($metadata, JSON_UNESCAPED_UNICODE)]);

                admin_set_flash('success', 'Contenuto creato con successo.');
                break;

            case 'update_page':
                $pageId = (int)($_POST['page_id'] ?? 0);
                $title = trim((string)($_POST['title'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $cssVariables = trim((string)($_POST['css_variables'] ?? ''));

                if ($pageId <= 0) {
                    throw new InvalidArgumentException('Pagina non valida.');
                }

                $decodedVariables = admin_decode_json($cssVariables);
                $stmt = $db->prepare('UPDATE pages SET title = ?, description = ?, css_variables = ? WHERE id = ?');
                $stmt->execute([$title, $description, json_encode($decodedVariables, JSON_UNESCAPED_UNICODE), $pageId]);

                admin_set_flash('success', 'Pagina aggiornata con successo.');
                break;

            case 'toggle_module':
                $moduleName = trim((string)($_POST['module_name'] ?? ''));
                $isActive = admin_bool_from_request($_POST['is_active'] ?? false);

                if ($moduleName === '') {
                    throw new InvalidArgumentException('Modulo non valido.');
                }

                $stmt = $db->prepare('UPDATE modules_registry SET is_active = ? WHERE name = ?');
                $stmt->execute([$isActive ? 1 : 0, $moduleName]);

                admin_set_flash('success', sprintf("Modulo '%s' %s.", $moduleName, $isActive ? 'attivato' : 'disattivato'));
                break;

            case 'sync_modules':
                admin_sync_modules($db);
                admin_set_flash('success', 'Sincronizzazione moduli completata.');
                break;

            default:
                admin_set_flash('error', 'Azione non riconosciuta.');
                break;
        }
    } catch (Throwable $exception) {
        admin_set_flash('error', $exception->getMessage());
    }

    $query = ['view' => $redirectView];
    if ($redirectView === 'results' && $selectedRaceId) {
        $query['race'] = $selectedRaceId;
    }

    header('Location: admin.php?' . http_build_query($query));
    exit;
}

$pages = admin_get_pages($db);
$races = admin_get_races($db);
$results = admin_get_results($db, 20, $selectedRaceId && $selectedRaceId > 0 ? $selectedRaceId : null);
$contents = admin_get_latest_content($db);
$modules = admin_get_modules($db);
$stats = admin_get_stats($pages, $results, $contents, $modules);

$moduleCards = array_map(function (array $module): array {
    $manifest = admin_load_module_manifest($module['name']);
    $defaultConfig = admin_decode_json($module['default_config'] ?? '');

    return [
        'data' => $module,
        'manifest' => $manifest,
        'default_config' => $defaultConfig,
    ];
}, $modules);

$navItems = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => 'fa-solid fa-chart-line'],
    'results' => ['label' => 'Risultati', 'icon' => 'fa-solid fa-stopwatch'],
    'content' => ['label' => 'Contenuti', 'icon' => 'fa-solid fa-pen-to-square'],
    'pages' => ['label' => 'Pagine', 'icon' => 'fa-solid fa-file-lines'],
    'modules' => ['label' => 'Moduli', 'icon' => 'fa-solid fa-puzzle-piece'],
    'page-builder' => ['label' => 'Page Builder', 'icon' => 'fa-solid fa-object-group', 'href' => 'page-builder.php'],
];

$allowedViews = ['dashboard', 'results', 'content', 'pages', 'modules'];
if (!in_array($view, $allowedViews, true)) {
    $view = 'dashboard';
}

$currentNav = $navItems[$view];

function admin_is_active_view(string $current, string $view): bool
{
    return $current === $view;
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel · Bologna Marathon</title>
    <link rel="stylesheet" href="../assets/css/core/variables.css">
    <link rel="stylesheet" href="../assets/css/core/reset.css">
    <link rel="stylesheet" href="../assets/css/core/typography.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-gZf3kWk7VdzS4EAlnXrhIRbkIuAeGHNirMRHkRkNvztNFVQVw1Gc7YCOUMIqFZRMVAbwY/jGj33jjXNpM4sK8A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <span class="admin-brand__badge">BM</span>
                <div class="admin-brand__copy">
                    <strong>Bologna Marathon</strong>
                    <small>Control Center</small>
                </div>
            </div>
            <nav class="admin-menu" aria-label="Sezioni amministrazione">
                <?php foreach ($navItems as $key => $item): ?>
                    <?php $href = $item['href'] ?? ('admin.php?view=' . $key); ?>
                    <a class="admin-menu__link <?= admin_is_active_view($view, $key) ? 'is-active' : '' ?>" href="<?= htmlspecialchars($href) ?>">
                        <i class="<?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
                <a class="admin-menu__link" href="../index.php" target="_blank" rel="noopener">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    <span>Vai al sito</span>
                </a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <div class="admin-header__titles">
                    <h1><?= htmlspecialchars($currentNav['label']) ?></h1>
                    <p>Gestisci moduli, contenuti e pagine del sito bolognamarathon.run</p>
                </div>
                <div class="admin-header__actions">
                    <form method="post" class="admin-inline-form">
                        <input type="hidden" name="intent" value="sync_modules">
                        <input type="hidden" name="return_view" value="<?= htmlspecialchars($view) ?>">
                        <button class="btn btn-secondary" type="submit">
                            <i class="fa-solid fa-rotate"></i>
                            <span>Sincronizza moduli</span>
                        </button>
                    </form>
                    <a class="btn btn-primary" href="page-builder.php">
                        <i class="fa-solid fa-object-group"></i>
                        <span>Apri Page Builder</span>
                    </a>
                </div>
            </header>

            <?php if ($flash): ?>
                <div class="admin-alert <?= $flash['type'] === 'success' ? 'is-success' : 'is-danger' ?>">
                    <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($view === 'dashboard'): ?>
                <section class="panel">
                    <h2 class="panel__title">Indicatori rapidi</h2>
                    <div class="stats-grid">
                        <article class="stat-card">
                            <span class="stat-card__label">Pagine</span>
                            <span class="stat-card__value"><?= $stats['pages'] ?></span>
                        </article>
                        <article class="stat-card">
                            <span class="stat-card__label">Risultati</span>
                            <span class="stat-card__value"><?= $stats['results'] ?></span>
                        </article>
                        <article class="stat-card">
                            <span class="stat-card__label">Contenuti</span>
                            <span class="stat-card__value"><?= $stats['contents'] ?></span>
                        </article>
                        <article class="stat-card">
                            <span class="stat-card__label">Moduli</span>
                            <span class="stat-card__value"><?= $stats['modules'] ?></span>
                        </article>
                    </div>
                </section>

                <section class="panel">
                    <h2 class="panel__title">Attività recenti</h2>
                    <div class="panel__split">
                        <div>
                            <h3 class="panel__subtitle">Ultimi contenuti</h3>
                            <ul class="list">
                                <?php foreach (array_slice($contents, 0, 5) as $content): ?>
                                    <li class="list__item">
                                        <span><?= htmlspecialchars($content['title'] ?: $content['content_type']) ?></span>
                                        <small><?= date('d/m/Y H:i', strtotime($content['created_at'])) ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div>
                            <h3 class="panel__subtitle">Ultimi moduli usati</h3>
                            <ul class="list">
                                <?php foreach (array_slice($modules, 0, 5) as $module): ?>
                                    <li class="list__item">
                                        <span><?= htmlspecialchars($module['name']) ?></span>
                                        <small><?= $module['is_active'] ? 'Attivo' : 'Disattivato' ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($view === 'results'): ?>
                <section class="panel">
                    <h2 class="panel__title">Nuovo risultato</h2>
                    <form method="post" class="form-grid">
                        <input type="hidden" name="intent" value="add_result">
                        <input type="hidden" name="return_view" value="results">
                        <div class="form-field">
                            <label for="race_id">Gara</label>
                            <select id="race_id" name="race_id" required>
                                <option value="">Seleziona una gara</option>
                                <?php foreach ($races as $race): ?>
                                    <option value="<?= (int)$race['id'] ?>"><?= htmlspecialchars($race['name']) ?> · <?= date('d/m/Y', strtotime($race['date'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="position">Posizione</label>
                            <input type="number" min="1" id="position" name="position" required>
                        </div>
                        <div class="form-field">
                            <label for="bib_number">Pettorale</label>
                            <input type="text" id="bib_number" name="bib_number" placeholder="Es. 1023">
                        </div>
                        <div class="form-field">
                            <label for="runner_name">Runner</label>
                            <input type="text" id="runner_name" name="runner_name" required>
                        </div>
                        <div class="form-field">
                            <label for="category">Categoria</label>
                            <input type="text" id="category" name="category" placeholder="Es. F40">
                        </div>
                        <div class="form-field">
                            <label for="time_result">Tempo (HH:MM:SS)</label>
                            <input type="time" id="time_result" name="time_result" step="1" required>
                        </div>
                        <div class="form-field form-field--full">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i>
                                <span>Aggiungi risultato</span>
                            </button>
                        </div>
                    </form>
                </section>

                <section class="panel">
                    <div class="panel__heading">
                        <h2 class="panel__title">Risultati recenti</h2>
                        <form class="admin-inline-form" method="get">
                            <input type="hidden" name="view" value="results">
                            <label for="race-filter" class="sr-only">Filtra per gara</label>
                            <select id="race-filter" name="race" onchange="this.form.submit()">
                                <option value="">Tutte le gare</option>
                                <?php foreach ($races as $race): ?>
                                    <option value="<?= (int)$race['id'] ?>" <?= $selectedRaceId === (int)$race['id'] ? 'selected' : '' ?>><?= htmlspecialchars($race['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Posizione</th>
                                    <th>Runner</th>
                                    <th>Categoria</th>
                                    <th>Tempo</th>
                                    <th>Gara</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): ?>
                                    <tr>
                                        <td><?= (int)$result['position'] ?></td>
                                        <td><?= htmlspecialchars($result['runner_name']) ?></td>
                                        <td><?= htmlspecialchars($result['category']) ?></td>
                                        <td><?= htmlspecialchars($result['time_result']) ?></td>
                                        <td>#<?= (int)$result['race_id'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($view === 'content'): ?>
                <section class="panel">
                    <h2 class="panel__title">Nuovo contenuto dinamico</h2>
                    <form method="post" class="form-grid">
                        <input type="hidden" name="intent" value="add_content">
                        <input type="hidden" name="return_view" value="content">
                        <div class="form-field">
                            <label for="content_type">Tipo</label>
                            <input type="text" id="content_type" name="content_type" placeholder="es. news" required>
                        </div>
                        <div class="form-field">
                            <label for="title">Titolo</label>
                            <input type="text" id="title" name="title" placeholder="Titolo opzionale">
                        </div>
                        <div class="form-field form-field--full">
                            <label for="content">Contenuto</label>
                            <textarea id="content" name="content" rows="4" required></textarea>
                        </div>
                        <div class="form-field">
                            <label for="metadata">Metadati JSON (opz.)</label>
                            <textarea id="metadata" name="metadata" rows="3" placeholder='{"cta":"Iscriviti ora"}'></textarea>
                        </div>
                        <div class="form-field">
                            <label class="form-checkbox">
                                <input type="checkbox" name="featured" value="1">
                                <span>Metti in evidenza</span>
                            </label>
                        </div>
                        <div class="form-field form-field--full">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Salva contenuto</span>
                            </button>
                        </div>
                    </form>
                </section>

                <section class="panel">
                    <h2 class="panel__title">Ultimi contenuti</h2>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Titolo</th>
                                    <th>Creato</th>
                                    <th>Metadati</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contents as $content): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($content['content_type']) ?></td>
                                        <td><?= htmlspecialchars($content['title'] ?: '—') ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($content['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-tertiary" data-json-toggle type="button">Vedi</button>
                                            <pre class="json-preview" hidden><?= htmlspecialchars(json_encode(admin_decode_json($content['metadata']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($view === 'pages'): ?>
                <section class="panel">
                    <h2 class="panel__title">Pagina home</h2>
                    <?php $homePage = $pages[0] ?? null; ?>
                    <?php if ($homePage): ?>
                        <form method="post" class="form-grid">
                            <input type="hidden" name="intent" value="update_page">
                            <input type="hidden" name="return_view" value="pages">
                            <input type="hidden" name="page_id" value="<?= (int)$homePage['id'] ?>">
                            <div class="form-field">
                                <label for="page-title">Titolo</label>
                                <input type="text" id="page-title" name="title" value="<?= htmlspecialchars($homePage['title']) ?>" required>
                            </div>
                            <div class="form-field form-field--full">
                                <label for="page-description">Descrizione</label>
                                <textarea id="page-description" name="description" rows="3" required><?= htmlspecialchars($homePage['description']) ?></textarea>
                            </div>
                            <div class="form-field form-field--full">
                                <label for="page-css">CSS Variables (JSON)</label>
                                <textarea id="page-css" name="css_variables" rows="4" placeholder="{}"><?= htmlspecialchars($homePage['css_variables'] ?? '{}') ?></textarea>
                            </div>
                            <div class="form-field form-field--full">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    <span>Salva modifiche</span>
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <p>Nessuna pagina pubblicata trovata.</p>
                    <?php endif; ?>
                </section>

                <section class="panel">
                    <h2 class="panel__title">Pagine disponibili</h2>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Slug</th>
                                    <th>Titolo</th>
                                    <th>Status</th>
                                    <th>Template</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pages as $page): ?>
                                    <tr>
                                        <td><?= (int)$page['id'] ?></td>
                                        <td><?= htmlspecialchars($page['slug']) ?></td>
                                        <td><?= htmlspecialchars($page['title']) ?></td>
                                        <td><span class="badge <?= admin_status_class($page['status'] === 'published') ?>"><?= htmlspecialchars($page['status']) ?></span></td>
                                        <td><?= htmlspecialchars($page['template']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($view === 'modules'): ?>
                <section class="panel">
                    <h2 class="panel__title">Moduli disponibili</h2>
                    <div class="module-grid">
                        <?php foreach ($moduleCards as $module): ?>
                            <?php $data = $module['data']; ?>
                            <article class="module-card <?= $data['is_active'] ? '' : 'is-inactive' ?>">
                                <header class="module-card__header">
                                    <div>
                                        <h3><?= htmlspecialchars($data['name']) ?></h3>
                                        <span class="module-card__path"><?= htmlspecialchars($data['component_path']) ?></span>
                                    </div>
                                    <form method="post" class="toggle-form">
                                        <input type="hidden" name="intent" value="toggle_module">
                                        <input type="hidden" name="return_view" value="modules">
                                        <input type="hidden" name="module_name" value="<?= htmlspecialchars($data['name']) ?>">
                                        <input type="hidden" name="is_active" value="<?= $data['is_active'] ? '0' : '1' ?>">
                                        <button type="submit" class="btn <?= $data['is_active'] ? 'btn-secondary' : 'btn-primary' ?>" data-confirm>
                                            <?= $data['is_active'] ? 'Disattiva' : 'Attiva' ?>
                                        </button>
                                    </form>
                                </header>
                                <dl class="module-meta">
                                    <div>
                                        <dt>Classe CSS</dt>
                                        <dd><?= htmlspecialchars($data['css_class'] ?: '—') ?></dd>
                                    </div>
                                    <div>
                                        <dt>Config default</dt>
                                        <dd><pre><?= htmlspecialchars(json_encode($module['default_config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre></dd>
                                    </div>
                                </dl>
                                <?php if (!empty($module['manifest'])): ?>
                                    <details class="module-manifest">
                                        <summary>Manifest</summary>
                                        <pre><?= htmlspecialchars(json_encode($module['manifest'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                    </details>
                                <?php else: ?>
                                    <p class="module-card__note">Manifest non disponibile.</p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
    <script src="assets/js/admin.js"></script>
</body>
</html>
