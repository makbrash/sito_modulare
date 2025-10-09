<?php
/**
 * Admin Layout Wrapper
 * Layout principale per tutte le pagine admin
 */

if (!isset($pageTitle)) {
    $pageTitle = 'Admin Dashboard';
}

if (!isset($currentPage)) {
    $currentPage = '';
}
?>
<!DOCTYPE html>
<html lang="it" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', sidebarOpen: window.innerWidth >= 1024 }" 
      :class="{ 'dark': darkMode }" 
      x-init="
        $watch('darkMode', val => localStorage.setItem('darkMode', val));
        darkMode = localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches);
      ">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Bologna Marathon Admin</title>
    
    <!-- Tailwind CSS CDN (Development Only) -->
    <?php if (APP_DEBUG): ?>
        <script src="https://cdn.tailwindcss.com"></script>
    <?php else: ?>
        <!-- In production, use compiled Tailwind CSS -->
        <link rel="stylesheet" href="../../assets/css/admin/tailwind.min.css">
    <?php endif; ?>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="../../assets/css/admin/page-builder.css">
    
    <style>
        [x-cloak] { display: none !important; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #64748b; }
        
        /* Smooth transitions */
        * { transition-property: background-color, border-color, color; transition-duration: 150ms; }
        
        /* Force dark mode styles */
        .dark { color-scheme: dark; }
        .dark body { background-color: #111827 !important; color: #f9fafb !important; }
        .dark .bg-gray-50 { background-color: #111827 !important; }
        .dark .bg-white { background-color: #1f2937 !important; }
        .dark .text-gray-900 { color: #f9fafb !important; }
        .dark .text-gray-700 { color: #d1d5db !important; }
        .dark .text-gray-600 { color: #9ca3af !important; }
        .dark .border-gray-200 { border-color: #374151 !important; }
        .dark .border-gray-300 { border-color: #374151 !important; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    
    <div class="flex h-screen overflow-hidden">
        
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <?php include __DIR__ . '/header.php'; ?>
            
            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <div class="p-6">
                    <div class="max-w-7xl mx-auto">
                        <?php if (isset($pageTitle) && $pageTitle !== 'Dashboard'): ?>
                            <div class="mb-6">
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($pageTitle) ?></h1>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Page Content Goes Here -->
                        <?php 
                        // Content will be rendered from the including page
                        // The including page should not have its own HTML structure
                        ?>
                    </div>
                </div>
                
                <?php include __DIR__ . '/footer.php'; ?>
            </main>
            
        </div>
    </div>
    
    <!-- Toast Notifications -->
    <div x-data="{ notifications: [] }" 
         @notify.window="notifications.push($event.detail); setTimeout(() => notifications.shift(), 3000)"
         class="fixed bottom-4 right-4 z-50 space-y-2">
        <template x-for="(notification, index) in notifications" :key="index">
            <div x-show="true" 
                 x-transition:enter="transform ease-out duration-300 transition"
                 x-transition:enter-start="translate-y-2 opacity-0"
                 x-transition:enter-end="translate-y-0 opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 :class="{
                     'bg-green-500': notification.type === 'success',
                     'bg-red-500': notification.type === 'error',
                     'bg-blue-500': notification.type === 'info',
                     'bg-yellow-500': notification.type === 'warning'
                 }"
                 class="px-4 py-3 rounded-lg shadow-lg text-white max-w-sm">
                <p class="text-sm font-medium" x-text="notification.message"></p>
            </div>
        </template>
    </div>
    
    <!-- Custom Admin JS -->
    <script src="../../assets/js/admin/page-builder.js"></script>
    
    <script>
        // Global notify function
        window.notify = function(message, type = 'info') {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { message, type }
            }));
        };
        
        // Global API helper
        window.api = {
            async call(endpoint, options = {}) {
                try {
                    const response = await fetch(endpoint, {
                        ...options,
                        headers: {
                            'Content-Type': 'application/json',
                            ...options.headers
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (!data.success) {
                        throw new Error(data.message || 'Errore API');
                    }
                    
                    return data;
                } catch (error) {
                    console.error('API Error:', error);
                    notify(error.message, 'error');
                    throw error;
                }
            }
        };
    </script>
</body>
</html>

