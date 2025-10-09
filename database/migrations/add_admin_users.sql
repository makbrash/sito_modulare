-- =====================================================
-- Admin Users Table - Sistema Autenticazione
-- NOTA: Sistema DISABILITATO di default (AUTH_ENABLED=false)
-- =====================================================

CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(100) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'Bcrypt hash',
    `email` VARCHAR(200) UNIQUE NOT NULL,
    `display_name` VARCHAR(150) NULL,
    `role` ENUM('super_admin', 'admin', 'editor', 'viewer') DEFAULT 'editor' NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE NOT NULL,
    `last_login` TIMESTAMP NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `failed_login_attempts` INT UNSIGNED DEFAULT 0 NOT NULL,
    `locked_until` TIMESTAMP NULL,
    `must_change_password` BOOLEAN DEFAULT FALSE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Admin Sessions Table
-- =====================================================

CREATE TABLE IF NOT EXISTS `admin_sessions` (
    `id` VARCHAR(128) PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `payload` TEXT NOT NULL,
    `last_activity` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_last_activity` (`last_activity`),
    FOREIGN KEY (`user_id`) REFERENCES `admin_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Admin Activity Log Table
-- =====================================================

CREATE TABLE IF NOT EXISTS `admin_activity_log` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NULL COMMENT 'page, module, theme, etc.',
    `entity_id` INT UNSIGNED NULL,
    `description` TEXT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Password Reset Tokens Table
-- =====================================================

CREATE TABLE IF NOT EXISTS `admin_password_resets` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `token` VARCHAR(64) UNIQUE NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `used` BOOLEAN DEFAULT FALSE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    INDEX `idx_token` (`token`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_expires_at` (`expires_at`),
    FOREIGN KEY (`user_id`) REFERENCES `admin_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- User iniziale di default (DISABILITATO)
-- Password: admin123 (DA CAMBIARE!)
-- UNCOMMENT per creare utente iniziale
-- =====================================================

/*
INSERT INTO `admin_users` (`username`, `password_hash`, `email`, `display_name`, `role`, `is_active`, `must_change_password`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@bolognamarathon.run', 'Amministratore', 'super_admin', 1, 1);
*/

-- =====================================================
-- ATTIVAZIONE SISTEMA AUTH
-- =====================================================
-- 
-- Per attivare il sistema di autenticazione:
-- 1. Eseguire questo SQL file (uncommentare INSERT utente)
-- 2. Modificare .env: AUTH_ENABLED=true
-- 3. Login: admin / admin123
-- 4. CAMBIARE PASSWORD immediatamente!
-- 5. Creare altri utenti dal pannello admin
-- 
-- =====================================================

