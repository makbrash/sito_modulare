-- Installation SQL for Splash Logo Module
-- Inserisce il modulo nel registro moduli

INSERT INTO `modules_registry` (
    `name`,
    `component_path`,
    `css_class`,
    `default_config`,
    `is_active`
) VALUES (
    'splash-logo',
    'splash-logo/splash-logo.php',
    'splash-logo',
    JSON_OBJECT(
        'logo_url', 'assets/images/splash.svg',
        'duration', 2500,
        'logo_size', 100,
        'pulse_speed', 2
    ),
    1
) ON DUPLICATE KEY UPDATE
    `component_path` = VALUES(`component_path`),
    `css_class` = VALUES(`css_class`),
    `default_config` = VALUES(`default_config`),
    `is_active` = VALUES(`is_active`);