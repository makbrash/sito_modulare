-- =====================================================
-- Fix: Permetti page_id NULL per template master
-- =====================================================
--
-- PROBLEMA: 
-- La colonna page_id non accetta NULL, ma i modelli master
-- non appartengono a nessuna pagina specifica (page_id = NULL)
--
-- SOLUZIONE:
-- Modifica colonna per permettere NULL
--
-- =====================================================

-- Modifica colonna page_id per permettere NULL
ALTER TABLE module_instances 
MODIFY COLUMN page_id INT DEFAULT NULL;

-- Verifica modifica
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'module_instances'
  AND COLUMN_NAME = 'page_id';

-- =====================================================
-- NOTE:
-- Dopo questa modifica, i template master potranno avere
-- page_id = NULL mentre le istanze normali avranno
-- page_id = [ID_PAGINA]
-- =====================================================

