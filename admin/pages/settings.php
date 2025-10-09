<?php
/**
 * Settings - Impostazioni Globali
 */

// Verifica autenticazione
require_once __DIR__ . '/../auth-check.php';

$pageTitle = 'Impostazioni';
$currentPage = 'settings';

// Carica configurazione
$envFile = __DIR__ . '/../../.env';
$envVars = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $envVars[trim($key)] = trim($value);
        }
    }
}

// Start output buffering to capture content
ob_start();
?>

<div x-data="settingsManager()" class="space-y-6">
    
    <!-- Header -->
    <div class="mb-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Configurazioni globali del sistema
        </p>
    </div>

    <!-- Settings Tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                <button @click="activeTab = 'general'" 
                        :class="activeTab === 'general' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Generali
                </button>
                <button @click="activeTab = 'database'" 
                        :class="activeTab === 'database' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Database
                </button>
                <button @click="activeTab = 'cache'" 
                        :class="activeTab === 'cache' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Cache
                </button>
                <button @click="activeTab = 'security'" 
                        :class="activeTab === 'security' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Sicurezza
                </button>
            </nav>
        </div>
    </div>

    <!-- General Tab -->
    <div x-show="activeTab === 'general'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informazioni Applicazione</h2>
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome Applicazione</label>
                        <input type="text" 
                               value="<?= htmlspecialchars($envVars['APP_NAME'] ?? 'Bologna Marathon') ?>" 
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ambiente</label>
                        <input type="text" 
                               value="<?= htmlspecialchars($envVars['APP_ENV'] ?? 'development') ?>" 
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL Base</label>
                    <input type="text" 
                           value="<?= htmlspecialchars($envVars['APP_URL'] ?? 'http://localhost/sito_modulare') ?>" 
                           disabled
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
                </div>

                <div class="flex items-center space-x-2">
                    <input type="checkbox" 
                           id="debug_mode"
                           <?= (!empty($envVars['APP_DEBUG']) && $envVars['APP_DEBUG'] === 'true') ? 'checked' : '' ?>
                           disabled
                           class="w-4 h-4 text-blue-600 rounded">
                    <label for="debug_mode" class="text-sm text-gray-700 dark:text-gray-300">
                        Debug Mode Attivo
                    </label>
                </div>
            </div>

            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Per modificare queste impostazioni, edita il file <code class="bg-blue-100 dark:bg-blue-900 px-1 rounded">.env</code> nella root del progetto.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Database Tab -->
    <div x-show="activeTab === 'database'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Configurazione Database</h2>
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Host</label>
                        <input type="text" 
                               value="<?= htmlspecialchars($envVars['DB_HOST'] ?? 'localhost') ?>" 
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Porta</label>
                        <input type="text" 
                               value="<?= htmlspecialchars($envVars['DB_PORT'] ?? '3306') ?>" 
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Database</label>
                    <input type="text" 
                           value="<?= htmlspecialchars($envVars['DB_DATABASE'] ?? 'bologna_marathon') ?>" 
                           disabled
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
                    <input type="text" 
                           value="<?= htmlspecialchars($envVars['DB_USERNAME'] ?? 'root') ?>" 
                           disabled
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
                </div>

                <button @click="testConnection()" 
                        :disabled="testing"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg transition-colors">
                    <span x-text="testing ? 'Testando...' : 'Testa Connessione'"></span>
                </button>

                <div x-show="connectionResult" 
                     x-cloak
                     :class="connectionSuccess ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-700 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-300'"
                     class="p-4 border rounded-lg">
                    <p class="text-sm" x-text="connectionResult"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cache Tab -->
    <div x-show="activeTab === 'cache'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Gestione Cache</h2>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Cache Attiva</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <?= (!empty($envVars['CACHE_ENABLED']) && $envVars['CACHE_ENABLED'] === 'true') ? 'Abilitata' : 'Disabilitata' ?>
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Driver Cache</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <?= htmlspecialchars($envVars['CACHE_DRIVER'] ?? 'file') ?>
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Lifetime Cache</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <?= htmlspecialchars($envVars['CACHE_LIFETIME'] ?? '3600') ?> secondi
                        </p>
                    </div>
                </div>

                <button @click="clearCache()" 
                        :disabled="clearing"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white rounded-lg transition-colors">
                    <span x-text="clearing ? 'Svuotando...' : 'Svuota Cache'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Security Tab -->
    <div x-show="activeTab === 'security'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Impostazioni Sicurezza</h2>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Autenticazione</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <?= (!empty($envVars['AUTH_ENABLED']) && $envVars['AUTH_ENABLED'] === 'true') ? 'Abilitata' : 'Disabilitata (da attivare in produzione)' ?>
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium <?= (!empty($envVars['AUTH_ENABLED']) && $envVars['AUTH_ENABLED'] === 'true') ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' ?>">
                        <?= (!empty($envVars['AUTH_ENABLED']) && $envVars['AUTH_ENABLED'] === 'true') ? 'Attiva' : 'Non Attiva' ?>
                    </span>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Protezione CSRF</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <?= (!empty($envVars['CSRF_PROTECTION']) && $envVars['CSRF_PROTECTION'] === 'true') ? 'Abilitata' : 'Disabilitata' ?>
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                        Predisposta
                    </span>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Durata Sessione</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <?= htmlspecialchars($envVars['SESSION_LIFETIME'] ?? '120') ?> minuti
                        </p>
                    </div>
                </div>

                <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                Prima del deploy in produzione, assicurati di attivare l'autenticazione modificando <code class="bg-yellow-100 dark:bg-yellow-900 px-1 rounded">AUTH_ENABLED=true</code> nel file .env
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function settingsManager() {
    return {
        activeTab: 'general',
        testing: false,
        clearing: false,
        connectionResult: '',
        connectionSuccess: false,
        
        async testConnection() {
            this.testing = true;
            this.connectionResult = '';
            
            try {
                // Testa chiamando un endpoint API
                await api.call('../api/pages.php');
                this.connectionResult = '✓ Connessione database funzionante';
                this.connectionSuccess = true;
            } catch (error) {
                this.connectionResult = '✗ Errore connessione database: ' + error.message;
                this.connectionSuccess = false;
            } finally {
                this.testing = false;
            }
        },
        
        async clearCache() {
            if (!confirm('Sei sicuro di voler svuotare la cache?')) return;
            
            this.clearing = true;
            
            try {
                // TODO: Implementare endpoint per pulizia cache
                await new Promise(resolve => setTimeout(resolve, 1000)); // Simulazione
                notify('Cache svuotata con successo', 'success');
            } catch (error) {
                notify('Errore durante lo svuotamento della cache', 'error');
            } finally {
                this.clearing = false;
            }
        }
    }
}
</script>

<?php
// Capture the buffered content
$pageContent = ob_get_clean();

// Include layout (which will use $pageContent)
require_once '../components/layout.php';

