<?php
/**
 * Configurazione Database
 * Sistema modulare Bologna Marathon
 */

// Configurazioni globali
define('MODULES_PATH', __DIR__ . '/../modules/');
define('ASSETS_PATH', __DIR__ . '/../assets/');

class Database {
    private string $host;
    private string $dbName;
    private string $username;
    private string $password;
    private ?PDO $conn = null;

    public function __construct(array $config = []) {
        $this->host = $config['host'] ?? getenv('DB_HOST') ?? 'localhost';
        $this->dbName = $config['database'] ?? getenv('DB_NAME') ?? 'bologna_marathon';
        $this->username = $config['username'] ?? getenv('DB_USER') ?? 'root';
        $this->password = $config['password'] ?? getenv('DB_PASSWORD') ?? '';
    }

    public function getConnection(): PDO {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        try {
            $this->conn = new PDO(
                sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $this->host, $this->dbName),
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException('Errore connessione database: ' . $exception->getMessage(), 0, $exception);
        }

        return $this->conn;
    }
}
