-- Install Text module: aggiunge un blocco contenuto alla home
INSERT INTO page_modules (page_id, module_name, config, order_index, is_active)
SELECT 1, 'text', '{"content":"Benvenuti alla Bologna Marathon"}', 2, 1
WHERE NOT EXISTS (
  SELECT 1 FROM page_modules WHERE page_id = 1 AND module_name = 'text'
);

