-- Install Race Cards module: sezione gare nella home
INSERT INTO page_modules (page_id, module_name, config, order_index, is_active)
SELECT 1, 'race-cards', '{"layout":"vertical"}', 4, 1
WHERE NOT EXISTS (
  SELECT 1 FROM page_modules WHERE page_id = 1 AND module_name = 'race-cards'
);

