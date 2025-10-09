-- =====================================================
-- Migrazione: Aggiunta colonna theme alla tabella pages
-- =====================================================
-- 
-- PROBLEMA: Il sistema di temi usa la colonna 'theme' ma non esiste nello schema
-- SOLUZIONE: Aggiungiamo la colonna theme con valore default
--
-- Data: Ottobre 2024
-- =====================================================

-- Controlla se la colonna esiste già (per evitare errori)
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'pages' 
  AND COLUMN_NAME = 'theme';

-- Aggiungi colonna solo se non esiste
SET @query = IF(@col_exists = 0,
    'ALTER TABLE pages ADD COLUMN theme VARCHAR(50) DEFAULT ''race-marathon'' AFTER status',
    'SELECT ''La colonna theme esiste già'' AS message'
);

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verifica che la colonna sia stata aggiunta
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_DEFAULT,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'pages'
  AND COLUMN_NAME = 'theme';

-- =====================================================
-- NOTA: Dopo aver eseguito questo script, tutte le
-- pagine esistenti avranno il tema default 'race-marathon'
-- =====================================================

-- Query di verifica (opzionale)
SELECT 
    id,
    slug,
    title,
    theme,
    status
FROM pages
ORDER BY id;

