<?php
declare(strict_types=1);

/**
 * Common bootstrap utilities for the admin application.
 */

require_once __DIR__ . '/../../config/database.php';

if (!defined('ADMIN_APP_START')) {
    define('ADMIN_APP_START', microtime(true));
}

/**
 * Returns a shared PDO connection.
 */
function admin_db(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $database = new Database();
    $connection = $database->getConnection();

    if (!$connection) {
        throw new RuntimeException('Impossibile connettersi al database.');
    }

    return $connection;
}

/**
 * Reads a JSON request payload and returns it as an associative array.
 */
function admin_read_json(): array
{
    $input = file_get_contents('php://input');
    if ($input === false || $input === '') {
        return [];
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Payload JSON non valido: ' . json_last_error_msg());
    }

    return $data;
}

/**
 * Sends a JSON response with the given HTTP status code.
 */
function admin_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
}

/**
 * Ensures that required keys are present inside the provided data array.
 */
function admin_require(array $data, array $requiredKeys): void
{
    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $data)) {
            throw new RuntimeException("Campo mancante: {$key}");
        }
    }
}

/**
 * Normalises a config array by removing null values.
 */
function admin_normalize_config(array $config): array
{
    return array_filter(
        $config,
        static fn ($value) => $value !== null && $value !== '' && $value !== [],
    );
}
