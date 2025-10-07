-- Aggiornamento sistema temi per editor colori e tema di default
-- Esegui questo script per aggiornare il database esistente

-- Aggiungi colonne per il sistema avanzato
ALTER TABLE theme_identities 
ADD COLUMN is_default BOOLEAN DEFAULT FALSE,
ADD COLUMN colors JSON DEFAULT NULL;

-- Aggiorna i temi esistenti con colori di default
UPDATE theme_identities SET colors = JSON_OBJECT(
    'primary', '#23a8eb',
    'secondary', '#1583b9', 
    'accent', '#22d3ee',
    'info', '#5DADE2',
    'success', '#52bd7b',
    'warning', '#F39C12',
    'error', '#E74C3C',
    'countdown_color', '#00ffff'
) WHERE colors IS NULL OR colors = 'null';

-- Imposta il tema Marathon come default
UPDATE theme_identities SET is_default = TRUE WHERE class_name = 'race-marathon' OR name = 'Marathon';

-- Aggiorna i temi esistenti con colori specifici
UPDATE theme_identities SET colors = JSON_OBJECT(
    'primary', '#dc335e',
    'secondary', '#af1b40',
    'accent', '#22d3ee',
    'info', '#e95177',
    'success', '#52bd7b',
    'warning', '#F39C12',
    'error', '#E74C3C',
    'countdown_color', '#00ffff'
) WHERE class_name = 'race-portici';

UPDATE theme_identities SET colors = JSON_OBJECT(
    'primary', '#cbdf44',
    'secondary', '#a0b229',
    'accent', '#22d3ee',
    'info', '#d0d95a',
    'success', '#52bd7b',
    'warning', '#F39C12',
    'error', '#E74C3C',
    'countdown_color', '#00ffff'
) WHERE class_name = 'race-run-tune-up';

UPDATE theme_identities SET colors = JSON_OBJECT(
    'primary', '#ff6b35',
    'secondary', '#e55a2b',
    'accent', '#22d3ee',
    'info', '#ff8c5a',
    'success', '#52bd7b',
    'warning', '#F39C12',
    'error', '#E74C3C',
    'countdown_color', '#00ffff'
) WHERE class_name = 'race-5k';

-- Tema Kids Run - Tonalità verde per bambini
UPDATE theme_identities SET colors = JSON_OBJECT(
    'primary', '#007b5f',
    'secondary', '#005a47',
    'accent', '#22d3ee',
    'info', '#00a67a',
    'success', '#52bd7b',
    'warning', '#F39C12',
    'error', '#E74C3C',
    'countdown_color', '#00ffff'
) WHERE class_name = 'race-kidsrun';