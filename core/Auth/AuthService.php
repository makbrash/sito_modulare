<?php

namespace BolognaMarathon\Auth;

use PDO;
use Exception;

/**
 * Authentication Service
 * Sistema autenticazione admin (DISABILITATO di default)
 * 
 * NOTA: Per abilitare, modificare .env: AUTH_ENABLED=true
 */
class AuthService
{
    private $db;
    private $enabled;
    private $sessionName = 'bm_admin_session';
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 900; // 15 minuti in secondi

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->enabled = $this->isAuthEnabled();
        
        // Avvia sessione se non già attiva
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Verifica se sistema auth è abilitato
     */
    public function isAuthEnabled(): bool
    {
        return filter_var(
            $_ENV['AUTH_ENABLED'] ?? $_SERVER['AUTH_ENABLED'] ?? getenv('AUTH_ENABLED') ?: 'false',
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * Login utente
     */
    public function login(string $username, string $password): array
    {
        if (!$this->enabled) {
            return ['success' => true, 'message' => 'Auth disabilitato - accesso libero'];
        }

        // Verifica se utente è bloccato
        $user = $this->getUserByUsername($username);
        if (!$user) {
            $this->logActivity(null, 'login_failed', null, null, "Username non trovato: {$username}");
            return ['success' => false, 'message' => 'Credenziali non valide'];
        }

        // Verifica blocco account
        if ($this->isUserLocked($user)) {
            $this->logActivity($user['id'], 'login_blocked', null, null, 'Tentativo login su account bloccato');
            return ['success' => false, 'message' => 'Account temporaneamente bloccato. Riprova più tardi.'];
        }

        // Verifica password
        if (!password_verify($password, $user['password_hash'])) {
            $this->incrementFailedAttempts($user['id']);
            $this->logActivity($user['id'], 'login_failed', null, null, 'Password errata');
            return ['success' => false, 'message' => 'Credenziali non valide'];
        }

        // Verifica se utente è attivo
        if (!$user['is_active']) {
            $this->logActivity($user['id'], 'login_blocked', null, null, 'Tentativo login su account disattivato');
            return ['success' => false, 'message' => 'Account disattivato'];
        }

        // Login riuscito
        $this->resetFailedAttempts($user['id']);
        $this->updateLastLogin($user['id']);
        $this->createSession($user);
        $this->logActivity($user['id'], 'login_success', null, null, 'Login riuscito');

        return [
            'success' => true,
            'message' => 'Login effettuato con successo',
            'user' => $this->sanitizeUserData($user),
            'must_change_password' => (bool)$user['must_change_password']
        ];
    }

    /**
     * Logout utente
     */
    public function logout(): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $userId = $this->getCurrentUserId();
        if ($userId) {
            $this->logActivity($userId, 'logout', null, null, 'Logout utente');
            $this->destroySession($_SESSION[$this->sessionName]['session_id'] ?? null);
        }

        unset($_SESSION[$this->sessionName]);
        return true;
    }

    /**
     * Ottiene utente corrente
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->enabled) {
            return $this->getGuestUser();
        }

        if (!isset($_SESSION[$this->sessionName])) {
            return null;
        }

        $userId = $_SESSION[$this->sessionName]['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        $user = $this->getUserById($userId);
        if (!$user || !$user['is_active']) {
            $this->logout();
            return null;
        }

        return $this->sanitizeUserData($user);
    }

    /**
     * Verifica se utente è autenticato
     */
    public function isAuthenticated(): bool
    {
        if (!$this->enabled) {
            return true; // Accesso libero se auth disabilitato
        }

        return $this->getCurrentUser() !== null;
    }

    /**
     * Verifica se utente ha un ruolo specifico
     */
    public function hasRole(string $role): bool
    {
        if (!$this->enabled) {
            return true; // Accesso completo se auth disabilitato
        }

        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        return $user['role'] === $role;
    }

    /**
     * Verifica se utente ha almeno un ruolo
     */
    public function hasAnyRole(array $roles): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        return in_array($user['role'], $roles);
    }

    /**
     * Cambia password utente
     */
    public function changePassword(int $userId, string $oldPassword, string $newPassword): array
    {
        $user = $this->getUserById($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'Utente non trovato'];
        }

        // Verifica vecchia password
        if (!password_verify($oldPassword, $user['password_hash'])) {
            $this->logActivity($userId, 'password_change_failed', null, null, 'Vecchia password errata');
            return ['success' => false, 'message' => 'Password attuale non corretta'];
        }

