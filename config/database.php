<?php
/**
 * Configurazione Database
 * Sistema modulare Bologna Marathon
 * Usa variabili d'ambiente da .env
 */

// Carica DotEnv se non già caricato
if (!function_exists('env')) {
    require_once __DIR__ . '/../core/Utils/DotEnv.php';
    
    try {
        \BolognaMarathon\Utils\DotEnv::createImmutable(__DIR__ . '/..');
    } catch (Exception $e) {
        // Se .env non esiste, usa valori di default hardcoded
        error_log('Warning: .env file not found, using default values');
    }
}

// Helper function per env (se non già definita da DotEnv)
if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return $value !== false ? $value : $default;
    }
}

// Configurazioni globali
define('MODULES_PATH', __DIR__ . '/../modules/');
define('ASSETS_PATH', __DIR__ . '/../assets/');
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', env('APP_DEBUG', false));

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $conn;

    public function __construct() {
        // Carica credenziali da variabili d'ambiente con fallback
        $this->host = env('DB_HOST', 'localhost');
        $this->port = env('DB_PORT', '3306');
        $this->db_name = env('DB_DATABASE', 'bologna_marathon');
        $this->username = env('DB_USERNAME', 'root');
        $this->password = env('DB_PASSWORD', '');
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            $message = "Errore connessione database";
            
            // In development mostra dettagli errore
            if (APP_DEBUG) {
                $message .= ": " . $exception->getMessage();
            }
            
            error_log($message);
            throw new Exception($message);
        }
        
        return $this->conn;
    }
    
    /**
     * Ottiene informazioni connessione (per debug)
     */
    public function getConnectionInfo() {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->db_name,
            'username' => $this->username
        ];
    }
}
