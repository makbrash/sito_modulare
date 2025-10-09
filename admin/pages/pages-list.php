<?php
/**
 * Pages List - Gestione Pagine
 */

session_start();

require_once '../../config/database.php';

$pageTitle = 'Gestione Pagine';
$currentPage = 'pages';

// Inizializza database
$database = new Database();
$db = $database->getConnection();

// Ottieni statistiche
$statsStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
    FROM pages
");
$stats = $statsStmt->fetch();

// Ottieni tutte le pagine
$pagesStmt = $db->query("SELECT * FROM pages ORDER BY updated_at DESC");
$pages = $pagesStmt->fetchAll();

// Ottieni temi disponibili
$themesStmt = $db->query("SELECT * FROM theme_identities WHERE is_active = 1 ORDER BY name");
$themes = $themesStmt->fetchAll();

$skipContent = true;
require_once '../components/layout.php';
?>

<div x-data="pagesManager()">
    
    <!-- Header Actions -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gestione Pagine</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                <?= $stats['total'] ?> pagine totali • <?= $stats['published'] ?> pubblicate • <?= $stats['draft'] ?> bozze
            </p>
        </div>
        <button @click="showCreateModal = true" 
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Nuova Pagina</span>
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-6 border border-gray-200 dark:border-gray-700">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" 
                       x-model="search" 
                       @input="filterPages()"
                       placeholder="Cerca per titolo o slug..." 
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <select x-model="filterStatus" 
                    @change="filterPages()"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                <option value="">Tutti gli stati</option>
                <option value="published">Pubblicate</option>
                <option value="draft">Bozze</option>
            </select>
            <button @click="loadPages()" 
                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Pages Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pagina</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stato</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tema</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ultima modifica</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="page in filteredPages" :key="page.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="page.title"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <span x-text="'/' + page.slug"></span>
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <button @click="toggleStatus(page)" 
                                        :class="page.status === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'"
                                        class="px-2 py-1 text-xs rounded-full font-medium transition-colors hover:opacity-80">
                                    <span x-text="page.status === 'published' ? 'Pubblicata' : 'Bozza'"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600 dark:text-gray-400" x-text="page.theme || 'race-marathon'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600 dark:text-gray-400" x-text="formatDate(page.updated_at)"></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a :href="'../page-builder.php?page_id=' + page.id" 
                                       class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                                       title="Modifica con Page Builder">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <button @click="editPage(page)" 
                                            class="p-2 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                            title="Modifica info">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                        </svg>
                                    </button>
                                    <button @click="duplicatePage(page)" 
                                            class="p-2 text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/30 rounded-lg transition-colors"
                                            title="Duplica">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                    <a :href="'/?id_pagina=' + page.id" 
                                       target="_blank"
                                       class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition-colors"
                                       title="Visualizza">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <button @click="deletePage(page)" 
                                            class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                            title="Elimina">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- Empty State -->
        <div x-show="filteredPages.length === 0" class="p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-gray-600 dark:text-gray-400">Nessuna pagina trovata</p>
            <button @click="showCreateModal = true" class="mt-4 text-blue-600 hover:text-blue-700 dark:text-blue-400">
                Crea la prima pagina →
            </button>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div x-show="showCreateModal || showEditModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div @click="showCreateModal = false; showEditModal = false" 
                 class="fixed inset-0 bg-black opacity-50"></div>
            
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4" x-text="showEditModal ? 'Modifica Pagina' : 'Nuova Pagina'"></h2>
                
                <form @submit.prevent="showEditModal ? updatePage() : createPage()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titolo *</label>
                            <input type="text" 
                                   x-model="formData.title" 
                                   required
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug *</label>
                            <input type="text" 
                                   x-model="formData.slug" 
                                   required
                                   pattern="[a-z0-9-]+"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Solo lettere minuscole, numeri e trattini</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descrizione</label>
                            <textarea x-model="formData.description" 
                                      rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Stato</label>
                                <select x-model="formData.status" 
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                    <option value="draft">Bozza</option>
                                    <option value="published">Pubblicata</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tema</label>
                                <select x-model="formData.theme" 
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                    <?php foreach ($themes as $theme): ?>
                                        <option value="<?= htmlspecialchars($theme['alias']) ?>"><?= htmlspecialchars($theme['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
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
                            <span x-text="showEditModal ? 'Salva Modifiche' : 'Crea Pagina'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
function pagesManager() {
    return {
        pages: <?= json_encode($pages) ?>,
        filteredPages: <?= json_encode($pages) ?>,
        search: '',
        filterStatus: '',
        showCreateModal: false,
        showEditModal: false,
        formData: {
            title: '',
            slug: '',
            description: '',
            status: 'draft',
            theme: 'race-marathon'
        },
        editingId: null,
        
        init() {
            this.filterPages();
        },
        
        filterPages() {
            let filtered = this.pages;
            
            if (this.search) {
                const searchLower = this.search.toLowerCase();
                filtered = filtered.filter(p => 
                    p.title.toLowerCase().includes(searchLower) || 
                    p.slug.toLowerCase().includes(searchLower)
                );
            }
            
            if (this.filterStatus) {
                filtered = filtered.filter(p => p.status === this.filterStatus);
            }
            
            this.filteredPages = filtered;
        },
        
        async loadPages() {
            try {
                const response = await api.call('../api/pages.php');
                this.pages = response.data.data;
                this.filterPages();
                notify('Pagine ricaricate', 'success');
            } catch (error) {
                console.error('Error loading pages:', error);
            }
        },
        
        async createPage() {
            try {
                const response = await api.call('../api/pages.php', {
                    method: 'POST',
                    body: JSON.stringify(this.formData)
                });
                
                this.pages.unshift(response.data);
                this.filterPages();
                this.showCreateModal = false;
                this.resetForm();
                notify('Pagina creata con successo', 'success');
            } catch (error) {
                console.error('Error creating page:', error);
            }
        },
        
        editPage(page) {
            this.editingId = page.id;
            this.formData = {
                title: page.title,
                slug: page.slug,
                description: page.description || '',
                status: page.status,
                theme: page.theme || 'race-marathon'
            };
            this.showEditModal = true;
        },
        
        async updatePage() {
            try {
                const response = await api.call(`../api/pages.php?id=${this.editingId}`, {
                    method: 'PUT',
                    body: JSON.stringify(this.formData)
                });
                
                const index = this.pages.findIndex(p => p.id === this.editingId);
                if (index !== -1) {
                    this.pages[index] = response.data;
                }
                this.filterPages();
                this.showEditModal = false;
                this.resetForm();
                notify('Pagina aggiornata con successo', 'success');
            } catch (error) {
                console.error('Error updating page:', error);
            }
        },
        
        async toggleStatus(page) {
            try {
                const newStatus = page.status === 'published' ? 'draft' : 'published';
                const response = await api.call(`../api/pages.php?id=${page.id}`, {
                    method: 'PUT',
                    body: JSON.stringify({ status: newStatus })
                });
                
                const index = this.pages.findIndex(p => p.id === page.id);
                if (index !== -1) {
                    this.pages[index] = response.data;
                }
                this.filterPages();
                notify(`Pagina ${newStatus === 'published' ? 'pubblicata' : 'messa in bozza'}`, 'success');
            } catch (error) {
                console.error('Error toggling status:', error);
            }
        },
        
        async duplicatePage(page) {
            if (!confirm(`Duplicare la pagina "${page.title}"?`)) return;
            
            try {
                const response = await api.call(`../api/pages.php?id=${page.id}&action=duplicate`, {
                    method: 'POST'
                });
                
                this.pages.unshift(response.data);
                this.filterPages();
                notify('Pagina duplicata con successo', 'success');
            } catch (error) {
                console.error('Error duplicating page:', error);
            }
        },
        
        async deletePage(page) {
            if (!confirm(`Eliminare definitivamente la pagina "${page.title}"?`)) return;
            
            try {
                await api.call(`../api/pages.php?id=${page.id}`, {
                    method: 'DELETE'
                });
                
                this.pages = this.pages.filter(p => p.id !== page.id);
                this.filterPages();
                notify('Pagina eliminata con successo', 'success');
            } catch (error) {
                console.error('Error deleting page:', error);
            }
        },
        
        resetForm() {
            this.formData = {
                title: '',
                slug: '',
                description: '',
                status: 'draft',
                theme: 'race-marathon'
            };
            this.editingId = null;
        },
        
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('it-IT', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
}
</script>

