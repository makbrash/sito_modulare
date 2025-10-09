-- =====================================================
-- Sistema Modelli Moduli Globali
-- =====================================================
-- 
-- FUNZIONALITÀ:
-- - Salva moduli come "modelli globali"
-- - Un'unica istanza master condivisa
-- - Modifica master → aggiorna tutte le pagine
-- - Possibilità di "staccare" e personalizzare
--
-- Data: Ottobre 2024
-- =====================================================

-- IMPORTANTE: Permetti page_id NULL per template master
-- I template master non appartengono a nessuna pagina (page_id = NULL)
ALTER TABLE module_instances 
MODIFY COLUMN page_id INT DEFAULT NULL;

-- Aggiungi colonne alla tabella module_instances
ALTER TABLE module_instances 
ADD COLUMN is_template BOOLEAN DEFAULT FALSE COMMENT 'Indica se questa istanza è un modello master',
ADD COLUMN template_name VARCHAR(200) DEFAULT NULL COMMENT 'Nome del modello (solo per is_template=1)',
ADD COLUMN template_instance_id INT DEFAULT NULL COMMENT 'ID istanza master se usa un template',
ADD INDEX idx_is_template (is_template),
ADD INDEX idx_template_instance (template_instance_id);

-- Aggiungi foreign key per template_instance_id
ALTER TABLE module_instances
ADD CONSTRAINT fk_template_instance 
FOREIGN KEY (template_instance_id) 
REFERENCES module_instances(id) 
ON DELETE SET NULL;

-- =====================================================
-- SPIEGAZIONE CAMPI
-- =====================================================
--
-- is_template (BOOLEAN):
--   - TRUE: Questa istanza è un MODELLO MASTER
--   - FALSE: Istanza normale o che usa un template
--   - Modelli master hanno page_id = NULL
--
-- template_name (VARCHAR):
--   - Nome descrittivo del modello (es: "Menu Principale")
--   - Solo per istanze con is_template = TRUE
--   - NULL per istanze normali
--
-- template_instance_id (INT):
--   - ID dell'istanza master da cui prendere la config
--   - Se impostato, questa istanza usa un template
--   - NULL = istanza locale indipendente
--
-- =====================================================
-- ESEMPI DI UTILIZZO
-- =====================================================

-- Esempio 1: Creare modello master
-- INSERT INTO module_instances 
--   (page_id, module_name, instance_name, config, is_template, template_name, order_index)
-- VALUES
--   (NULL, 'menu', 'template_menu_main', '{"items": [...]}', TRUE, 'Menu Principale', 0);

-- Esempio 2: Usare template in una pagina
-- INSERT INTO module_instances
--   (page_id, module_name, instance_name, config, template_instance_id, order_index)
-- VALUES
--   (1, 'menu', 'menu_1', '{}', 100, 1);
--   -- Prende config dall'istanza ID 100

-- Esempio 3: Query per ottenere modelli disponibili
-- SELECT 
--   id, 
--   module_name, 
--   template_name,
--   config,
--   created_at
-- FROM module_instances
-- WHERE is_template = TRUE
-- ORDER BY template_name;

-- Esempio 4: Query per ottenere config effettiva di un'istanza
-- SELECT 
--   mi.id,
--   mi.instance_name,
--   COALESCE(template.config, mi.config) as effective_config,
--   CASE 
--     WHEN mi.template_instance_id IS NOT NULL THEN TRUE 
--     ELSE FALSE 
--   END as uses_template
-- FROM module_instances mi
-- LEFT JOIN module_instances template ON mi.template_instance_id = template.id
-- WHERE mi.page_id = 1;

-- =====================================================
-- VERIFICA INSTALLAZIONE
-- =====================================================

-- Controlla che le colonne siano state aggiunte
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'module_instances'
  AND COLUMN_NAME IN ('is_template', 'template_name', 'template_instance_id')
ORDER BY ORDINAL_POSITION;

-- Verifica foreign key
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'module_instances'
  AND CONSTRAINT_NAME = 'fk_template_instance';

-- =====================================================
-- NOTE IMPORTANTI
-- =====================================================
--
-- 1. RENDERING:
--    Quando renderizzi un modulo, controlla prima se ha template_instance_id.
--    Se sì, usa la config dell'istanza master.
--
-- 2. MODIFICA GLOBALE:
--    Quando modifichi un modello master (is_template=1),
--    tutte le istanze con template_instance_id che lo referenziano
--    prenderanno automaticamente la nuova config.
--
-- 3. STACCA DA TEMPLATE:
--    Per personalizzare un'istanza:
--    - Copia config dal master
--    - Imposta template_instance_id = NULL
--    - Ora è un'istanza indipendente
--
-- 4. ELIMINAZIONE MASTER:
--    Se elimini un modello master, le istanze che lo usano
--    avranno template_instance_id = NULL (grazie a ON DELETE SET NULL)
--    e manterranno l'ultima config salvata.
--
-- =====================================================

-- Query di test (opzionale)
SELECT 
    id,
    page_id,
    module_name,
    instance_name,
    is_template,
    template_name,
    template_instance_id,
    created_at
FROM module_instances
ORDER BY 
    is_template DESC, 
    page_id, 
    order_index;

