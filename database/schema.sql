-- Schema Database Bologna Marathon
-- Sistema modulare con supporto AI

-- Tabella pagine
CREATE TABLE pages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    template VARCHAR(50) DEFAULT 'default',
    layout_config JSON,
    css_variables JSON,
    meta_data JSON,
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status)
);

-- Registro moduli disponibili
CREATE TABLE modules_registry (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    component_path VARCHAR(200) NOT NULL,
    css_class VARCHAR(100),
    default_config JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Moduli assegnati alle pagine
CREATE TABLE page_modules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_id INT NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    config JSON,
    order_index INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    INDEX idx_page_order (page_id, order_index),
    INDEX idx_module (module_name)
);

-- Risultati gara
CREATE TABLE race_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    race_id INT NOT NULL,
    position INT NOT NULL,
    bib_number VARCHAR(20),
    runner_name VARCHAR(200) NOT NULL,
    category VARCHAR(50),
    time_result TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_race (race_id),
    INDEX idx_position (race_id, position),
    INDEX idx_category (race_id, category)
);

-- Gare
CREATE TABLE races (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    date DATE NOT NULL,
    distance VARCHAR(50),
    status ENUM('upcoming', 'active', 'completed') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_status (status)
);

-- Contenuti dinamici
CREATE TABLE dynamic_content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    content_type VARCHAR(50) NOT NULL,
    title VARCHAR(200),
    content TEXT,
    metadata JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (content_type),
    INDEX idx_active (is_active)
);

-- Istanze di moduli per page builder
CREATE TABLE module_instances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_id INT NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    instance_name VARCHAR(100) NOT NULL,
    config JSON,
    order_index INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_instance (page_id, instance_name),
    INDEX idx_page_order (page_id, order_index),
    INDEX idx_module (module_name)
);

-- Inserimento moduli base
INSERT INTO modules_registry (name, component_path, css_class, default_config) VALUES
('button', 'button/button.php', 'btn', '{"variant":"primary","size":"medium"}'),
('footer', 'footer/footer.php', 'site-footer', '{"columns":4}'),
('hero', 'hero/hero.php', 'hero-module', '[]'),
('menu', 'menu/menu.php', 'main-menu', '{"style":"horizontal","sticky":true}'),
('race-cards', 'race-cards/race-cards.php', 'race-cards-module', '[]'),
('results', 'results/results.php', 'results-module', '[]'),
('text', 'text/text.php', 'rich-text', '{"wrapper":"article"}');

-- Inserimento pagina home di esempio
INSERT INTO pages (slug, title, description, template, layout_config, css_variables) VALUES
('home', 'Bologna Marathon 2025', 'Sito ufficiale della Bologna Marathon', 'home', 
'{"layout": "fullwidth", "sections": ["hero", "content", "results"]}',
'{}'),
('test', 'Bologna test', 'Pagina di test per il page builder', 'default', 
'{"layout": "default", "sections": []}',
'{}');

-- Inserimento moduli per home
INSERT INTO page_modules (page_id, module_name, config, order_index) VALUES
(1, 'hero', '{"title": "Bologna Marathon 2025", "subtitle": "La corsa più bella d\'Italia", "image": "hero-bg.jpg", "layout": "2col"}', 1),
(1, 'text', '{"content": "Benvenuti alla Bologna Marathon, l\'evento podistico più importante della città..."}', 2),
(1, 'results', '{"race_id": 1, "limit": 20, "show_categories": true}', 3),
(1, 'footer', '{"columns": 4, "social": true}', 4);
