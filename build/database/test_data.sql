-- Database Test - Bologna Marathon
-- Dati di esempio per test

-- Inserimento gara di test
INSERT INTO races (id, name, date, distance, status) VALUES
(1, 'Bologna Marathon 2025', '2025-04-15', '42.195 km', 'completed'),
(2, 'Bologna Half Marathon 2025', '2025-04-15', '21.097 km', 'completed');

-- Inserimento risultati gara di test
INSERT INTO race_results (race_id, position, bib_number, runner_name, category, time_result) VALUES
-- Marathon 2025
(1, 1, '001', 'Mario Rossi', 'M', '02:15:30'),
(1, 2, '002', 'Giulia Bianchi', 'F', '02:18:45'),
(1, 3, '003', 'Luca Verdi', 'M', '02:20:12'),
(1, 4, '004', 'Anna Neri', 'F', '02:22:18'),
(1, 5, '005', 'Marco Blu', 'M', '02:25:33'),
(1, 6, '006', 'Sara Gialli', 'F', '02:28:45'),
(1, 7, '007', 'Paolo Rossi', 'M40', '02:30:15'),
(1, 8, '008', 'Elena Verde', 'F40', '02:32:22'),
(1, 9, '009', 'Giuseppe Bianco', 'M', '02:35:18'),
(1, 10, '010', 'Francesca Rosa', 'F', '02:37:45'),
(2, 1, '101', 'Antonio Nero', 'M', '01:05:30'),
(2, 2, '102', 'Chiara Viola', 'F', '01:08:15'),
(2, 3, '103', 'Roberto Grigio', 'M', '01:10:22'),
(2, 4, '104', 'Valentina Azzurra', 'F', '01:12:45'),
(2, 5, '105', 'Stefano Marrone', 'M40', '01:15:18');

-- Inserimento contenuti dinamici
INSERT INTO dynamic_content (content_type, title, content, metadata) VALUES
('news', 'Bologna Marathon 2025: Record di Partecipanti', 'La Bologna Marathon 2025 ha registrato un record di partecipanti con oltre 15.000 iscritti...', '{"featured": true, "date": "2025-04-15"}'),
('news', 'Percorso Aggiornato per la Prossima Edizione', 'Il percorso della Bologna Marathon è stato aggiornato per migliorare la sicurezza e l\'esperienza dei runner...', '{"featured": false, "date": "2025-03-20"}'),
('sponsor', 'Sponsor Ufficiali 2025', 'Ringraziamo tutti gli sponsor che hanno reso possibile la Bologna Marathon 2025...', '{"type": "sponsor", "priority": 1}');

-- Inserimento pagina home di esempio
INSERT INTO pages (slug, title, description, template, layout_config, css_variables, status) VALUES
('home', 'Bologna Marathon 2025', 'Sito ufficiale della Bologna Marathon', 'home', 
'{"layout": "fullwidth", "sections": ["hero", "content", "results"]}',
'{}',
'published')
ON DUPLICATE KEY UPDATE 
status = 'published',
title = VALUES(title),
description = VALUES(description),
css_variables = VALUES(css_variables);

-- Inserimento moduli per pagina home
INSERT INTO page_modules (page_id, module_name, config, order_index) VALUES
(1, 'hero', '{"title": "Bologna Marathon 2025", "subtitle": "La corsa più bella d\'Italia", "image": "hero-bg.jpg", "layout": "2col", "height": "100vh"}', 1),
(1, 'text', '{"content": "<h2>Benvenuti alla Bologna Marathon 2025</h2><p>La Bologna Marathon è l\'evento podistico più importante della città, che attrae migliaia di runner da tutta Italia e dall\'estero. Un percorso unico che attraversa i luoghi più belli di Bologna, dal centro storico alle colline.</p><p>Quest\'anno abbiamo registrato un record di partecipanti con oltre 15.000 iscritti tra maratona e mezza maratona.</p>"}', 2),
(1, 'results', '{"race_id": 1, "limit": 10, "show_categories": true, "sortable": true}', 3),
(1, 'footer', '{"columns": 4, "social": true}', 4);
