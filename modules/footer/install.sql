-- Install Footer module: posiziona in fondo alla home
INSERT INTO page_modules (page_id, module_name, config, order_index, is_active)
SELECT 1, 'footer', '{"columns":4, "social": true}', 9, 1
WHERE NOT EXISTS (
  SELECT 1 FROM page_modules WHERE page_id = 1 AND module_name = 'footer'
);

