<?php

namespace BolognaMarathon\Auth;

/**
 * Authentication Middleware
 * Protegge route admin richiedendo autenticazione
 * 
 * NOTA: Attivo solo se AUTH_ENABLED=true
 */
class AuthMiddleware
{
    private $authService;
    private $publicRoutes = [
        '/admin/login.php',
        '/admin/api/auth/login.php',
        '/admin/assets/',
        '/assets/'
    ];

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Verifica accesso alla risorsa corrente
     */
    public function handle(): void
    {
        // Se auth disabilitato, permetti accesso
        if (!$this->authService->isAuthEnabled()) {
            return;
        }

        // Verifica se route è pubblica
        if ($this->isPublicRoute()) {
            return;
        }

        // Verifica autenticazione
        if (!$this->authService->isAuthenticated()) {
            $this->redirectToLogin();
            exit;
        }

        // Aggiorna attività sessione
        $this->updateSessionActivity();
    }

    /**
     * Richiede ruolo specifico
     */
    public function requireRole(string $role): void
    {
        if (!$this->authService->isAuthEnabled()) {
            return;
        }

        if (!$this->authService->hasRole($role)) {
            $this->handleUnauthorized();
            exit;
        }
    }

    /**
     * Richiede almeno uno dei ruoli
     */
    public function requireAnyRole(array $roles): void
    {
        if (!$this->authService->isAuthEnabled()) {
            return;
        }

        if (!$this->authService->hasAnyRole($roles)) {
            $this->handleUnauthorized();
            exit;
        }
    }

    /**
     * Verifica se route corrente è pubblica
     */
    private function isPublicRoute(): bool
    {
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        
        foreach ($this->publicRoutes as $route) {
            if (strpos($currentPath, $route) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redirect a login
     */
    private function redirectToLogin(): void
    {
        $returnUrl = $_SERVER['REQUEST_URI'] ?? '/admin/dashboard.php';
        header('Location: /admin/login.php?return=' . urlencode($returnUrl));
    }

    /**
     * Gestisce accesso non autorizzato
     */
    private function handleUnauthorized(): void
    {
        http_response_code(403);
        
        // Se è richiesta AJAX, ritorna JSON
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Accesso non autorizzato',
                'code' => 403
            ]);
        } else {
            // Altrimenti mostra pagina errore
            echo '<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accesso Negato</title>
    <style>
        body { font-family: system-ui; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f3f4f6; }
        .error-box { background: white; padding: 3rem; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; max-width: 400px; }
        h1 { color: #dc2626; margin: 0 0 1rem; font-size: 3rem; }
        p { color: #6b7280; margin: 0 0 1.5rem; }
        a { display: inline-block; padding: 0.75rem 1.5rem; background: #3b82f6; color: white; text-decoration: none; border-radius: 0.375rem; }
        a:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>403</h1>
        <p>Non hai i permessi necessari per accedere a questa risorsa.</p>
        <a href="/admin/dashboard.php">Torna alla Dashboard</a>
    </div>
</body>
</html>';
        }
    }

    /**
     * Verifica se è richiesta AJAX
     */
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Aggiorna attività sessione
     */
    private function updateSessionActivity(): void
    {
        if (isset($_SESSION['bm_admin_session'])) {
            $_SESSION['bm_admin_session']['last_activity'] = time();
        }
    }

    /**
     * Verifica CSRF token (per richieste POST/PUT/DELETE)
     */
    public function verifyCsrfToken(): bool
    {
        if (!$this->authService->isAuthEnabled()) {
            return true; // Skip CSRF se auth disabilitato
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return true; // Solo per richieste modificanti
        }

        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        $sessionToken = $_SESSION['csrf_token'] ?? null;

        return $token && $sessionToken && hash_equals($sessionToken, $token);
    }

    /**
     * Genera CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

