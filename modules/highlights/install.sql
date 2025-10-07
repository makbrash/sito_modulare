-- Installazione modulo Highlights
-- Questo script registra il modulo nel database

INSERT INTO modules_registry (
    name, 
    component_path, 
    css_class,
    default_config, 
    is_active
) VALUES (
    'highlights',
    'highlights/highlights.php',
    'highlights',
    '{"title": "Ultime NEWS", "highlights": [{"image": "assets/images/marathon-start.jpg", "title": "Presentata la Termal Bologna Marathon 2025: in 10.000 runner attesi", "url": "#"}, {"image": "assets/images/marathon-start.jpg", "title": "Termal Bologna Marathon: tutte le chiusure delle strade del 2 Marzo", "url": "#"}, {"image": "assets/images/marathon-start.jpg", "title": "DOCUFILM \\"THE SECRET IS BOLOGNA\\"", "url": "#"}, {"image": "assets/images/marathon-start.jpg", "title": "INFORMAZIONI UTILI - TERMAL BOLOGNA MARATHON 2025", "url": "#"}, {"image": "assets/images/marathon-start.jpg", "title": "Anche la 30 Km dei Portici è SOLD OUT!", "url": "#"}]}',
    1
) ON DUPLICATE KEY UPDATE
    component_path = VALUES(component_path),
    css_class = VALUES(css_class),
    default_config = VALUES(default_config),
    is_active = VALUES(is_active);