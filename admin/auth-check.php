<?php
/**
 * Auth Check - Include in tutte le pagine admin protette
 * Verifica autenticazione se AUTH_ENABLED=true
 */

// Include solo se non già incluso
if (!defined('AUTH_CHECK_INCLUDED')) {
    define('AUTH_CHECK_INCLUDED', true);

    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../core/Auth/AuthService.php';
    require_once __DIR__ . '/../core/Auth/AuthMiddleware.php';

    use BolognaMarathon\Auth\AuthService;
    use BolognaMarathon\Auth\AuthMiddleware;

    // Inizializza servizi auth
    $database = new Database();
    $db = $database->getConnection();
    $authService = new AuthService($db);
    $authMiddleware = new AuthMiddleware($authService);

    // Verifica autenticazione (se auth enabled, redirect a login se necessario)
    $authMiddleware->handle();

    // Ottieni utente corrente (disponibile in tutte le pagine)
    $currentUser = $authService->getCurrentUser();
}

