<?php
/**
 * Themes Editor - Gestione Temi
 */

session_start();

require_once '../../config/database.php';

$pageTitle = 'Gestione Temi';
$currentPage = 'themes';

// Inizializza database
$database = new Database();
$db = $database->getConnection();

// Ottieni temi
$themesStmt = $db->query("SELECT * FROM theme_identities ORDER BY name");
$themes = $themesStmt->fetchAll();

// Start output buffering to capture content
ob_start();
?>

<div x-data="themesEditor()" class="space-y-6">
    
    <!-- Header Actions -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                <span x-text="themes.length"></span> temi • 
                <span x-text="themes.filter(t => t.is_active).length"></span> attivi
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <button @click="generateCSS()" 
                    :disabled="generating"
                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span x-text="generating ? 'Generando...' : 'Genera CSS'"></span>
            </button>
            <button @click="showCreateModal = true" 
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Nuovo Tema</span>
            </button>
        </div>
    </div>

    <!-- Themes Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <template x-for="theme in themes" :key="theme.id">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Theme Header -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="theme.name"></h3>
                            <div class="flex items-center space-x-2 mt-1">
                                <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded" x-text="theme.alias"></code>
                                <span x-show="theme.is_default" class="text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 px-2 py-1 rounded">Default</span>
                                <span :class="theme.is_active ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'" 
                                      class="text-xs px-2 py-1 rounded" 
                                      x-text="theme.is_active ? 'Attivo' : 'Inattivo'"></span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="editTheme(theme)" 
                                    class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button @click="deleteTheme(theme)" 
                                    :disabled="theme.is_default"
                                    class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Color Palette -->
                <div class="p-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Palette Colori</p>
                    <div class="grid grid-cols-4 gap-2">
                        <div class="text-center">
                            <div class="w-full h-12 rounded border border-gray-200 dark:border-gray-700" 
                                 :style="`background-color: ${theme.primary_color}`"></div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Primary</p>
                        </div>
                        <div class="text-center">
                            <div class="w-full h-12 rounded border border-gray-200 dark:border-gray-700" 
                                 :style="`background-color: ${theme.secondary_color}`"></div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Secondary</p>
                        </div>
                        <div class="text-center">
                            <div class="w-full h-12 rounded border border-gray-200 dark:border-gray-700" 
                                 :style="`background-color: ${theme.accent_color}`"></div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Accent</p>
                        </div>
                        <div class="text-center">
                            <div class="w-full h-12 rounded border border-gray-200 dark:border-gray-700" 
                                 :style="`background-color: ${theme.success_color}`"></div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Success</p>
                        </div>
                    </div>
                </div>

                <!-- Theme Actions -->
                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <button @click="exportTheme(theme)" 
                            class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Esporta</span>
                    </button>
                    
                    <button @click="setAsDefault(theme)" 
                            :disabled="theme.is_default"
                            class="text-sm text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Imposta Default</span>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="themes.length === 0" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
        </svg>
        <p class="text-gray-600 dark:text-gray-400 mb-4">Nessun tema disponibile</p>
        <button @click="showCreateModal = true" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
            Crea il primo tema →
        </button>
    </div>

    <!-- Create/Edit Modal -->
    <div x-show="showCreateModal || showEditModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div @click="showCreateModal = false; showEditModal = false" 
                 class="fixed inset-0 bg-black opacity-50"></div>
            
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4" x-text="showEditModal ? 'Modifica Tema' : 'Nuovo Tema'"></h2>
                
                <form @submit.prevent="showEditModal ? updateTheme() : createTheme()">
                    <div class="space-y-4">
                        <!-- Basic Info -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome *</label>
                                <input type="text" 
                                       x-model="formData.name" 
                                       required
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alias *</label>
                                <input type="text" 
                                       x-model="formData.alias" 
                                       required
                                       pattern="[a-z0-9-]+"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Classe CSS</label>
                            <input type="text" 
                                   x-model="formData.class_name" 
                                   placeholder="race-marathon"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Color Pickers -->
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Colori Tema</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Primary</label>
                                    <input type="color" 
                                           x-model="formData.primary_color" 
                                           class="w-full h-10 rounded border border-gray-300 dark:border-gray-600">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Secondary</label>
                                    <input type="color" 
                                           x-model="formData.secondary_color" 
                                           class="w-full h-10 rounded border border-gray-300 dark:border-gray-600">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Accent</label>
                                    <input type="color" 
                                           x-model="formData.accent_color" 
                                           class="w-full h-10 rounded border border-gray-300 dark:border-gray-600">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Info</label>
                                    <input type="color" 
                                           x-model="formData.info_color" 
                                           class="w-full h-10 rounded border border-gray-300 dark:border-gray-600">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Success</label>
                                    <input type="color" 
                                           x-model="formData.success_color" 
                                           class="w-full h-10 rounded border border-gray-300 dark:border-gray-600">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Warning</label>
                                    <input type="color" 
                                           x-model="formData.warning_color" 
                                           class="w-full h-10 rounded border border-gray-300 dark:border-gray-600">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Error</label>
                                    <input type="color" 
                                           x-model="formData.error_color" 
                                           class="w-full h-10 rounded border border-gray-300 dark:border-gray-600">
                                </div>
                            </div>
                        </div>

                        <!-- Checkboxes -->
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" 
                                       x-model="formData.is_active" 
                                       class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Attivo</span>
                            </label>
                            
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" 
                                       x-model="formData.is_default" 
                                       class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Imposta come default</span>
                            </label>
                            
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" 
                                       x-model="formData.regenerate_css" 
                                       class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Rigenera CSS</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" 
                                @click="showCreateModal = false; showEditModal = false"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            Annulla
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                            <span x-text="showEditModal ? 'Salva Modifiche' : 'Crea Tema'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
function themesEditor() {
    return {
        themes: <?= json_encode($themes) ?>,
        generating: false,
        showCreateModal: false,
        showEditModal: false,
        formData: {
            name: '',
            alias: '',
            class_name: '',
            primary_color: '#23a8eb',
            secondary_color: '#1583b9',
            accent_color: '#22d3ee',
            info_color: '#5DADE2',
            success_color: '#52bd7b',
            warning_color: '#F39C12',
            error_color: '#E74C3C',
            is_active: true,
            is_default: false,
            regenerate_css: true
        },
        editingId: null,
        
        async generateCSS() {
            this.generating = true;
            try {
                await api.call('../api/themes.php?action=generate-css', {
                    method: 'POST'
                });
                notify('CSS temi generato con successo', 'success');
            } catch (error) {
                console.error('Error generating CSS:', error);
            } finally {
                this.generating = false;
            }
        },
        
        async loadThemes() {
            try {
                const response = await api.call('../api/themes.php');
                this.themes = response.data;
            } catch (error) {
                console.error('Error loading themes:', error);
            }
        },
        
        async createTheme() {
            try {
                const response = await api.call('../api/themes.php', {
                    method: 'POST',
                    body: JSON.stringify(this.formData)
                });
                
                this.themes.push(response.data);
                this.showCreateModal = false;
                this.resetForm();
                notify('Tema creato con successo', 'success');
            } catch (error) {
                console.error('Error creating theme:', error);
            }
        },
        
        editTheme(theme) {
            this.editingId = theme.id;
            this.formData = {
                name: theme.name,
                alias: theme.alias,
                class_name: theme.class_name,
                primary_color: theme.primary_color,
                secondary_color: theme.secondary_color,
                accent_color: theme.accent_color,
                info_color: theme.info_color,
                success_color: theme.success_color,
                warning_color: theme.warning_color,
                error_color: theme.error_color,
                is_active: Boolean(theme.is_active),
                is_default: Boolean(theme.is_default),
                regenerate_css: true
            };
            this.showEditModal = true;
        },
        
        async updateTheme() {
            try {
                const response = await api.call(`../api/themes.php?id=${this.editingId}`, {
                    method: 'PUT',
                    body: JSON.stringify(this.formData)
                });
                
                const index = this.themes.findIndex(t => t.id === this.editingId);
                if (index !== -1) {
                    this.themes[index] = response.data;
                }
                
                this.showEditModal = false;
                this.resetForm();
                notify('Tema aggiornato con successo', 'success');
            } catch (error) {
                console.error('Error updating theme:', error);
            }
        },
        
        async deleteTheme(theme) {
            if (theme.is_default) {
                notify('Non puoi eliminare il tema di default', 'error');
                return;
            }
            
            if (!confirm(`Eliminare il tema "${theme.name}"?`)) return;
            
            try {
                await api.call(`../api/themes.php?id=${theme.id}`, {
                    method: 'DELETE'
                });
                
                this.themes = this.themes.filter(t => t.id !== theme.id);
                notify('Tema eliminato con successo', 'success');
            } catch (error) {
                console.error('Error deleting theme:', error);
            }
        },
        
        async setAsDefault(theme) {
            try {
                await api.call(`../api/themes.php?action=default&id=${theme.id}`, {
                    method: 'POST'
                });
                
                // Update all themes
                this.themes.forEach(t => {
                    t.is_default = (t.id === theme.id);
                });
                
                notify('Tema impostato come default', 'success');
            } catch (error) {
                console.error('Error setting default theme:', error);
            }
        },
        
        async exportTheme(theme) {
            try {
                window.open(`../api/themes.php?action=export&id=${theme.id}`, '_blank');
            } catch (error) {
                console.error('Error exporting theme:', error);
            }
        },
        
        resetForm() {
            this.formData = {
                name: '',
                alias: '',
                class_name: '',
                primary_color: '#23a8eb',
                secondary_color: '#1583b9',
                accent_color: '#22d3ee',
                info_color: '#5DADE2',
                success_color: '#52bd7b',
                warning_color: '#F39C12',
                error_color: '#E74C3C',
                is_active: true,
                is_default: false,
                regenerate_css: true
            };
            this.editingId = null;
        }
    }
}
</script>

<?php
// Capture the buffered content
$pageContent = ob_get_clean();

// Include layout (which will use $pageContent)
require_once '../components/layout.php';

