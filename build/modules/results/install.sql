-- Install Results module: crea config di esempio nella pagina home
INSERT INTO page_modules (page_id, module_name, config, order_index, is_active)
SELECT 1, 'results', '{"race_id":1, "limit": 20, "show_categories": true}', 3, 1
WHERE NOT EXISTS (
  SELECT 1 FROM page_modules WHERE page_id = 1 AND module_name = 'results'
);

