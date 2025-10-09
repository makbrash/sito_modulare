<?php
// Calcola percorso base dinamico per evitare problemi di navigazione
$currentDir = dirname($_SERVER['PHP_SELF']);
$basePath = '';
if (strpos($currentDir, '/admin/pages') !== false) {
    $basePath = '../'; // Se siamo in admin/pages/
} elseif (strpos($currentDir, '/admin') !== false) {
    $basePath = ''; // Se siamo in admin/
} else {
    $basePath = 'admin/'; // Se siamo fuori da admin
}
?>

<!-- Admin Header -->
<header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
    <div class="flex items-center justify-between">
        
        <!-- Left: Sidebar Toggle + Title -->
        <div class="flex items-center space-x-4">
            <button @click="sidebarOpen = !sidebarOpen" 
                    class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            
            <div>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Bologna Marathon</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">Sistema Amministrazione</p>
            </div>
        </div>
        
        <!-- Right: Actions -->
        <div class="flex items-center space-x-3">
            
            <!-- Dark Mode Toggle -->
            <button @click="darkMode = !darkMode" 
                    class="p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </button>
            
            <!-- View Site -->
            <a href="/" target="_blank" 
               class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                <span class="hidden sm:inline">Visualizza Sito</span>
            </a>
            
            <!-- User Menu -->
            <div class="relative" x-data="{ 
                open: false,
                handleLogout() {
                    if (confirm('Sei sicuro di voler uscire?')) {
                        fetch('<?= $basePath ?>api/auth.php?action=logout', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            window.location.href = '<?= $basePath ?>login.php';
                        })
                        .catch(error => {
                            console.error('Errore logout:', error);
                            window.location.href = '<?= $basePath ?>login.php';
                        });
                    }
                }
            }">
                <button @click="open = !open" 
                        class="flex items-center space-x-2 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                        <?php 
                        if (isset($currentUser) && !empty($currentUser['display_name'])) {
                            $initials = strtoupper(substr($currentUser['display_name'], 0, 2));
                            echo htmlspecialchars($initials);
                        } else {
                            echo 'AD';
                        }
                        ?>
                    </div>
                </button>
                
                <!-- Dropdown Menu -->
                <div x-show="open" 
                     @click.away="open = false"
                     x-cloak
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50">
                    
                    <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            <?= isset($currentUser) ? htmlspecialchars($currentUser['display_name'] ?? $currentUser['username']) : 'Admin' ?>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <?= isset($currentUser) ? htmlspecialchars($currentUser['email'] ?? '') : 'admin@bolognamarathon.run' ?>
                        </p>
                    </div>
                    
                    <a href="<?= $basePath ?>pages/settings.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-cog w-4 mr-2"></i> Impostazioni
                    </a>
                    
                    <div class="border-t border-gray-200 dark:border-gray-700"></div>
                    
                    <button @click="handleLogout()" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-sign-out-alt w-4 mr-2"></i> Logout
                    </button>
                </div>
            </div>
            
        </div>
    </div>
</header>

