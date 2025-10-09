<?php

namespace BolognaMarathon\Utils;

use Exception;
use Throwable;

/**
 * Error Handler Centralizzato
 * Gestisce eccezioni e errori PHP con logging e pagine user-friendly
 */
class ErrorHandler
{
    private static $logger;
    private static $debugMode;
    private static $initialized = false;

    /**
     * Inizializza error handler
     */
    public static function register(?Logger $logger = null): void
    {
        if (self::$initialized) {
            return;
        }

        self::$logger = $logger ?? new Logger();
        self::$debugMode = filter_var(
            $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: 'false',
            FILTER_VALIDATE_BOOLEAN
        );

        // Registra handler
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);

        // Configurazione error reporting
        if (self::$debugMode) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('display_errors', '0');
        }

        self::$initialized = true;
    }

    /**
     * Gestisce eccezioni non catturate
     */
    public static function handleException(Throwable $exception): void
    {
        $error = [
            'type' => 'Exception',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'code' => $exception->getCode()
        ];

        // Log errore
        self::logError($error);

        // Mostra pagina errore
        self::displayErrorPage($error);
    }

    /**
     * Gestisce errori PHP
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        // Ignora errori soppressi con @
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $error = [
            'type' => self::getErrorType($errno),
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'code' => $errno
        ];

        // Log errore
        self::logError($error);

        // In debug mode, mostra errore
        if (self::$debugMode) {
            return false; // Lascia gestire a PHP
        }

        return true; // Blocca output PHP
    }

    /**
     * Gestisce shutdown fatale
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorData = [
                'type' => self::getErrorType($error['type']),
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'code' => $error['type']
            ];

            self::logError($errorData);
            
            if (!self::$debugMode) {
                self::displayErrorPage($errorData);
            }
        }
    }

    /**
     * Log errore
     */
    private static function logError(array $error): void
    {
        if (self::$logger) {
            $context = [
                'file' => $error['file'] ?? '',
                'line' => $error['line'] ?? '',
                'code' => $error['code'] ?? 0,
                'trace' => $error['trace'] ?? '',
                'url' => $_SERVER['REQUEST_URI'] ?? '',
                'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ];

            self::$logger->error($error['message'], $context);
        } else {
            // Fallback a error_log
            error_log(sprintf(
                "[%s] %s in %s:%s",
                $error['type'] ?? 'Error',
                $error['message'],
                $error['file'] ?? 'unknown',
                $error['line'] ?? 0
            ));
        }
    }

    /**
     * Mostra pagina errore user-friendly
     */
    private static function displayErrorPage(array $error): void
    {
        // Pulisci output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');

        if (self::$debugMode) {
            self::displayDebugError($error);
        } else {
            self::displayProductionError();
        }

        exit(1);
    }

    /**
     * Pagina errore per sviluppo (dettagliata)
     */
    private static function displayDebugError(array $error): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="it">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error - Debug Mode</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #1a1a1a; color: #e0e0e0; padding: 2rem; }
                .container { max-width: 1200px; margin: 0 auto; }
                .error-box { background: #2d2d2d; border-left: 4px solid #dc2626; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; }
                h1 { color: #dc2626; margin-bottom: 1rem; font-size: 2rem; }
                .error-type { color: #f59e0b; font-size: 0.9rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 1px; }
                .error-message { font-size: 1.2rem; margin-bottom: 1rem; line-height: 1.6; }
                .error-location { color: #9ca3af; font-size: 0.9rem; margin-bottom: 1rem; }
                .error-location strong { color: #60a5fa; }
                .trace-box { background: #1a1a1a; padding: 1.5rem; border-radius: 8px; margin-top: 1rem; overflow-x: auto; }
                .trace-box h3 { color: #60a5fa; margin-bottom: 1rem; font-size: 1.1rem; }
                .trace-box pre { color: #d1d5db; font-size: 0.85rem; line-height: 1.6; white-space: pre-wrap; word-wrap: break-word; }
                .context { background: #2d2d2d; padding: 1.5rem; border-radius: 8px; margin-top: 1rem; }
                .context h3 { color: #10b981; margin-bottom: 1rem; }
                .context table { width: 100%; border-collapse: collapse; }
                .context td { padding: 0.5rem; border-bottom: 1px solid #404040; font-size: 0.85rem; }
                .context td:first-child { color: #9ca3af; width: 150px; }
                .footer { text-align: center; margin-top: 2rem; color: #6b7280; font-size: 0.85rem; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="error-box">
                    <div class="error-type"><?= htmlspecialchars($error['type'] ?? 'Error') ?></div>
                    <h1><?= htmlspecialchars($error['class'] ?? 'Application Error') ?></h1>
                    <div class="error-message"><?= htmlspecialchars($error['message']) ?></div>
                    <div class="error-location">
                        <strong>File:</strong> <?= htmlspecialchars($error['file'] ?? 'unknown') ?><br>
                        <strong>Line:</strong> <?= htmlspecialchars($error['line'] ?? 0) ?>
                        <?php if (!empty($error['code'])): ?>
                            <br><strong>Code:</strong> <?= htmlspecialchars($error['code']) ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($error['trace'])): ?>
                    <div class="trace-box">
                        <h3>Stack Trace</h3>
                        <pre><?= htmlspecialchars($error['trace']) ?></pre>
                    </div>
                <?php endif; ?>

                <div class="context">
                    <h3>Request Context</h3>
                    <table>
                        <tr>
                            <td>URL</td>
                            <td><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td>Method</td>
                            <td><?= htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td>IP Address</td>
                            <td><?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td>User Agent</td>
                            <td><?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td>Timestamp</td>
                            <td><?= date('Y-m-d H:i:s') ?></td>
                        </tr>
                    </table>
                </div>

                <div class="footer">
                    <p>Bologna Marathon - Debug Mode Active</p>
                    <p>Disabilita debug mode in produzione: APP_DEBUG=false</p>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Pagina errore per produzione (generica)
     */
    private static function displayProductionError(): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="it">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Errore - Bologna Marathon</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: system-ui, -apple-system, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
                .error-container { background: white; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 3rem; text-align: center; max-width: 500px; }
                .icon { font-size: 4rem; margin-bottom: 1rem; }
                h1 { color: #1f2937; font-size: 2rem; margin-bottom: 1rem; }
                p { color: #6b7280; line-height: 1.6; margin-bottom: 2rem; }
                .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 0.75rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600; transition: transform 0.2s; }
                .button:hover { transform: translateY(-2px); }
                .support { margin-top: 2rem; font-size: 0.875rem; color: #9ca3af; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="icon">⚠️</div>
                <h1>Oops! Qualcosa è andato storto</h1>
                <p>
                    Ci scusiamo per l'inconveniente. Si è verificato un errore imprevisto. 
                    Il nostro team è stato notificato e sta lavorando per risolvere il problema.
                </p>
                <a href="/" class="button">Torna alla Home</a>
                <div class="support">
                    Se il problema persiste, contatta il supporto:<br>
                    <strong>support@bolognamarathon.run</strong>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Converte codice errore in tipo leggibile
     */
    private static function getErrorType(int $errno): string
    {
        $types = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];

        return $types[$errno] ?? 'Unknown Error';
    }
}

