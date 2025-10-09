<?php
/**
 * Admin Dashboard - Bologna Marathon
 * Dashboard principale unificata
 */

session_start();

require_once '../config/database.php';

// TODO: Quando AUTH_ENABLED, verificare sessione
// if (defined('AUTH_ENABLED') && AUTH_ENABLED && !isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit;
// }

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

// Statistiche rapide
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Conta pagine
    $pagesStmt = $db->query("SELECT COUNT(*) as total, 
                              SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                              SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
                              FROM pages");
    $pagesStats = $pagesStmt->fetch();
    
    // Conta moduli
    $modulesStmt = $db->query("SELECT COUNT(*) as total FROM modules_registry WHERE is_active = 1");
    $modulesStats = $modulesStmt->fetch();
    
    // Conta istanze moduli
    $instancesStmt = $db->query("SELECT COUNT(*) as total FROM module_instances WHERE is_active = 1");
    $instancesStats = $instancesStmt->fetch();
    
    // Conta temi
    $themesStmt = $db->query("SELECT COUNT(*) as total FROM theme_identities WHERE is_active = 1");
    $themesStats = $themesStmt->fetch();
    
} catch (Exception $e) {
    $pagesStats = ['total' => 0, 'published' => 0, 'draft' => 0];
    $modulesStats = ['total' => 0];
    $instancesStats = ['total' => 0];
    $themesStats = ['total' => 0];
}

// Start output buffering to capture content
ob_start();
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Pagine -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Pagine</h3>
            <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </div>
        <div class="flex items-baseline space-x-3">
            <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= $pagesStats['total'] ?></p>
            <span class="text-sm text-green-600 dark:text-green-400"><?= $pagesStats['published'] ?> pubblicate</span>
        </div>
        <div class="mt-4">
            <a href="pages/pages-list.php" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                Gestisci pagine →
            </a>
        </div>
    </div>

    <!-- Moduli -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Moduli</h3>
            <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z" />
            </svg>
        </div>
        <div class="flex items-baseline space-x-3">
            <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= $modulesStats['total'] ?></p>
            <span class="text-sm text-gray-600 dark:text-gray-400"><?= $instancesStats['total'] ?> istanze</span>
        </div>
        <div class="mt-4">
            <a href="pages/modules-manager.php" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                Gestisci moduli →
            </a>
        </div>
    </div>

    <!-- Temi -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Temi</h3>
            <svg class="w-8 h-8 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
            </svg>
        </div>
        <div class="flex items-baseline space-x-3">
            <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= $themesStats['total'] ?></p>
            <span class="text-sm text-gray-600 dark:text-gray-400">attivi</span>
        </div>
        <div class="mt-4">
            <a href="pages/themes-editor.php" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                Gestisci temi →
            </a>
        </div>
    </div>

    <!-- Page Builder -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Page Builder</h3>
            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
            </svg>
        </div>
        <div class="mb-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Costruisci e modifica pagine visualmente</p>
        </div>
        <div class="mt-4">
            <a href="page-builder.php" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                Apri Page Builder →
            </a>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700 mb-8">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Azioni Rapide</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="pages/pages-list.php?action=new" class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="text-sm font-medium text-gray-900 dark:text-white">Nuova Pagina</span>
        </a>

        <a href="api/modules.php?action=sync" class="flex items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span class="text-sm font-medium text-gray-900 dark:text-white">Sincronizza Moduli</span>
        </a>

        <a href="/" target="_blank" class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <span class="text-sm font-medium text-gray-900 dark:text-white">Visualizza Sito</span>
        </a>
    </div>
</div>

<!-- Recent Pages -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Pagine Recenti</h2>
        <a href="pages/pages-list.php" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
            Vedi tutte →
        </a>
    </div>
    
    <?php
    try {
        $recentStmt = $db->query("SELECT id, title, slug, status, updated_at FROM pages ORDER BY updated_at DESC LIMIT 5");
        $recentPages = $recentStmt->fetchAll();
        
        if (!empty($recentPages)): ?>
            <div class="space-y-3">
                <?php foreach ($recentPages as $page): ?>
                    <div class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg transition-colors">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($page['title']) ?></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <?= htmlspecialchars($page['slug']) ?> • 
                                <?= date('d/m/Y H:i', strtotime($page['updated_at'])) ?>
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-1 text-xs rounded-full <?= $page['status'] === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' ?>">
                                <?= $page['status'] === 'published' ? 'Pubblicata' : 'Bozza' ?>
                            </span>
                            <a href="page-builder.php?page_id=<?= $page['id'] ?>" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">
                Nessuna pagina trovata. <a href="pages/pages-list.php?action=new" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">Crea la prima pagina</a>
            </p>
        <?php endif;
    } catch (Exception $e) {
        echo '<p class="text-sm text-red-500 text-center py-8">Errore caricamento pagine</p>';
    }
    ?>
</div>

<?php
// Capture the buffered content
$pageContent = ob_get_clean();

// Include layout (which will use $pageContent)
require_once 'components/layout.php';

