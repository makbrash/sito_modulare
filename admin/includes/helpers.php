<?php
declare(strict_types=1);

/**
 * Raccolta di funzioni helper per l'area admin
 */

/**
 * Recupera tutte le pagine ordinate per titolo.
 */
function admin_get_pages(\PDO $db): array
{
    $stmt = $db->query('SELECT * FROM pages ORDER BY title');
    return $stmt->fetchAll() ?: [];
}

/**
 * Recupera l'elenco delle gare per popolare le select.
 */
function admin_get_races(\PDO $db): array
{
    $stmt = $db->query('SELECT id, name, date, status FROM races ORDER BY date DESC');
    return $stmt->fetchAll() ?: [];
}

/**
 * Recupera gli ultimi risultati con filtro opzionale per gara.
 */
function admin_get_results(\PDO $db, int $limit = 20, ?int $raceId = null): array
{
    $sql = 'SELECT * FROM race_results';
    $params = [];

    if ($raceId !== null) {
        $sql .= ' WHERE race_id = ?';
        $params[] = $raceId;
    }

    $sql .= ' ORDER BY race_id, position LIMIT ' . max(1, $limit);

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll() ?: [];
}

/**
 * Recupera gli ultimi contenuti dinamici.
 */
function admin_get_latest_content(\PDO $db, int $limit = 10): array
{
    $stmt = $db->prepare('SELECT * FROM dynamic_content ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, max(1, $limit), \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll() ?: [];
}

/**
 * Recupera i moduli registrati.
 */
function admin_get_modules(\PDO $db): array
{
    $stmt = $db->query('SELECT * FROM modules_registry ORDER BY name');
    return $stmt->fetchAll() ?: [];
}

/**
 * Carica il manifest del modulo se presente.
 */
function admin_load_module_manifest(string $slug): array
{
    $manifestPath = __DIR__ . '/../../modules/' . $slug . '/module.json';
    if (!file_exists($manifestPath)) {
        return [];
    }

    $json = json_decode((string)file_get_contents($manifestPath), true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
        return [];
    }

    return $json;
}

/**
 * Decodifica una stringa JSON restituendo un array associativo.
 */
function admin_decode_json(?string $value): array
{
    if ($value === null || trim($value) === '') {
        return [];
    }

    $decoded = json_decode($value, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        return [];
    }

    return $decoded;
}

/**
 * Gestisce la coda dei messaggi flash usando la sessione.
 */
function admin_get_flash(): ?array
{
    if (!isset($_SESSION['admin_flash'])) {
        return null;
    }

    $flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);

    return is_array($flash) ? $flash : null;
}

function admin_set_flash(string $type, string $message): void
{
    $_SESSION['admin_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Normalizza input booleani provenienti da checkbox.
 */
function admin_bool_from_request($value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_string($value)) {
        return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
    }

    if (is_numeric($value)) {
        return (int)$value === 1;
    }

    return false;
}

/**
 * Valida e restituisce metadati JSON dal form.
 *
 * @throws InvalidArgumentException
 */
function admin_parse_metadata(?string $metadata, bool $featured): array
{
    $base = ['featured' => $featured];

    if ($metadata === null || trim($metadata) === '') {
        return $base;
    }

    $decoded = json_decode($metadata, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        throw new InvalidArgumentException('I metadati devono essere un JSON valido.');
    }

    return array_merge($base, $decoded);
}

/**
 * Restituisce statistiche rapide per la dashboard.
 */
function admin_get_stats(array $pages, array $results, array $contents, array $modules): array
{
    return [
        'pages' => count($pages),
        'results' => count($results),
        'contents' => count($contents),
        'modules' => count($modules),
    ];
}

/**
 * Restituisce la stringa di classe per lo stato.
 */
function admin_status_class(bool $condition): string
{
    return $condition ? 'is-success' : 'is-danger';
}
