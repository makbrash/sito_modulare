<?php
/**
 * Bootstrap File
 * Inizializza sistema: .env, error handling, logging
 * Include questo file all'inizio di ogni entry point
 */

// Carica autoloader se disponibile
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Carica .env loader custom
if (file_exists(__DIR__ . '/Utils/DotEnv.php')) {
    require_once __DIR__ . '/Utils/DotEnv.php';
    \BolognaMarathon\Utils\DotEnv::createImmutable(__DIR__ . '/..');
}

// Carica configurazione database
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
}

// Inizializza Logger
require_once __DIR__ . '/Utils/Logger.php';
$logger = new \BolognaMarathon\Utils\Logger();

// Inizializza Error Handler
require_once __DIR__ . '/Utils/ErrorHandler.php';
\BolognaMarathon\Utils\ErrorHandler::register($logger);

// Log avvio applicazione (solo in debug)
if (filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN)) {
    $logger->debug('Application bootstrap completed', [
        'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI'
    ]);
}

