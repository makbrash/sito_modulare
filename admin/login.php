<?php
/**
 * Admin Login Page
 * Sistema autenticazione Bologna Marathon Admin
 * 
 * NOTA: Questa pagina è visibile solo se AUTH_ENABLED=true
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth/AuthService.php';

use BolognaMarathon\Auth\AuthService;

$database = new Database();
$db = $database->getConnection();
$authService = new AuthService($db);

// Se auth disabilitato, redirect a dashboard
if (!$authService->isAuthEnabled()) {
    header('Location: dashboard.php');
    exit;
}

// Se già autenticato, redirect a dashboard
if ($authService->isAuthenticated()) {
    $returnUrl = $_GET['return'] ?? 'dashboard.php';
    header('Location: ' . $returnUrl);
    exit;
}

$error = '';
$success = '';

// Gestione form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username e password sono obbligatori';
    } else {
        $result = $authService->login($username, $password);
        
        if ($result['success']) {
            $returnUrl = $_GET['return'] ?? 'dashboard.php';
            header('Location: ' . $returnUrl);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bologna Marathon Admin</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    
    <div class="w-full max-w-md">
        
        <!-- Login Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-8 text-center">
                <div class="w-20 h-20 bg-white rounded-full mx-auto mb-4 flex items-center justify-center">
                    <span class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-500 to-purple-600">BM</span>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">Bologna Marathon</h1>
                <p class="text-blue-100">Admin Dashboard</p>
            </div>
            
            <!-- Form -->
            <div class="p-8">
                
                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <p class="text-sm text-red-700 dark:text-red-300"><?= htmlspecialchars($error) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <p class="text-sm text-green-700 dark:text-green-300"><?= htmlspecialchars($success) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="space-y-6">
                    
                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-user w-5 mr-2"></i>
                            Username
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required
                            autofocus
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                            placeholder="Inserisci username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        >
                    </div>
                    
                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-lock w-5 mr-2"></i>
                            Password
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all pr-12"
                                placeholder="Inserisci password"
                            >
                            <button 
                                type="button" 
                                onclick="togglePassword()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                <i id="password-icon" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Remember Me -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            >
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Ricordami</span>
                        </label>
                        
                        <a href="#" class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                            Password dimenticata?
                        </a>
                    </div>
                    
                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-blue-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all transform hover:scale-105"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Accedi
                    </button>
                    
                </form>
                
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-900 px-8 py-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>&copy; <?= date('Y') ?> Bologna Marathon</span>
                    <a href="/" target="_blank" class="hover:text-blue-500">
                        <i class="fas fa-external-link-alt mr-1"></i>
                        Visualizza Sito
                    </a>
                </div>
            </div>
            
        </div>
        
        <!-- Info Box -->
        <div class="mt-6 p-4 bg-white/90 dark:bg-gray-800/90 backdrop-blur rounded-lg text-center">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                Sistema autenticazione attivo
            </p>
        </div>
        
    </div>
    
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }
        
        // Auto-focus username field
        document.getElementById('username').focus();
    </script>
    
</body>
</html>

