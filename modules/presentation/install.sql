-- Installazione modulo Presentation
-- Questo script registra il modulo nel database

INSERT INTO modules_registry (
    name, 
    component_path, 
    css_class,
    default_config, 
    is_active
) VALUES (
    'presentation',
    'presentation/presentation.php',
    'presentation',
    '{"title": "DOVE LO SPORT INCONTRA", "subtitle": "LA STORIA", "description1": "La Maratona di Bologna è un grande evento dove lo sport si fonde con la storia, la cultura, l\'arte, la musica e il buon cibo di una delle città più antiche d\'Europa.", "description2": "Tre giorni di festa in Piazza Maggiore, una delle piazze più belle d\'Italia, offrendo a tutti i partecipanti diverse opportunità per visitare e scoprire una città unica.", "image_url": "assets/images/marathon-start.jpg", "image_alt": "Maratona di Bologna", "image_position": "right", "stats": [{"icon": "fas fa-running", "number": "10.000+", "label": "Runner"}, {"icon": "fas fa-globe", "number": "50+", "label": "Nazioni"}, {"icon": "fas fa-music", "number": "3", "label": "Giorni di Eventi"}]}',
    1
) ON DUPLICATE KEY UPDATE
    component_path = VALUES(component_path),
    css_class = VALUES(css_class),
    default_config = VALUES(default_config),
    is_active = VALUES(is_active);
