-- ============================================
-- Newsletter Subscribers Table
-- Tabella per gestire le iscrizioni newsletter
-- ============================================

-- Crea tabella se non esiste
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `subscribed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` DATETIME NULL DEFAULT NULL,
  `unsubscribed_at` DATETIME NULL DEFAULT NULL,
  `status` ENUM('pending', 'confirmed', 'unsubscribed') NOT NULL DEFAULT 'pending',
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` TEXT NULL DEFAULT NULL,
  `source` VARCHAR(50) NULL DEFAULT 'website',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `status` (`status`),
  KEY `subscribed_at` (`subscribed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indici per performance
CREATE INDEX IF NOT EXISTS `idx_email_status` ON `newsletter_subscribers` (`email`, `status`);
CREATE INDEX IF NOT EXISTS `idx_subscribed_date` ON `newsletter_subscribers` (`subscribed_at` DESC);

-- ============================================
-- Dati di test (opzionale)
-- ============================================

-- Esempio di iscrizioni test
INSERT INTO `newsletter_subscribers` (`name`, `email`, `status`, `confirmed_at`, `source`) 
VALUES 
  ('Mario Rossi', 'mario.rossi@example.com', 'confirmed', NOW(), 'website'),
  ('Laura Bianchi', 'laura.bianchi@example.com', 'confirmed', NOW(), 'website'),
  ('Giuseppe Verdi', 'giuseppe.verdi@example.com', 'pending', NULL, 'website')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ============================================
-- Stored Procedure: Conferma Iscrizione
-- ============================================

DELIMITER //

DROP PROCEDURE IF EXISTS `confirm_newsletter_subscription` //

CREATE PROCEDURE `confirm_newsletter_subscription`(
  IN p_email VARCHAR(255)
)
BEGIN
  UPDATE `newsletter_subscribers`
  SET 
    `status` = 'confirmed',
    `confirmed_at` = NOW()
  WHERE 
    `email` = p_email 
    AND `status` = 'pending';
    
  SELECT ROW_COUNT() as affected_rows;
END //

DELIMITER ;

-- ============================================
-- Stored Procedure: Disiscrizione
-- ============================================

DELIMITER //

DROP PROCEDURE IF EXISTS `unsubscribe_newsletter` //

CREATE PROCEDURE `unsubscribe_newsletter`(
  IN p_email VARCHAR(255)
)
BEGIN
  UPDATE `newsletter_subscribers`
  SET 
    `status` = 'unsubscribed',
    `unsubscribed_at` = NOW()
  WHERE 
    `email` = p_email 
    AND `status` IN ('pending', 'confirmed');
    
  SELECT ROW_COUNT() as affected_rows;
END //

DELIMITER ;

-- ============================================
-- View: Iscritti Attivi
-- ============================================

CREATE OR REPLACE VIEW `active_newsletter_subscribers` AS
SELECT 
  `id`,
  `name`,
  `email`,
  `subscribed_at`,
  `confirmed_at`,
  `source`
FROM 
  `newsletter_subscribers`
WHERE 
  `status` = 'confirmed'
ORDER BY 
  `confirmed_at` DESC;

-- ============================================
-- Statistiche Newsletter
-- ============================================

CREATE OR REPLACE VIEW `newsletter_stats` AS
SELECT 
  COUNT(*) as total_subscribers,
  SUM(CASE WHEN `status` = 'confirmed' THEN 1 ELSE 0 END) as confirmed_subscribers,
  SUM(CASE WHEN `status` = 'pending' THEN 1 ELSE 0 END) as pending_subscribers,
  SUM(CASE WHEN `status` = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed,
  SUM(CASE WHEN `subscribed_at` >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as last_7_days,
  SUM(CASE WHEN `subscribed_at` >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as last_30_days
FROM 
  `newsletter_subscribers`;

-- ============================================
-- Eventi Automatici (opzionale)
-- ============================================

-- Elimina iscrizioni non confermate dopo 30 giorni
-- NOTA: Decommentare se si vuole abilitare la pulizia automatica

/*
DROP EVENT IF EXISTS `cleanup_unconfirmed_subscribers`;

DELIMITER //

CREATE EVENT `cleanup_unconfirmed_subscribers`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
  DELETE FROM `newsletter_subscribers`
  WHERE 
    `status` = 'pending'
    AND `subscribed_at` < DATE_SUB(NOW(), INTERVAL 30 DAY);
END //

DELIMITER ;
*/

-- ============================================
-- Fine script
-- ============================================

