-- Installazione modulo Partner
INSERT INTO modules_registry (
    name, 
    component_path, 
    is_active
) VALUES (
    'partner', 
    'partner/partner.php',
    1
) ON DUPLICATE KEY UPDATE
    component_path = VALUES(component_path),
    is_active = VALUES(is_active);

-- Tabella sponsor
CREATE TABLE IF NOT EXISTS sponsors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    category VARCHAR(100) NOT NULL,
    group_type ENUM('main', 'official', 'technical', 'sponsor', 'credits') NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    website_url VARCHAR(500),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_group_type (group_type),
    INDEX idx_active (is_active),
    INDEX idx_sort (sort_order)
);

-- Inserimento sponsor dalle cartelle
INSERT INTO sponsors (name, category, group_type, image_path, sort_order) VALUES
('Pellicon', 'main sponsor', 'main', 'assets/images/sponsor/main sponsor/pellicon.png', 1),
('Hyundai', 'official car', 'official', 'assets/images/sponsor/official car/hyundai.jpg', 2),
('Culligan', 'official whater', 'official', 'assets/images/sponsor/official whater/culligan.png', 3),
('Birrificio Angelo Poretti', 'official beer', 'official', 'assets/images/sponsor/official beer/birra poretti.png', 4),
('Joma', 'thecnical sponsor', 'technical', 'assets/images/sponsor/thecnical sponsor/joma.jpeg', 5),
('CER Medical', 'sponsor', 'sponsor', 'assets/images/sponsor/sponsor/cer_medical.png', 6),
('Dicloreum ICE', 'sponsor', 'sponsor', 'assets/images/sponsor/sponsor/dicloreum.jpeg', 7),
('GYM TO GO', 'sponsor', 'sponsor', 'assets/images/sponsor/sponsor/gym_to_go.png', 8),
('Master-Aid Sport', 'sponsor', 'sponsor', 'assets/images/sponsor/sponsor/master_aid_sport.png', 9),
('NH Hotels & Resorts', 'sponsor', 'sponsor', 'assets/images/sponsor/sponsor/nh_hotels_e_resort.png', 10),
('T>per', 'sponsor', 'sponsor', 'assets/images/sponsor/sponsor/t_per_cambia_il_movimento.png', 11),
('Tigota', 'sponsor', 'sponsor', 'assets/images/sponsor/sponsor/tigota.png', 12),
('Today Conad', 'sponsor', 'sponsor', 'assets/images/sponsor/sponsor/tuday_conad.png', 13),
('Adventure Agency', 'sponsor 2', 'technical', 'assets/images/sponsor/sponsor 2/adventure_agency.jpg', 14),
('Artigianquality', 'sponsor 2', 'technical', 'assets/images/sponsor/sponsor 2/artigianquality.png', 15),
('Associazione Pianificatori Bologna', 'sponsor 2', 'technical', 'assets/images/sponsor/sponsor 2/associacione_pianificatori_bologna_e_provincia.png', 16),
('Dole', 'sponsor 2', 'technical', 'assets/images/sponsor/sponsor 2/dole.png', 17),
('Franzini', 'sponsor 2', 'technical', 'assets/images/sponsor/sponsor 2/franzini.png', 18),
('Twists Products', 'sponsor 2', 'technical', 'assets/images/sponsor/sponsor 2/fruits_production_twists.png', 19),
('Frullà', 'sponsor 2', 'technical', 'assets/images/sponsor/sponsor 2/frulla.png', 20),
('Marazzi', 'sponsor 2', 'technical', 'assets/images/sponsor/sponsor 2/marazzi.jpg', 21),
('Matteiplast', 'sponsor 2', 'technical', 'assets/images/sponsor/sponsor 2/mattei_plast.png', 22),
('Moca', 'sponsor 2', 'technical', 'assets/images/sponsor/sponsor 2/moca.png', 23),
('Parmigiano Reggiano', 'sponsor 2', 'technical', 'assets/images/sponsor/sponsor 2/parmigiano_reggiano.jpeg', 24),
('Sticker Mule', 'sponsor 2', 'technical', 'assets/images/sponsor/sponsor 2/sticker_mule.jpg', 25);
