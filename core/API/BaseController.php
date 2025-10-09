<?php
/**
 * Base API Controller
 * Funzionalità comuni per tutti i controller API
 */

namespace BolognaMarathon\API;

class BaseController
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Invia response JSON
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Success response
     */
    protected function success($data = [], $message = null, $statusCode = 200)
    {
        $response = [
            'success' => true,
            'data' => $data
        ];

        if ($message) {
            $response['message'] = $message;
        }

        $this->json($response, $statusCode);
    }

    /**
     * Error response
     */
    protected function error($message, $statusCode = 400, $errors = [])
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        $this->json($response, $statusCode);
    }

    /**
     * Not found response
     */
    protected function notFound($message = 'Risorsa non trovata')
    {
        $this->error($message, 404);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorized($message = 'Non autorizzato')
    {
        $this->error($message, 401);
    }

    /**
     * Forbidden response
     */
    protected function forbidden($message = 'Accesso negato')
    {
        $this->error($message, 403);
    }

    /**
     * Validation error response
     */
    protected function validationError($errors)
    {
        $this->error('Errore di validazione', 422, $errors);
    }

    /**
     * Ottieni dati POST/PUT/PATCH
     */
    protected function getRequestData()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'POST' && !empty($_POST)) {
            return $_POST;
        }

        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return [];
        }

        // Prova a decodificare JSON
        $json = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        // Prova a parsare form-encoded
        parse_str($input, $data);
        return $data;
    }

    /**
     * Ottieni parametro GET
     */
    protected function getQuery($key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Ottieni tutti i parametri GET
     */
    protected function getAllQuery()
    {
        return $_GET;
    }

    /**
     * Valida campi richiesti
     */
    protected function validateRequired($data, $requiredFields)
    {
        $errors = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $errors[$field] = "Campo {$field} obbligatorio";
            }
        }

        if (!empty($errors)) {
            $this->validationError($errors);
        }

        return true;
    }

    /**
     * Sanitizza input
     */
    protected function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }

        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Verifica metodo HTTP
     */
    protected function requireMethod($method)
    {
        $currentMethod = $_SERVER['REQUEST_METHOD'];
        
        if (is_array($method)) {
            if (!in_array($currentMethod, $method)) {
                $this->error("Metodo non permesso. Richiesto: " . implode(', ', $method), 405);
            }
        } else {
            if ($currentMethod !== $method) {
                $this->error("Metodo non permesso. Richiesto: {$method}", 405);
            }
        }
    }

    /**
     * Rate limiting semplice (TODO: implementare con cache)
     */
    protected function checkRateLimit($identifier, $maxRequests = 60, $windowSeconds = 60)
    {
        // TODO: Implementare rate limiting con file cache o Redis
        return true;
    }

    /**
     * Log errore
     */
    protected function logError($message, $context = [])
    {
        $log = date('[Y-m-d H:i:s] ') . $message;
        
        if (!empty($context)) {
            $log .= ' | ' . json_encode($context);
        }

        error_log($log . PHP_EOL, 3, __DIR__ . '/../../logs/api-errors.log');
    }

    /**
     * Gestione eccezioni centralizzata
     */
    protected function handleException(\Exception $e)
    {
        $this->logError($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        // In produzione non mostrare dettagli
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $this->error($e->getMessage(), 500);
        } else {
            $this->error('Errore interno del server', 500);
        }
    }

    /**
     * Verifica autenticazione (TODO: implementare quando auth attivo)
     */
    protected function requireAuth()
    {
        // TODO: Verificare sessione/token quando AUTH_ENABLED = true
        
        if (defined('AUTH_ENABLED') && AUTH_ENABLED) {
            // Logica autenticazione
            if (!isset($_SESSION['user_id'])) {
                $this->unauthorized();
            }
        }
        
        return true;
    }

    /**
     * Verifica permessi (TODO: implementare quando auth attivo)
     */
    protected function requirePermission($permission)
    {
        // TODO: Verificare permessi utente
        
        if (defined('AUTH_ENABLED') && AUTH_ENABLED) {
            // Logica permessi
        }
        
        return true;
    }

    /**
     * CORS headers
     */
    protected function setCorsHeaders()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');

        // Handle preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}

