<?php
/**
 * Auth API Endpoint
 * Gestione autenticazione via API
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/API/BaseController.php';
require_once __DIR__ . '/../../core/Auth/AuthService.php';

use BolognaMarathon\API\BaseController;
use BolognaMarathon\Auth\AuthService;

$database = new Database();
$db = $database->getConnection();
$authService = new AuthService($db);

class AuthController extends BaseController
{
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handleRequest(): void
    {
        $method = $this->getMethod();
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'login':
                if ($method !== 'POST') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->login();
                break;

            case 'logout':
                if ($method !== 'POST') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->logout();
                break;

            case 'me':
                if ($method !== 'GET') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->getCurrentUser();
                break;

            case 'change-password':
                if ($method !== 'POST') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->changePassword();
                break;

            case 'status':
                $this->getAuthStatus();
                break;

            default:
                $this->sendError('Action not found', 404);
                break;
        }
    }

    private function login(): void
    {
        $data = $this->getJsonInput();
        
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->sendError('Username e password sono obbligatori', 400);
        }

        $result = $this->authService->login($username, $password);

        if ($result['success']) {
            $this->sendResponse($result, 200);
        } else {
            $this->sendError($result['message'], 401);
        }
    }

    private function logout(): void
    {
        $this->authService->logout();
        $this->sendResponse(['message' => 'Logout effettuato con successo']);
    }

    private function getCurrentUser(): void
    {
        $user = $this->authService->getCurrentUser();
        
        if (!$user) {
            $this->sendError('Non autenticato', 401);
        }

        $this->sendResponse(['user' => $user]);
    }

    private function changePassword(): void
    {
        $data = $this->getJsonInput();
        
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            $this->sendError('Non autenticato', 401);
        }

        $oldPassword = $data['old_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->sendError('Tutti i campi sono obbligatori', 400);
        }

        if ($newPassword !== $confirmPassword) {
            $this->sendError('Le nuove password non coincidono', 400);
        }

        $result = $this->authService->changePassword($user['id'], $oldPassword, $newPassword);

        if ($result['success']) {
            $this->sendResponse($result);
        } else {
            $this->sendError($result['message'], 400);
        }
    }

    private function getAuthStatus(): void
    {
        $this->sendResponse([
            'auth_enabled' => $this->authService->isAuthEnabled(),
            'authenticated' => $this->authService->isAuthenticated(),
            'user' => $this->authService->getCurrentUser()
        ]);
    }
}

$controller = new AuthController($authService);
$controller->handleRequest();

