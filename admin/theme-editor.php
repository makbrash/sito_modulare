<?php
/**
 * Theme Editor - Bologna Marathon
 * Gestione temi dinamici per il sistema
 */

require_once '../config/database.php';
require_once '../core/ModuleRenderer.php';

// Inizializza connessione database
$database = new Database();
$db = $database->getConnection();
$renderer = new ModuleRenderer($db);

// Gestione azioni AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'create_theme':
                $name = $_POST['name'];
                $alias = $_POST['alias'];
                $class_name = $_POST['class_name'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $is_default = isset($_POST['is_default']) ? 1 : 0;
                $colors = json_decode($_POST['colors'], true);
                
                // Se questo è il tema di default, rimuovi il flag da tutti gli altri
                if ($is_default) {
                    $stmt = $db->prepare("UPDATE theme_identities SET is_default = 0");
                    $stmt->execute();
                }
                
                // Verifica se esiste la colonna colors (struttura JSON)
                $checkStmt = $db->query("SHOW COLUMNS FROM theme_identities LIKE 'colors'");
                $hasColorsColumn = $checkStmt->rowCount() > 0;
                
                if ($hasColorsColumn) {
                    // Usa la colonna JSON
                    $stmt = $db->prepare("INSERT INTO theme_identities (name, alias, class_name, is_active, is_default, colors) VALUES (?, ?, ?, ?, ?, ?)");
                    $success = $stmt->execute([$name, $alias, $class_name, $is_active, $is_default, json_encode($colors)]);
                } else {
                    // Usa le colonne separate (struttura originale)
                    $stmt = $db->prepare("INSERT INTO theme_identities (name, alias, class_name, is_active, is_default, primary_color, secondary_color, accent_color, info_color, success_color, warning_color, error_color) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $success = $stmt->execute([
                        $name, $alias, $class_name, $is_active, $is_default,
                        $colors['primary'] ?? '#23a8eb',
                        $colors['secondary'] ?? '#1583b9',
                        $colors['accent'] ?? 'rgb(34 211 238)',
                        $colors['info'] ?? '#5DADE2',
                        $colors['success'] ?? '#52bd7b',
                        $colors['warning'] ?? '#F39C12',
                        $colors['error'] ?? '#E74C3C'
                    ]);
                }
                
                if ($success) {
                    // Aggiorna variables.css
                    updateColorsCSS();
                }
                
                echo json_encode(['success' => $success, 'id' => $db->lastInsertId()]);
                exit;
                
            case 'update_theme':
                $id = (int)$_POST['id'];
                $name = $_POST['name'];
                $alias = $_POST['alias'];
                $class_name = $_POST['class_name'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $is_default = isset($_POST['is_default']) ? 1 : 0;
                $colors = json_decode($_POST['colors'], true);
                
                // Se questo è il tema di default, rimuovi il flag da tutti gli altri
                if ($is_default) {
                    $stmt = $db->prepare("UPDATE theme_identities SET is_default = 0");
                    $stmt->execute();
                }
                
                // Verifica se esiste la colonna colors (struttura JSON)
                $checkStmt = $db->query("SHOW COLUMNS FROM theme_identities LIKE 'colors'");
                $hasColorsColumn = $checkStmt->rowCount() > 0;
                
                if ($hasColorsColumn) {
                    // Usa la colonna JSON
                    $stmt = $db->prepare("UPDATE theme_identities SET name = ?, alias = ?, class_name = ?, is_active = ?, is_default = ?, colors = ? WHERE id = ?");
                    $success = $stmt->execute([$name, $alias, $class_name, $is_active, $is_default, json_encode($colors), $id]);
                } else {
                    // Usa le colonne separate (struttura originale)
                    $stmt = $db->prepare("UPDATE theme_identities SET name = ?, alias = ?, class_name = ?, is_active = ?, is_default = ?, primary_color = ?, secondary_color = ?, accent_color = ?, info_color = ?, success_color = ?, warning_color = ?, error_color = ? WHERE id = ?");
                    $success = $stmt->execute([
                        $name, $alias, $class_name, $is_active, $is_default,
                        $colors['primary'] ?? '#23a8eb',
                        $colors['secondary'] ?? '#1583b9',
                        $colors['accent'] ?? 'rgb(34 211 238)',
                        $colors['info'] ?? '#5DADE2',
                        $colors['success'] ?? '#52bd7b',
                        $colors['warning'] ?? '#F39C12',
                        $colors['error'] ?? '#E74C3C',
                        $id
                    ]);
                }
                
                if ($success) {
                    // Aggiorna variables.css
                    updateColorsCSS();
                }
                
                echo json_encode(['success' => $success]);
                exit;
                
            case 'delete_theme':
                $id = (int)$_POST['id'];
                
                $stmt = $db->prepare("DELETE FROM theme_identities WHERE id = ?");
                $success = $stmt->execute([$id]);
                
                echo json_encode(['success' => $success]);
                exit;
                
            case 'toggle_theme':
                $id = (int)$_POST['id'];
                $is_active = (int)$_POST['is_active'];
                
                $stmt = $db->prepare("UPDATE theme_identities SET is_active = ? WHERE id = ?");
                $success = $stmt->execute([$is_active, $id]);
                
                if ($success) {
                    updateColorsCSS();
                }
                
                echo json_encode(['success' => $success]);
                exit;
                
            case 'set_default_theme':
                $id = (int)$_POST['id'];
                
                // Rimuovi il flag da tutti i temi
                $stmt = $db->prepare("UPDATE theme_identities SET is_default = 0");
                $stmt->execute();
                
                // Imposta il nuovo tema di default
                $stmt = $db->prepare("UPDATE theme_identities SET is_default = 1 WHERE id = ?");
                $success = $stmt->execute([$id]);
                
                if ($success) {
                    updateColorsCSS();
                }
                
                echo json_encode(['success' => $success]);
                exit;
                
            case 'update_colors':
                $id = (int)$_POST['id'];
                $colors = json_decode($_POST['colors'], true);
                
                // Verifica se esiste la colonna colors (struttura JSON)
                $checkStmt = $db->query("SHOW COLUMNS FROM theme_identities LIKE 'colors'");
                $hasColorsColumn = $checkStmt->rowCount() > 0;
                
                if ($hasColorsColumn) {
                    // Usa la colonna JSON
                    $stmt = $db->prepare("UPDATE theme_identities SET colors = ? WHERE id = ?");
                    $success = $stmt->execute([json_encode($colors), $id]);
                } else {
                    // Usa le colonne separate (struttura originale)
                    $stmt = $db->prepare("UPDATE theme_identities SET 
                        primary_color = ?, 
                        secondary_color = ?, 
                        accent_color = ?, 
                        info_color = ?, 
                        success_color = ?, 
                        warning_color = ?, 
                        error_color = ? 
                        WHERE id = ?");
                    $success = $stmt->execute([
                        $colors['primary'] ?? '#23a8eb',
                        $colors['secondary'] ?? '#1583b9',
                        $colors['accent'] ?? 'rgb(34 211 238)',
                        $colors['info'] ?? '#5DADE2',
                        $colors['success'] ?? '#52bd7b',
                        $colors['warning'] ?? '#F39C12',
                        $colors['error'] ?? '#E74C3C',
                        $id
                    ]);
                }
                
                if ($success) {
                    updateColorsCSS();
                }
                
                echo json_encode(['success' => $success]);
                exit;
                
            case 'get_theme_colors':
                $id = (int)$_POST['id'];
                
                $stmt = $db->prepare("SELECT * FROM theme_identities WHERE id = ?");
                $stmt->execute([$id]);
                $theme = $stmt->fetch();
                
                if ($theme) {
                    $colors = getThemeColors($theme);
                    echo json_encode(['success' => true, 'colors' => $colors]);
                } else {
                    // Restituisci colori di default
                    $defaultColors = getDefaultColors();
                    echo json_encode(['success' => true, 'colors' => $defaultColors]);
                }
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Funzione per aggiornare colors.css
function updateColorsCSS() {
    global $db;
    
    // Ottieni tutti i temi attivi
    $themes = $db->query("SELECT * FROM theme_identities WHERE is_active = 1 ORDER BY is_default DESC, name")->fetchAll();
    
    $css = "/* ========================================\n";
    $css .= "   CSS COLORS SYSTEM - BOLOGNA MARATHON\n";
    $css .= "   ======================================== */\n\n";
    
    // Tema di default (primo nella lista)
    if (!empty($themes)) {
        $defaultTheme = $themes[0];
        $colors = getThemeColors($defaultTheme);
        
        $css .= "/* Tema Base - " . $defaultTheme['name'] . " (Default) */\n";
        $css .= ":root {\n";
        $css .= generateColorsCSS($colors);
        $css .= "}\n\n";
    }
    
    // Altri temi
    $css .= "/* ========================================\n";
    $css .= "   TEMI DINAMICI - GENERATI DA DATABASE\n";
    $css .= "   ======================================== */\n\n";
    
    foreach ($themes as $index => $theme) {
        if ($index === 0) continue; // Salta il tema di default già processato
        
        $colors = getThemeColors($theme);
        
        $css .= "/* Tema " . $theme['name'] . " */\n";
        $css .= "." . $theme['class_name'] . " {\n";
        $css .= generateColorsCSS($colors);
        $css .= "}\n\n";
    }
    
    // Override per sezioni specifiche
    $css .= "/* ========================================\n";
    $css .= "   OVERRIDE TEMI - PER SEZIONI SPECIFICHE\n";
    $css .= "   ======================================== */\n\n";
    
    foreach ($themes as $theme) {
        $colors = getThemeColors($theme);
        
        // Genera sia .theme-* che .theme-race-* per compatibilità
        $baseOverrideClass = str_replace('race-', 'theme-', $theme['class_name']);
        $raceOverrideClass = 'theme-' . $theme['class_name']; // theme-race-*
        
        // Classe base (es. .theme-kidsrun)
        $css .= "/* Override per sezione " . $theme['name'] . " */\n";
        $css .= "." . $baseOverrideClass . ",\n";
        $css .= "." . $raceOverrideClass . " {\n";
        $css .= generateColorsCSS($colors);
        $css .= "}\n\n";
    }
    
    // Salva il file
    file_put_contents(__DIR__ . '/../assets/css/core/colors.css', $css);
}

// Funzione per ottenere i colori di un tema (compatibile con entrambe le strutture DB)
function getThemeColors($theme) {
    // Se esiste la colonna colors (JSON), usala
    if (isset($theme['colors']) && !empty($theme['colors'])) {
        $colors = json_decode($theme['colors'], true);
        if ($colors) {
            return $colors;
        }
    }
    
    // Altrimenti usa le colonne separate (struttura originale)
    return [
        'primary' => $theme['primary_color'] ?? '#23a8eb',
        'secondary' => $theme['secondary_color'] ?? '#1583b9',
        'accent' => $theme['accent_color'] ?? 'rgb(34 211 238)',
        'info' => $theme['info_color'] ?? '#5DADE2',
        'success' => $theme['success_color'] ?? '#52bd7b',
        'warning' => $theme['warning_color'] ?? '#F39C12',
        'error' => $theme['error_color'] ?? '#E74C3C',
        'countdown_color' => '#00ffff' // Non presente nella struttura originale
    ];
}

// Funzione per generare CSS dei colori
function generateColorsCSS($colors) {
    $css = "";
    
    // Colori principali
    $css .= "    --primary: " . ($colors['primary'] ?? '#23a8eb') . ";\n";
    $css .= "    --secondary: " . ($colors['secondary'] ?? '#1583b9') . ";\n";
    $css .= "    --accent: " . ($colors['accent'] ?? 'rgb(34 211 238)') . ";\n";
    $css .= "    --info: " . ($colors['info'] ?? '#5DADE2') . ";\n";
    $css .= "    --success: " . ($colors['success'] ?? '#52bd7b') . ";\n";
    $css .= "    --warning: " . ($colors['warning'] ?? '#F39C12') . ";\n";
    $css .= "    --error: " . ($colors['error'] ?? '#E74C3C') . ";\n";
    $css .= "    --countdown-color: " . ($colors['countdown_color'] ?? '#00ffff') . ";\n\n";
    
    // RGB values
    $primaryRgb = hexToRgb($colors['primary'] ?? '#23a8eb');
    $secondaryRgb = hexToRgb($colors['secondary'] ?? '#1583b9');
    $accentRgb = hexToRgb($colors['accent'] ?? '#22d3ee');
    
    $css .= "    /* RGB values for rgba() functions */\n";
    $css .= "    --primary-rgb: " . $primaryRgb . ";\n";
    $css .= "    --secondary-rgb: " . $secondaryRgb . ";\n";
    $css .= "    --accent-rgb: " . $accentRgb . ";\n\n";
    
    // Gradients
    $css .= "    /* Gradients */\n";
    $css .= "    --gradient-primary: linear-gradient(45deg, var(--primary), var(--secondary));\n";
    $css .= "    --gradient-button: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);\n";
    $css .= "    --gradient-button-hover: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);\n";
    $css .= "    --gradient-secondary: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);\n\n";
    
    // Typography - Strong/Bold sui titoli
    $css .= "    /* Typography - Strong/Bold sui titoli */\n";
    $css .= "    --title-strong-color: var(--primary);\n";
    $css .= "    --title-bold-color: var(--primary);\n";
    
    return $css;
}

// Funzione per convertire hex a RGB
function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "$r, $g, $b";
}

// Funzione per ottenere colori di default
function getDefaultColors() {
    return [
        'primary' => '#23a8eb',
        'secondary' => '#1583b9',
        'accent' => '#22d3ee',
        'info' => '#5DADE2',
        'success' => '#52bd7b',
        'warning' => '#F39C12',
        'error' => '#E74C3C',
        'countdown_color' => '#00ffff'
    ];
}

// Ottieni tutti i temi
$themes = $db->query("SELECT * FROM theme_identities ORDER BY is_default DESC, name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Editor - Bologna Marathon</title>
    
    <!-- CSS Core -->
    <link rel="stylesheet" href="../assets/css/core/variables.css">
    <link rel="stylesheet" href="../assets/css/core/reset.css">
    <link rel="stylesheet" href="../assets/css/core/typography.css">
    <link rel="stylesheet" href="../assets/css/core/fonts.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: var(--font-primary, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif);
            background: #f8f9fa;
            margin: 0;
            padding: 2rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .header h1 {
            color: var(--primary);
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .header p {
            color: #6c757d;
            margin: 0;
            font-size: 1.1rem;
        }
        
        .actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .themes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .theme-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .theme-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        .theme-card.active {
            border-color: var(--primary);
        }
        
        .theme-card.inactive {
            opacity: 0.6;
            border-color: #dee2e6;
        }
        
        .theme-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .theme-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }
        
        .theme-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .theme-status.active {
            background: #d4edda;
            color: #155724;
        }
        
        .theme-status.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .theme-info {
            margin-bottom: 1.5rem;
        }
        
        .theme-info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .theme-info-label {
            font-weight: 600;
            color: #495057;
        }
        
        .theme-info-value {
            color: #6c757d;
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .theme-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-small {
            padding: 0.4rem 0.6rem;
            font-size: 0.8rem;
            border-radius: 6px;
            min-width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-small i {
            font-size: 0.9rem;
        }
        
        .btn-small span {
            display: none;
        }
        
        .btn-small:hover {
            transform: scale(1.1);
        }
        
        .btn-small[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            white-space: nowrap;
            z-index: 1000;
        }
        
        .btn-edit {
            background: #28a745;
            color: white;
        }
        
        .btn-edit:hover {
            background: #218838;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .btn-toggle {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-toggle:hover {
            background: #e0a800;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6c757d;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .modal-close:hover {
            background: #f8f9fa;
            color: #495057;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #495057;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(35,168,235,0.1);
        }
        
        .form-group .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: normal;
        }
        
        .form-group .checkbox-label input {
            width: auto;
            margin: 0;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Editor Colori */
        .color-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .color-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .color-group label {
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
        }
        
        .color-group input[type="color"] {
            width: 100%;
            height: 40px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .color-group input[type="text"] {
            padding: 0.5rem;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        .color-group input[type="text"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(35,168,235,0.1);
        }
        
        .color-preview {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .color-preview h4 {
            margin: 0 0 1rem 0;
            color: #495057;
        }
        
        .preview-colors {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .preview-color {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-palette"></i> Theme Editor</h1>
            <p>Gestisci i temi dinamici per le pagine del sito</p>
        </div>
        
        <div class="actions">
            <button class="btn btn-primary" onclick="openModal('create')">
                <i class="fas fa-plus"></i> Nuovo Tema
            </button>
            <a href="page-builder.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Torna al Page Builder
            </a>
        </div>
        
        <div id="alert-container"></div>
        
        <div class="themes-grid">
            <?php if (empty($themes)): ?>
                <div class="empty-state">
                    <i class="fas fa-palette"></i>
                    <h3>Nessun tema disponibile</h3>
                    <p>Crea il tuo primo tema per iniziare</p>
                </div>
            <?php else: ?>
                <?php foreach ($themes as $theme): ?>
                    <div class="theme-card <?= $theme['is_active'] ? 'active' : 'inactive' ?>" data-theme-id="<?= $theme['id'] ?>">
                        <div class="theme-header">
                            <h3 class="theme-name"><?= htmlspecialchars($theme['name']) ?></h3>
                            <span class="theme-status <?= $theme['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $theme['is_active'] ? 'Attivo' : 'Inattivo' ?>
                            </span>
                        </div>
                        
                        <div class="theme-info">
                            <div class="theme-info-item">
                                <span class="theme-info-label">Alias:</span>
                                <span class="theme-info-value"><?= htmlspecialchars($theme['alias']) ?></span>
                            </div>
                            <div class="theme-info-item">
                                <span class="theme-info-label">Classe CSS:</span>
                                <span class="theme-info-value"><?= htmlspecialchars($theme['class_name']) ?></span>
                            </div>
                            <?php if ($theme['is_default']): ?>
                            <div class="theme-info-item">
                                <span class="theme-info-label">Tema Principale:</span>
                                <span class="theme-info-value" style="color: #28a745; font-weight: bold;">✓ SÌ</span>
                            </div>
                            <?php endif; ?>
                            <div class="theme-info-item">
                                <span class="theme-info-label">Creato:</span>
                                <span class="theme-info-value"><?= date('d/m/Y H:i', strtotime($theme['created_at'])) ?></span>
                            </div>
                        </div>
                        
                        <div class="theme-actions">
                            <button class="btn btn-small btn-edit" onclick="openModal('edit', <?= $theme['id'] ?>)" title="Modifica">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-small btn-edit" onclick="openColorEditor(<?= $theme['id'] ?>)" title="Colori">
                                <i class="fas fa-palette"></i>
                            </button>
                            <?php if (!$theme['is_default']): ?>
                            <button class="btn btn-small btn-primary" onclick="setDefaultTheme(<?= $theme['id'] ?>)" title="Imposta come Principale">
                                <i class="fas fa-star"></i>
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-small btn-toggle" onclick="toggleTheme(<?= $theme['id'] ?>, <?= $theme['is_active'] ? 0 : 1 ?>)" title="<?= $theme['is_active'] ? 'Disattiva' : 'Attiva' ?>">
                                <i class="fas fa-<?= $theme['is_active'] ? 'pause' : 'play' ?>"></i>
                            </button>
                            <button class="btn btn-small btn-delete" onclick="deleteTheme(<?= $theme['id'] ?>)" title="Elimina">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal -->
    <div class="modal" id="theme-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-title">Nuovo Tema</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <form id="theme-form">
                <input type="hidden" id="theme-id" name="id">
                
                <div class="form-group">
                    <label for="theme-name">Nome Tema *</label>
                    <input type="text" id="theme-name" name="name" required placeholder="es. Marathon, 30K Portici">
                </div>
                
                <div class="form-group">
                    <label for="theme-alias">Alias *</label>
                    <input type="text" id="theme-alias" name="alias" required placeholder="es. Maratona, 30K Portici">
                </div>
                
                <div class="form-group">
                    <label for="theme-class">Classe CSS *</label>
                    <input type="text" id="theme-class" name="class_name" required placeholder="es. race-marathon, race-portici">
                    <small style="color: #6c757d; font-size: 0.9rem; margin-top: 0.25rem; display: block;">
                        La classe verrà applicata al body della pagina
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="theme-active" name="is_active" checked>
                        Tema attivo
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="theme-default" name="is_default">
                        Tema principale (default)
                    </label>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Editor Colori -->
    <div class="modal" id="color-modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title" id="color-modal-title">Editor Colori</h3>
                <button class="modal-close" onclick="closeColorModal()">&times;</button>
            </div>
            
            <div id="color-editor-content">
                <div class="color-grid">
                    <div class="color-group">
                        <label>Colore Principale</label>
                        <input type="color" id="color-primary" value="#23a8eb">
                        <input type="text" id="color-primary-hex" value="#23a8eb" placeholder="#23a8eb">
                    </div>
                    
                    <div class="color-group">
                        <label>Colore Secondario</label>
                        <input type="color" id="color-secondary" value="#1583b9">
                        <input type="text" id="color-secondary-hex" value="#1583b9" placeholder="#1583b9">
                    </div>
                    
                    <div class="color-group">
                        <label>Colore Accent</label>
                        <input type="color" id="color-accent" value="#22d3ee">
                        <input type="text" id="color-accent-hex" value="#22d3ee" placeholder="#22d3ee">
                    </div>
                    
                    <div class="color-group">
                        <label>Colore Info</label>
                        <input type="color" id="color-info" value="#5DADE2">
                        <input type="text" id="color-info-hex" value="#5DADE2" placeholder="#5DADE2">
                    </div>
                    
                    <div class="color-group">
                        <label>Colore Success</label>
                        <input type="color" id="color-success" value="#52bd7b">
                        <input type="text" id="color-success-hex" value="#52bd7b" placeholder="#52bd7b">
                    </div>
                    
                    <div class="color-group">
                        <label>Colore Warning</label>
                        <input type="color" id="color-warning" value="#F39C12">
                        <input type="text" id="color-warning-hex" value="#F39C12" placeholder="#F39C12">
                    </div>
                    
                    <div class="color-group">
                        <label>Colore Error</label>
                        <input type="color" id="color-error" value="#E74C3C">
                        <input type="text" id="color-error-hex" value="#E74C3C" placeholder="#E74C3C">
                    </div>
                    
                    <div class="color-group">
                        <label>Colore Countdown</label>
                        <input type="color" id="color-countdown" value="#00ffff">
                        <input type="text" id="color-countdown-hex" value="#00ffff" placeholder="#00ffff">
                    </div>
                </div>
                
                <div class="color-preview">
                    <h4>Anteprima Colori</h4>
                    <div class="preview-colors">
                        <div class="preview-color" style="background: var(--primary);"></div>
                        <div class="preview-color" style="background: var(--secondary);"></div>
                        <div class="preview-color" style="background: var(--accent);"></div>
                        <div class="preview-color" style="background: var(--info);"></div>
                        <div class="preview-color" style="background: var(--success);"></div>
                        <div class="preview-color" style="background: var(--warning);"></div>
                        <div class="preview-color" style="background: var(--error);"></div>
                        <div class="preview-color" style="background: var(--countdown-color);"></div>
                    </div>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeColorModal()">Annulla</button>
                <button type="button" class="btn btn-primary" onclick="saveColors()">
                    <i class="fas fa-save"></i> Salva Colori
                </button>
            </div>
        </div>
    </div>
    
    <script>
        let currentAction = '';
        let currentThemeId = null;
        
        // Apri modal
        function openModal(action, themeId = null) {
            currentAction = action;
            currentThemeId = themeId;
            
            const modal = document.getElementById('theme-modal');
            const title = document.getElementById('modal-title');
            const form = document.getElementById('theme-form');
            
            // Reset form
            form.reset();
            
            if (action === 'create') {
                title.textContent = 'Nuovo Tema';
                document.getElementById('theme-active').checked = true;
            } else if (action === 'edit' && themeId) {
                title.textContent = 'Modifica Tema';
                loadThemeData(themeId);
            }
            
            modal.classList.add('show');
        }
        
        // Chiudi modal
        function closeModal() {
            document.getElementById('theme-modal').classList.remove('show');
        }
        
        // Carica dati tema
        function loadThemeData(themeId) {
            // In una implementazione reale, faresti una chiamata AJAX
            // Per ora, simuliamo con i dati esistenti
            const themeCard = document.querySelector(`[data-theme-id="${themeId}"]`);
            if (themeCard) {
                const name = themeCard.querySelector('.theme-name').textContent;
                const alias = themeCard.querySelector('.theme-info-item:nth-child(1) .theme-info-value').textContent;
                const class_name = themeCard.querySelector('.theme-info-item:nth-child(2) .theme-info-value').textContent;
                const is_active = themeCard.classList.contains('active');
                const is_default = themeCard.querySelector('.theme-info-item:nth-child(3)') !== null;
                
                document.getElementById('theme-id').value = themeId;
                document.getElementById('theme-name').value = name;
                document.getElementById('theme-alias').value = alias;
                document.getElementById('theme-class').value = class_name;
                document.getElementById('theme-active').checked = is_active;
                document.getElementById('theme-default').checked = is_default;
            }
        }
        
        // Gestisci submit form
        document.getElementById('theme-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = currentAction === 'create' ? 'create_theme' : 'update_theme';
            
            // Aggiungi colori di default
            const defaultColors = {
                primary: '#23a8eb',
                secondary: '#1583b9',
                accent: '#22d3ee',
                info: '#5DADE2',
                success: '#52bd7b',
                warning: '#F39C12',
                error: '#E74C3C',
                countdown_color: '#00ffff'
            };
            
            formData.append('action', action);
            formData.append('colors', JSON.stringify(defaultColors));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Tema salvato con successo!');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showAlert('error', 'Errore: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                showAlert('error', 'Errore durante il salvataggio: ' + error.message);
            });
        });
        
        // Toggle tema
        function toggleTheme(themeId, newStatus) {
            const formData = new FormData();
            formData.append('action', 'toggle_theme');
            formData.append('id', themeId);
            formData.append('is_active', newStatus);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Stato tema aggiornato!');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showAlert('error', 'Errore: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                showAlert('error', 'Errore durante l\'aggiornamento: ' + error.message);
            });
        }
        
        // Elimina tema
        function deleteTheme(themeId) {
            if (!confirm('Sei sicuro di voler eliminare questo tema?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_theme');
            formData.append('id', themeId);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Tema eliminato con successo!');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showAlert('error', 'Errore: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                showAlert('error', 'Errore durante l\'eliminazione: ' + error.message);
            });
        }
        
        // Mostra alert
        function showAlert(type, message) {
            const container = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i> ${message}`;
            
            container.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
        
        // Chiudi modal cliccando fuori
        document.getElementById('theme-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Editor Colori
        let currentColorThemeId = null;
        
        function openColorEditor(themeId) {
            currentColorThemeId = themeId;
            
            // Carica i colori del tema
            const themeCard = document.querySelector(`[data-theme-id="${themeId}"]`);
            if (themeCard) {
                const themeName = themeCard.querySelector('.theme-name').textContent;
                document.getElementById('color-modal-title').textContent = `Editor Colori - ${themeName}`;
            }
            
            // Carica colori esistenti (per ora usa default)
            loadThemeColors(themeId);
            
            document.getElementById('color-modal').classList.add('show');
        }
        
        function closeColorModal() {
            document.getElementById('color-modal').classList.remove('show');
        }
        
        function loadThemeColors(themeId) {
            // Carica i colori reali dal database
            const formData = new FormData();
            formData.append('action', 'get_theme_colors');
            formData.append('id', themeId);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.colors) {
                    // Usa i colori dal database
                    Object.keys(data.colors).forEach(colorName => {
                        const colorInput = document.getElementById(`color-${colorName}`);
                        const hexInput = document.getElementById(`color-${colorName}-hex`);
                        
                        if (colorInput && hexInput) {
                            colorInput.value = data.colors[colorName];
                            hexInput.value = data.colors[colorName];
                        }
                    });
                } else {
                    // Fallback ai colori di default
                    const defaultColors = {
                        primary: '#23a8eb',
                        secondary: '#1583b9',
                        accent: '#22d3ee',
                        info: '#5DADE2',
                        success: '#52bd7b',
                        warning: '#F39C12',
                        error: '#E74C3C',
                        countdown_color: '#00ffff'
                    };
                    
                    Object.keys(defaultColors).forEach(colorName => {
                        const colorInput = document.getElementById(`color-${colorName}`);
                        const hexInput = document.getElementById(`color-${colorName}-hex`);
                        
                        if (colorInput && hexInput) {
                            colorInput.value = defaultColors[colorName];
                            hexInput.value = defaultColors[colorName];
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Errore caricamento colori:', error);
                // Fallback ai colori di default
                const defaultColors = {
                    primary: '#23a8eb',
                    secondary: '#1583b9',
                    accent: '#22d3ee',
                    info: '#5DADE2',
                    success: '#52bd7b',
                    warning: '#F39C12',
                    error: '#E74C3C',
                    countdown_color: '#00ffff'
                };
                
                Object.keys(defaultColors).forEach(colorName => {
                    const colorInput = document.getElementById(`color-${colorName}`);
                    const hexInput = document.getElementById(`color-${colorName}-hex`);
                    
                    if (colorInput && hexInput) {
                        colorInput.value = defaultColors[colorName];
                        hexInput.value = defaultColors[colorName];
                    }
                });
            });
        }
        
        function saveColors() {
            if (!currentColorThemeId) return;
            
            const colors = {
                primary: document.getElementById('color-primary').value,
                secondary: document.getElementById('color-secondary').value,
                accent: document.getElementById('color-accent').value,
                info: document.getElementById('color-info').value,
                success: document.getElementById('color-success').value,
                warning: document.getElementById('color-warning').value,
                error: document.getElementById('color-error').value,
                countdown_color: document.getElementById('color-countdown').value
            };
            
            const formData = new FormData();
            formData.append('action', 'update_colors');
            formData.append('id', currentColorThemeId);
            formData.append('colors', JSON.stringify(colors));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Colori salvati con successo!');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showAlert('error', 'Errore: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                showAlert('error', 'Errore durante il salvataggio: ' + error.message);
            });
        }
        
        function setDefaultTheme(themeId) {
            if (!confirm('Sei sicuro di voler impostare questo tema come principale?')) return;
            
            const formData = new FormData();
            formData.append('action', 'set_default_theme');
            formData.append('id', themeId);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Tema principale aggiornato!');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showAlert('error', 'Errore: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                showAlert('error', 'Errore durante l\'aggiornamento: ' + error.message);
            });
        }
        
        // Sincronizza color picker con input text
        document.addEventListener('DOMContentLoaded', function() {
            const colorInputs = document.querySelectorAll('input[type="color"]');
            colorInputs.forEach(input => {
                const hexInput = document.getElementById(input.id + '-hex');
                if (hexInput) {
                    input.addEventListener('change', function() {
                        hexInput.value = this.value;
                    });
                    
                    hexInput.addEventListener('input', function() {
                        if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
                            input.value = this.value;
                        }
                    });
                }
            });
        });
        
        // Chiudi modal colori cliccando fuori
        document.getElementById('color-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeColorModal();
            }
        });
    </script>
</body>
</html>