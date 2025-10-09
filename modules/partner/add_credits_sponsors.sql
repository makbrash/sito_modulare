-- ========================================
-- Aggiunta Sponsor Credits - Bologna Marathon
-- ========================================
--
-- Questo script:
-- 1. Aggiunge 'credits' all'ENUM group_type
-- 2. Inserisce tutti i loghi della cartella credits
-- ========================================

-- Step 1: Modifica ENUM per aggiungere 'credits'
ALTER TABLE sponsors 
MODIFY COLUMN group_type ENUM('main', 'official', 'technical', 'sponsor', 'credits') NOT NULL;

-- Step 2: Inserimento loghi credits
-- I loghi sono ordinati per importanza/priorità
INSERT INTO sponsors (name, category, group_type, image_path, is_active, sort_order) VALUES
('FIDAL', 'credits', 'credits', 'assets/images/sponsor/credits/LogoFidal.jpg', 1, 101),
('CONI Emilia Romagna', 'credits', 'credits', 'assets/images/sponsor/credits/CONI_EMILIA_ROMAGNA.png', 1, 102),
('Comune di Bologna', 'credits', 'credits', 'assets/images/sponsor/credits/06-comune-di-bologna-bn-rgb (8).png', 1, 103),
('Sport Valley Emilia Romagna', 'credits', 'credits', 'assets/images/sponsor/credits/SPORT-VALLEY-ER-COLOR.png', 1, 104),
('Bologna per lo Sport', 'credits', 'credits', 'assets/images/sponsor/credits/BO-per-lo-sport_LOGO_yellow.png', 1, 105),
('CSI Bologna', 'credits', 'credits', 'assets/images/sponsor/credits/Logo CSI Bologna.png', 1, 106),
('Run Tune Up', 'credits', 'credits', 'assets/images/sponsor/credits/Logo RTU-01.png', 1, 107),
('Bologna Marathon - Termal', 'credits', 'credits', 'assets/images/sponsor/credits/Logo BM-Termal.png', 1, 108),
('30km dei Portici', 'credits', 'credits', 'assets/images/sponsor/credits/30km.png', 1, 109),
('5km Bologna City Run', 'credits', 'credits', 'assets/images/sponsor/credits/5km_Bologna City Run.png', 1, 110)

ON DUPLICATE KEY UPDATE
    image_path = VALUES(image_path),
    is_active = VALUES(is_active),
    sort_order = VALUES(sort_order);

-- Verifica inserimento
SELECT COUNT(*) as 'Credits inseriti' FROM sponsors WHERE group_type = 'credits';

-- Mostra tutti i credits
SELECT id, name, group_type, image_path, is_active 
FROM sponsors 
WHERE group_type = 'credits' 
ORDER BY sort_order ASC;

