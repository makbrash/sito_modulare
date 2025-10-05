<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/module_sync.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db instanceof \PDO) {
    throw new \RuntimeException('Impossibile connettersi al database.');
}

$summary = admin_sync_modules($db);

?><!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Moduli · Bologna Marathon</title>
    <link rel="stylesheet" href="../assets/css/core/variables.css">
    <style>
        body {
            font-family: var(--font-primary, 'Inter', sans-serif);
            background: #0b0b15;
            color: var(--text-secondary, #d1d5db);
            padding: 2rem;
        }
        h1, h2 {
            color: var(--text-primary, #fff);
        }
        .panel {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        ul {
            padding-left: 1.2rem;
        }
        .updates li {
            margin-bottom: 0.25rem;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }
        .badge--created { background: rgba(35, 168, 235, 0.15); color: var(--primary, #23a8eb); }
        .badge--updated { background: rgba(203, 223, 68, 0.15); color: var(--accent, #cbdf44); }
        .badge--deactivated { background: rgba(231, 76, 60, 0.15); color: var(--error, #e74c3c); }
        a {
            color: var(--primary, #23a8eb);
        }
    </style>
</head>
<body>
    <h1>Sincronizzazione moduli completata</h1>

    <section class="panel">
        <h2>Moduli trovati nel filesystem</h2>
        <ul>
            <?php foreach ($summary['filesystem'] as $module): ?>
                <li><?= htmlspecialchars($module) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="panel">
        <h2>Moduli presenti nel database</h2>
        <ul>
            <?php foreach ($summary['database'] as $module): ?>
                <li><?= htmlspecialchars($module) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="panel">
        <h2>Azioni eseguite</h2>
        <ul class="updates">
            <?php foreach ($summary['updates'] as $update): ?>
                <?php
                    $type = $update['type'];
                    $badgeClass = 'badge--updated';
                    $label = 'Aggiornato';
                    if ($type === 'created') {
                        $badgeClass = 'badge--created';
                        $label = 'Creato';
                    } elseif ($type === 'deactivated') {
                        $badgeClass = 'badge--deactivated';
                        $label = 'Disattivato';
                    }
                ?>
                <li><span class="badge <?= $badgeClass ?>"><?= $label ?></span> <?= htmlspecialchars($update['module']) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <p><a href="admin.php">⬅ Torna all'admin</a></p>
</body>
</html>
