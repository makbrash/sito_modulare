<?php

namespace BolognaMarathon\Utils;

use DateTime;

/**
 * Logger Strutturato
 * Sistema di logging con livelli e rotazione file
 */
class Logger
{
    private $logPath;
    private $logLevel;
    private $enabled;
    
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    private $levels = [
        self::EMERGENCY => 0,
        self::ALERT => 1,
        self::CRITICAL => 2,
        self::ERROR => 3,
        self::WARNING => 4,
        self::NOTICE => 5,
        self::INFO => 6,
        self::DEBUG => 7
    ];

    public function __construct(?string $logPath = null, string $logLevel = 'error')
    {
        $this->enabled = filter_var(
            $_ENV['LOG_ENABLED'] ?? $_SERVER['LOG_ENABLED'] ?? getenv('LOG_ENABLED') ?: 'true',
            FILTER_VALIDATE_BOOLEAN
        );

        if ($logPath) {
            $this->logPath = $logPath;
        } else {
            $basePath = __DIR__ . '/../../logs';
            $this->logPath = $_ENV['LOG_FILE'] ?? $_SERVER['LOG_FILE'] ?? getenv('LOG_FILE') ?: $basePath . '/app.log';
        }

        $envLevel = $_ENV['LOG_LEVEL'] ?? $_SERVER['LOG_LEVEL'] ?? getenv('LOG_LEVEL');
        $this->logLevel = $envLevel ?: $logLevel;

        // Crea directory logs se non esiste
        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
    }

    /**
     * System is unusable
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Critical conditions
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Normal but significant events
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Interesting events
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Detailed debug information
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Log generico
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        // Verifica livello minimo
        if (!$this->shouldLog($level)) {
            return;
        }

        $logEntry = $this->formatLogEntry($level, $message, $context);
        
        // Scrivi su file
        @file_put_contents($this->logPath, $logEntry . PHP_EOL, FILE_APPEND | LOCK_EX);

        // Rotazione log se troppo grande (> 10MB)
        $this->rotateLogIfNeeded();
    }

    /**
     * Verifica se livello deve essere loggato
     */
    private function shouldLog(string $level): bool
    {
        $currentLevel = $this->levels[$this->logLevel] ?? 7;
        $messageLevel = $this->levels[$level] ?? 7;
        
        return $messageLevel <= $currentLevel;
    }

    /**
     * Formatta entry di log
     */
    private function formatLogEntry(string $level, string $message, array $context): string
    {
        $timestamp = (new DateTime())->format('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        
        // Interpola placeholders {key} nel messaggio
        $message = $this->interpolate($message, $context);
        
        // Formato: [2025-01-09 14:30:45] ERROR: Message goes here {"context":"data"}
        $logLine = sprintf(
            '[%s] %s: %s',
            $timestamp,
            str_pad($levelUpper, 9),
            $message
        );

        // Aggiungi contesto se presente
        if (!empty($context)) {
            $logLine .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return $logLine;
    }

    /**
     * Interpola placeholders nel messaggio
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        
        foreach ($context as $key => $val) {
            // Converti valore in stringa
            if (is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            } elseif (is_array($val) || is_object($val)) {
                $replace['{' . $key . '}'] = json_encode($val);
            } else {
                $replace['{' . $key . '}'] = '[' . gettype($val) . ']';
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Rotazione log file se necessario
     */
    private function rotateLogIfNeeded(): void
    {
        if (!file_exists($this->logPath)) {
            return;
        }

        $maxSize = 10 * 1024 * 1024; // 10 MB
        $fileSize = filesize($this->logPath);

        if ($fileSize > $maxSize) {
            $timestamp = date('Y-m-d_H-i-s');
            $archivePath = $this->logPath . '.' . $timestamp;
            
            @rename($this->logPath, $archivePath);
            
            // Comprimi vecchio log (opzionale)
            if (function_exists('gzencode')) {
                $content = file_get_contents($archivePath);
                file_put_contents($archivePath . '.gz', gzencode($content, 9));
                @unlink($archivePath);
            }
        }

        // Pulizia vecchi log (mantieni ultimi 10)
        $this->cleanOldLogs();
    }

    /**
     * Pulisce vecchi log
     */
    private function cleanOldLogs(): void
    {
        $logDir = dirname($this->logPath);
        $logBaseName = basename($this->logPath);
        
        $files = glob($logDir . '/' . $logBaseName . '.*');
        
        if (count($files) > 10) {
            // Ordina per data modifica
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Rimuovi i più vecchi
            $toRemove = array_slice($files, 0, count($files) - 10);
            foreach ($toRemove as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Ottiene contenuto log (per visualizzazione admin)
     */
    public function getRecentLogs(int $lines = 100): array
    {
        if (!file_exists($this->logPath)) {
            return [];
        }

        $content = file_get_contents($this->logPath);
        $allLines = explode(PHP_EOL, $content);
        
        // Prendi ultime N righe
        $recentLines = array_slice($allLines, -$lines);
        
        $logs = [];
        foreach ($recentLines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            
            // Parse log entry
            if (preg_match('/^\[([^\]]+)\]\s+(\w+):\s+(.+)$/', $line, $matches)) {
                $logs[] = [
                    'timestamp' => $matches[1],
                    'level' => trim($matches[2]),
                    'message' => $matches[3]
                ];
            }
        }

        return array_reverse($logs); // Più recenti per primi
    }

    /**
     * Pulisce tutti i log
     */
    public function clear(): void
    {
        if (file_exists($this->logPath)) {
            @unlink($this->logPath);
        }
        
        // Rimuovi anche archivi
        $logDir = dirname($this->logPath);
        $logBaseName = basename($this->logPath);
        $files = glob($logDir . '/' . $logBaseName . '.*');
        
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}