        // Valida nuova password
        $validation = $this->validatePassword($newPassword);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        // Aggiorna password
        $stmt = $this->db->prepare("UPDATE admin_users SET password_hash = ?, must_change_password = 0 WHERE id = ?");
        $stmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $userId]);

        $this->logActivity($userId, 'password_changed', null, null, 'Password modificata con successo');

        return ['success' => true, 'message' => 'Password modificata con successo'];
    }

    /**
     * Crea utente
     */
    public function createUser(array $data): array
    {
        // Valida dati
        $validation = $this->validateUserData($data);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        // Verifica unicità username/email
        if ($this->getUserByUsername($data['username'])) {
            return ['success' => false, 'message' => 'Username già esistente'];
        }
        if ($this->getUserByEmail($data['email'])) {
            return ['success' => false, 'message' => 'Email già esistente'];
        }

        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        // Inserisci utente
        $sql = "INSERT INTO admin_users (username, password_hash, email, display_name, role, is_active, must_change_password) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['username'],
            $passwordHash,
            $data['email'],
            $data['display_name'] ?? $data['username'],
            $data['role'] ?? 'editor',
            $data['is_active'] ?? 1,
            $data['must_change_password'] ?? 1
        ]);

        $userId = $this->db->lastInsertId();
        $this->logActivity($this->getCurrentUserId(), 'user_created', 'user', $userId, "Creato utente: {$data['username']}");

        return ['success' => true, 'message' => 'Utente creato con successo', 'user_id' => $userId];
    }

    /**
     * Valida password
     */
    private function validatePassword(string $password): array
    {
        if (strlen($password) < 8) {
            return ['valid' => false, 'message' => 'Password deve essere almeno 8 caratteri'];
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return ['valid' => false, 'message' => 'Password deve contenere almeno una maiuscola'];
        }

        if (!preg_match('/[a-z]/', $password)) {
            return ['valid' => false, 'message' => 'Password deve contenere almeno una minuscola'];
        }

        if (!preg_match('/[0-9]/', $password)) {
            return ['valid' => false, 'message' => 'Password deve contenere almeno un numero'];
        }

        return ['valid' => true];
    }

    /**
     * Valida dati utente
     */
    private function validateUserData(array $data): array
    {
        if (empty($data['username']) || strlen($data['username']) < 3) {
            return ['valid' => false, 'message' => 'Username deve essere almeno 3 caratteri'];
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Email non valida'];
        }

        if (!empty($data['password'])) {
            $passwordValidation = $this->validatePassword($data['password']);
            if (!$passwordValidation['valid']) {
                return $passwordValidation;
            }
        }

        return ['valid' => true];
    }

    /**
     * Ottiene utente per username
     */
    private function getUserByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Ottiene utente per email
     */
    private function getUserByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Ottiene utente per ID
     */
    private function getUserById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Verifica se utente è bloccato
     */
    private function isUserLocked(array $user): bool
    {
        if (empty($user['locked_until'])) {
            return false;
        }

        $lockedUntil = strtotime($user['locked_until']);
        if ($lockedUntil > time()) {
            return true;
        }

        // Sblocca automaticamente se periodo scaduto
        $this->resetFailedAttempts($user['id']);
        return false;
    }

    /**
     * Incrementa tentativi falliti
     */
    private function incrementFailedAttempts(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE admin_users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?");
        $stmt->execute([$userId]);

        // Verifica se bloccare account
        $user = $this->getUserById($userId);
        if ($user && $user['failed_login_attempts'] >= $this->maxLoginAttempts) {
            $lockUntil = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
            $stmt = $this->db->prepare("UPDATE admin_users SET locked_until = ? WHERE id = ?");
            $stmt->execute([$lockUntil, $userId]);
        }
    }

    /**
     * Reset tentativi falliti
     */
    private function resetFailedAttempts(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE admin_users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->execute([$userId]);
    }

    /**
     * Aggiorna ultimo login
     */
    private function updateLastLogin(int $userId): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $this->db->prepare("UPDATE admin_users SET last_login = NOW(), last_login_ip = ? WHERE id = ?");
        $stmt->execute([$ip, $userId]);
    }

    /**
     * Crea sessione
     */
    private function createSession(array $user): void
    {
        $sessionId = bin2hex(random_bytes(32));
        
        $_SESSION[$this->sessionName] = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'session_id' => $sessionId,
            'created_at' => time()
        ];

        // Salva sessione in database
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $this->db->prepare("INSERT INTO admin_sessions (id, user_id, ip_address, user_agent, payload, last_activity) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $sessionId,
            $user['id'],
            $ip,
            substr($userAgent, 0, 255),
            json_encode($_SESSION[$this->sessionName])
        ]);
    }

    /**
     * Distrugge sessione
     */
    private function destroySession(?string $sessionId): void
    {
        if ($sessionId) {
            $stmt = $this->db->prepare("DELETE FROM admin_sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
        }
    }

    /**
     * Ottiene ID utente corrente
     */
    private function getCurrentUserId(): ?int
    {
        return $_SESSION[$this->sessionName]['user_id'] ?? null;
    }

    /**
     * Sanitizza dati utente
     */
    private function sanitizeUserData(array $user): array
    {
        unset($user['password_hash']);
        return $user;
    }

    /**
     * Ottiene utente guest (quando auth disabilitato)
     */
    private function getGuestUser(): array
    {
        return [
            'id' => 0,
            'username' => 'guest',
            'email' => 'guest@localhost',
            'display_name' => 'Guest User',
            'role' => 'super_admin',
            'is_active' => true
        ];
    }

    /**
     * Log attività
     */
    public function logActivity(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $description = null): void
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $this->db->prepare("INSERT INTO admin_activity_log (user_id, action, entity_type, entity_id, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                $description,
                $ip,
                substr($userAgent, 0, 255)
            ]);
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}

