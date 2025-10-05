<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$database = new Database();
$db = $database->getConnection();

if (!$db instanceof \PDO) {
    throw new \RuntimeException('Impossibile stabilire la connessione al database per l\'area admin.');
}

return $db;
