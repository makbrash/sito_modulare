<?php
/**
 * Simple DotEnv Loader
 * Carica variabili da file .env senza dipendenze esterne
 */

namespace BolognaMarathon\Utils;

class DotEnv
{
    protected $path;
    protected $loaded = false;

    public function __construct($path)
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Carica il file .env
     */
    public function load()
    {
        if ($this->loaded) {
            return;
        }

        $envFile = $this->path . DIRECTORY_SEPARATOR . '.env';
        
        if (!file_exists($envFile)) {
            // Se .env non esiste, prova con env.example come fallback
            $envFile = $this->path . DIRECTORY_SEPARATOR . 'env.example';
            if (!file_exists($envFile)) {
                throw new \RuntimeException('File .env non trovato');
            }
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignora commenti e linee vuote
            if (strpos(trim($line), '#') === 0 || empty(trim($line))) {
                continue;
            }

            // Parse linea KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                
                $name = trim($name);
                $value = trim($value);

                // Rimuovi quotes
                $value = $this->removeQuotes($value);

                // Sostituisci variabili ${VAR}
                $value = $this->interpolate($value);

                // Set variabile d'ambiente
                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                    putenv("{$name}={$value}");
                }
            }
        }

        $this->loaded = true;
    }

    /**
     * Rimuove quotes da un valore
     */
    protected function removeQuotes($value)
    {
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            return substr($value, 1, -1);
        }
        
        return $value;
    }

    /**
     * Interpola variabili ${VAR} nel valore
     */
    protected function interpolate($value)
    {
        if (strpos($value, '${') !== false) {
            return preg_replace_callback('/\$\{([a-zA-Z0-9_]+)\}/', function($matches) {
                $var = $matches[1];
                return $_ENV[$var] ?? $_SERVER[$var] ?? getenv($var) ?: '';
            }, $value);
        }
        
        return $value;
    }

    /**
     * Crea istanza e carica immediatamente
     */
    public static function createImmutable($path)
    {
        $instance = new static($path);
        $instance->load();
        return $instance;
    }
}

/**
 * Helper function per accedere a variabili d'ambiente
 */
if (!function_exists('env')) {
    function env($key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        
        if ($value === false) {
            return $default;
        }

        // Convert string booleans
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}

