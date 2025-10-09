-- ========================================
-- Countdown Module - Installation SQL
-- ========================================
-- 
-- STRUTTURA TABELLA modules_registry:
-- - id (INT, PK, AUTO_INCREMENT)
-- - name (VARCHAR(100), UNIQUE, NOT NULL)
-- - component_path (VARCHAR(200), NOT NULL)
-- - css_class (VARCHAR(100), NULLABLE)
-- - default_config (JSON, NULLABLE)
-- - is_active (BOOLEAN, DEFAULT TRUE)
-- - created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
-- 
-- ⚠️ ATTENZIONE: Le colonne description, ui_schema, slug, version NON esistono!
-- ========================================

-- Registrazione del modulo nel sistema
INSERT INTO modules_registry (
    name,
    component_path,
    css_class,
    default_config,
    is_active
) VALUES (
    'countdown',
    'countdown/countdown.php',
    'countdown',
    '{
        "variant": "banner",
        "target_date": "2026-03-01T09:00:00",
        "title": "",
        "subtitle": "Mancano solo",
        "logo_1": "assets/images/logo-bologna-marathon.svg",
        "logo_2": "assets/images/logo-bologna-marathon.svg",
        "show_logos": true,
        "theme_class": ""
    }',
    1
) ON DUPLICATE KEY UPDATE
    component_path = VALUES(component_path),
    css_class = VALUES(css_class),
    default_config = VALUES(default_config),
    is_active = VALUES(is_active);

