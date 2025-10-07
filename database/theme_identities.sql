-- Tabella per le identità dei temi
CREATE TABLE IF NOT EXISTS theme_identities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    class_name VARCHAR(50) NOT NULL UNIQUE,
    alias VARCHAR(100) NOT NULL,
    primary_color VARCHAR(7) NOT NULL,
    secondary_color VARCHAR(7) NOT NULL,
    info_color VARCHAR(7) NOT NULL,
    success_color VARCHAR(7) NOT NULL,
    warning_color VARCHAR(7) NOT NULL,
    error_color VARCHAR(7) NOT NULL,
    accent_color VARCHAR(20) NOT NULL,
    gradient_primary TEXT,
    gradient_button TEXT,
    gradient_button_hover TEXT,
    gradient_dark TEXT,
    gradient_hero TEXT,
    gradient_secondary TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserimento temi predefiniti
INSERT INTO theme_identities (name, class_name, alias, primary_color, secondary_color, info_color, success_color, warning_color, error_color, accent_color, gradient_primary, gradient_button, gradient_button_hover, gradient_dark, gradient_hero, gradient_secondary) VALUES
('Marathon', 'race-marathon', 'Maratona', '#23a8eb', '#1583b9', '#5DADE2', '#52bd7b', '#F39C12', '#E74C3C', 'rgb(34 211 238)', 'linear-gradient(45deg, #23a8eb, #1583b9)', 'linear-gradient(135deg, rgb(34 211 238) 0%, #23a8eb 100%)', 'linear-gradient(135deg, #1583b9 0%, #23a8eb 100%)', 'linear-gradient(135deg, #0b0b15 0%, #1a1a2e 100%)', 'linear-gradient(135deg, rgba(11, 11, 21, 0.8) 0%, rgba(26, 26, 46, 0.8) 100%)', 'linear-gradient(135deg, #23a8eb 0%, #1583b9 100%)'),
('30K Portici', 'race-portici', '30K Portici', '#dc335e', '#af1b40', '#e95177', '#52bd7b', '#F39C12', '#E74C3C', 'rgb(34 211 238)', 'linear-gradient(45deg, #dc335e, #af1b40)', 'linear-gradient(135deg, rgb(34 211 238) 0%, #dc335e 100%)', 'linear-gradient(135deg, #af1b40 0%, #dc335e 100%)', 'linear-gradient(135deg, #0b0b15 0%, #1a1a2e 100%)', 'linear-gradient(135deg, rgba(11, 11, 21, 0.8) 0%, rgba(26, 26, 46, 0.8) 100%)', 'linear-gradient(135deg, #dc335e 0%, #af1b40 100%)'),
('Run Tune Up', 'race-run-tune-up', 'Run Tune Up', '#cbdf44', '#a0b229', '#d0d95a', '#52bd7b', '#F39C12', '#E74C3C', 'rgb(34 211 238)', 'linear-gradient(45deg, #cbdf44, #a0b229)', 'linear-gradient(135deg, rgb(34 211 238) 0%, #cbdf44 100%)', 'linear-gradient(135deg, #a0b229 0%, #cbdf44 100%)', 'linear-gradient(135deg, #0b0b15 0%, #1a1a2e 100%)', 'linear-gradient(135deg, rgba(11, 11, 21, 0.8) 0%, rgba(26, 26, 46, 0.8) 100%)', 'linear-gradient(135deg, #cbdf44 0%, #a0b229 100%)'),
('5K', 'race-5k', '5K', '#ff6b35', '#e55a2b', '#ff8c5a', '#52bd7b', '#F39C12', '#E74C3C', 'rgb(34 211 238)', 'linear-gradient(45deg, #ff6b35, #e55a2b)', 'linear-gradient(135deg, rgb(34 211 238) 0%, #ff6b35 100%)', 'linear-gradient(135deg, #e55a2b 0%, #ff6b35 100%)', 'linear-gradient(135deg, #0b0b15 0%, #1a1a2e 100%)', 'linear-gradient(135deg, rgba(11, 11, 21, 0.8) 0%, rgba(26, 26, 46, 0.8) 100%)', 'linear-gradient(135deg, #ff6b35 0%, #e55a2b 100%)'),
('Kids Run', 'race-kidsrun', 'Kids Run', '#007b5f', '#005a47', '#00a67a', '#52bd7b', '#F39C12', '#E74C3C', 'rgb(34 211 238)', 'linear-gradient(45deg, #007b5f, #005a47)', 'linear-gradient(135deg, rgb(34 211 238) 0%, #007b5f 100%)', 'linear-gradient(135deg, #005a47 0%, #007b5f 100%)', 'linear-gradient(135deg, #0b0b15 0%, #1a1a2e 100%)', 'linear-gradient(135deg, rgba(11, 11, 21, 0.8) 0%, rgba(26, 26, 46, 0.8) 100%)', 'linear-gradient(135deg, #007b5f 0%, #005a47 100%)');

-- Aggiungi colonna theme alla tabella pages
ALTER TABLE pages ADD COLUMN theme VARCHAR(50) DEFAULT 'marathon';
