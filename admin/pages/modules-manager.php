<?php
/**
 * Modules Manager - Gestione Moduli
 */

// Verifica autenticazione
require_once __DIR__ . '/../auth-check.php';

$pageTitle = 'Gestione Moduli';
$currentPage = 'modules';

// Inizializza database
$database = new Database();
$db = $database->getConnection();

// Ottieni moduli registrati
$modulesStmt = $db->query("SELECT * FROM modules_registry ORDER BY name");
$modules = $modulesStmt->fetchAll();

// Start output buffering to capture content
ob_start();
?>

<div x-data="modulesManager()" class="space-y-6">
    
    <!-- Header Actions -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                <span x-text="modules.length"></span> moduli registrati • 
                <span x-text="modules.filter(m => m.is_active).length"></span> attivi
            </p>
        </div>
        <button @click="syncModules()" 
                :disabled="syncing"
                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center space-x-2">
            <svg class="w-5 h-5" :class="{ 'animate-spin': syncing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span x-text="syncing ? 'Sincronizzando...' : 'Sincronizza da Filesystem'"></span>
        </button>
    </div>

    <!-- Module Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="module in modules" :key="module.id">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                <!-- Module Header -->
                <div class="p-4 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="module.name"></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="module.component_path"></p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="toggleModuleStatus(module)" 
                                    :class="module.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'"
                                    class="px-2 py-1 text-xs rounded-full font-medium transition-colors">
                                <span x-text="module.is_active ? 'Attivo' : 'Inattivo'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Module Body -->
                <div class="p-4">
                    <!-- Default Config -->
                    <div class="mb-3">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Configurazione Default</p>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded p-2 max-h-32 overflow-y-auto">
                            <pre class="text-xs text-gray-600 dark:text-gray-400" x-text="formatJSON(module.default_config)"></pre>
                        </div>
                    </div>

                    <!-- CSS Class -->
                    <div class="flex items-center justify-between text-sm mb-2">
                        <span class="text-gray-600 dark:text-gray-400">CSS Class:</span>
                        <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs" x-text="module.css_class || '-'"></code>
                    </div>

                    <!-- Created At -->
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Registrato:</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400" x-text="formatDate(module.created_at)"></span>
                    </div>
                </div>

                <!-- Module Footer -->
                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <a :href="'../../modules/' + getModuleSlug(module.component_path)" 
                       target="_blank"
                       class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span>Vai ai file</span>
                    </a>
                    
                    <button @click="viewManifest(module)" 
                            class="text-xs text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Info manifest</span>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="modules.length === 0" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z" />
        </svg>
        <p class="text-gray-600 dark:text-gray-400 mb-4">Nessun modulo registrato</p>
        <button @click="syncModules()" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
            Sincronizza moduli da filesystem →
        </button>
    </div>

    <!-- Manifest Modal -->
    <div x-show="showManifestModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div @click="showManifestModal = false" 
                 class="fixed inset-0 bg-black opacity-50"></div>
            
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Manifest Modulo</h2>
                    <button @click="showManifestModal = false" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 max-h-96 overflow-y-auto">
                    <pre class="text-sm text-gray-700 dark:text-gray-300" x-text="selectedManifest"></pre>
                </div>
                
                <div class="mt-4 flex justify-end">
                    <button @click="showManifestModal = false" 
                            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                        Chiudi
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function modulesManager() {
    return {
        modules: <?= json_encode($modules) ?>,
        syncing: false,
        showManifestModal: false,
        selectedManifest: '',
        
        async syncModules() {
            this.syncing = true;
            try {
                const response = await api.call('../api/modules.php?action=sync', {
                    method: 'POST'
                });
                
                // Ricarica moduli
                await this.loadModules();
                
                const result = response.data;
                notify(`${result.synced} moduli sincronizzati`, 'success');
                
                if (result.errors && result.errors.length > 0) {
                    console.warn('Sync errors:', result.errors);
                    notify(`${result.errors.length} errori durante la sincronizzazione`, 'warning');
                }
            } catch (error) {
                console.error('Error syncing modules:', error);
            } finally {
                this.syncing = false;
            }
        },
        
        async loadModules() {
            try {
                const response = await api.call('../api/modules.php');
                this.modules = response.data;
            } catch (error) {
                console.error('Error loading modules:', error);
            }
        },
        
        async toggleModuleStatus(module) {
            try {
                // Nota: Questa funzionalità richiede un endpoint per aggiornare i moduli registrati
                // Per ora solo feedback visivo
                module.is_active = !module.is_active;
                notify(`Modulo ${module.is_active ? 'attivato' : 'disattivato'}`, 'info');
            } catch (error) {
                console.error('Error toggling module status:', error);
                module.is_active = !module.is_active; // Rollback
            }
        },
        
        async viewManifest(module) {
            try {
                // Prova a caricare il manifest dal filesystem
                const modulePath = this.getModuleSlug(module.component_path);
                const response = await fetch(`../../modules/${modulePath}/module.json`);
                
                if (response.ok) {
                    const manifest = await response.json();
                    this.selectedManifest = JSON.stringify(manifest, null, 2);
                } else {
                    this.selectedManifest = JSON.stringify({
                        name: module.name,
                        component_path: module.component_path,
                        css_class: module.css_class,
                        default_config: JSON.parse(module.default_config || '{}')
                    }, null, 2);
                }
                
                this.showManifestModal = true;
            } catch (error) {
                console.error('Error loading manifest:', error);
                this.selectedManifest = 'Errore caricamento manifest';
                this.showManifestModal = true;
            }
        },
        
        getModuleSlug(componentPath) {
            // Estrai slug da path: "hero/hero.php" -> "hero"
            const parts = componentPath.split('/');
            return parts[0];
        },
        
        formatJSON(jsonString) {
            try {
                const obj = typeof jsonString === 'string' ? JSON.parse(jsonString) : jsonString;
                return JSON.stringify(obj, null, 2);
            } catch (e) {
                return jsonString || '{}';
            }
        },
        
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('it-IT', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric'
            });
        }
    }
}
</script>

<?php
// Capture the buffered content
$pageContent = ob_get_clean();

// Include layout (which will use $pageContent)
require_once '../components/layout.php';

