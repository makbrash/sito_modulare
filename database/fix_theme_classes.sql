-- Script per correggere le classi tema nel database
-- Aggiorna le classi tema per essere coerenti con il CSS

-- Aggiorna Marathon
UPDATE theme_identities 
SET class_name = 'race-marathon' 
WHERE name = 'Marathon' AND class_name = 'marathon';

-- Aggiorna 30K Portici  
UPDATE theme_identities 
SET class_name = 'race-portici' 
WHERE name = '30K Portici' AND class_name = 'portici';

-- Aggiorna Run Tune Up
UPDATE theme_identities 
SET class_name = 'race-run-tune-up' 
WHERE name = 'Run Tune Up' AND class_name = 'run-tune-up';

-- Aggiorna 5K
UPDATE theme_identities 
SET class_name = 'race-5k' 
WHERE name = '5K' AND class_name = '5k';

-- Aggiorna anche le pagine che potrebbero avere i vecchi nomi
UPDATE pages 
SET theme = 'race-marathon' 
WHERE theme = 'marathon';

UPDATE pages 
SET theme = 'race-portici' 
WHERE theme = 'portici';

UPDATE pages 
SET theme = 'race-run-tune-up' 
WHERE theme = 'run-tune-up';

UPDATE pages 
SET theme = 'race-5k' 
WHERE theme = '5k';

-- Mostra i risultati
SELECT 'Temi aggiornati:' as status;
SELECT name, class_name, alias FROM theme_identities ORDER BY name;

SELECT 'Pagine aggiornate:' as status;
SELECT id, title, theme FROM pages ORDER BY id;

