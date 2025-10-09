<?php
/**
 * Theme Service
 * Gestione logica business per i temi
 */

namespace BolognaMarathon\Services;

use PDO;
use Exception;

class ThemeService
{
    private $db;
    private $colorsFile;

    public function __construct(PDO $db, $colorsFile = null)
    {
        $this->db = $db;
        $this->colorsFile = $colorsFile ?? __DIR__ . '/../../assets/css/core/colors.css';
    }

    /**
     * Ottieni tutti i temi
     */
    public function getThemes($activeOnly = false)
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        $sql = "SELECT * FROM theme_identities {$where} ORDER BY name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Ottieni tema per ID
     */
    public function getThemeById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM theme_identities WHERE id = ?");
        $stmt->execute([$id]);
        $theme = $stmt->fetch();

        if (!$theme) {
            throw new Exception("Tema non trovato");
        }

        return $theme;
    }

    /**
     * Ottieni tema per alias
     */
    public function getThemeByAlias($alias)
    {
        $stmt = $this->db->prepare("SELECT * FROM theme_identities WHERE alias = ?");
        $stmt->execute([$alias]);
        return $stmt->fetch();
    }

    /**
     * Crea nuovo tema
     */
    public function createTheme($data)
    {
        $this->validateThemeData($data);

        // Verifica alias univoco
        if ($this->getThemeByAlias($data['alias'])) {
            throw new Exception("Alias già esistente");
        }

        // Se questo è default, rimuovi flag da altri
        if (!empty($data['is_default'])) {
            $this->clearDefaultTheme();
        }

        $colors = $this->prepareColors($data);

        $sql = "INSERT INTO theme_identities 
                (name, alias, class_name, is_active, is_default, primary_color, secondary_color, accent_color, info_color, success_color, warning_color, error_color) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            $data['name'],
            $data['alias'],
            $data['class_name'] ?? 'race-' . $data['alias'],
            $data['is_active'] ?? true,
            $data['is_default'] ?? false,
            $colors['primary'],
            $colors['secondary'],
            $colors['accent'],
            $colors['info'],
            $colors['success'],
            $colors['warning'],
            $colors['error']
        ]);

        if (!$success) {
            throw new Exception("Errore creazione tema");
        }

        $theme = $this->getThemeById($this->db->lastInsertId());

        // Rigenera CSS se necessario
        if (!empty($data['regenerate_css'])) {
            $this->generateThemeCSS();
        }

        return $theme;
    }

    /**
     * Aggiorna tema esistente
     */
    public function updateTheme($id, $data)
    {
        $theme = $this->getThemeById($id);

        // Se questo diventa default, rimuovi flag da altri
        if (!empty($data['is_default'])) {
            $this->clearDefaultTheme();
        }

        $updates = [];
        $params = [];

        $simpleFields = ['name', 'alias', 'class_name', 'is_active', 'is_default'];
        foreach ($simpleFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        $colorFields = ['primary_color', 'secondary_color', 'accent_color', 'info_color', 'success_color', 'warning_color', 'error_color'];
        foreach ($colorFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updates)) {
            return $theme;
        }

        $params[] = $id;
        $sql = "UPDATE theme_identities SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // Rigenera CSS se necessario
        if (!empty($data['regenerate_css'])) {
            $this->generateThemeCSS();
        }

        return $this->getThemeById($id);
    }

    /**
     * Elimina tema
     */
    public function deleteTheme($id)
    {
        $theme = $this->getThemeById($id);

        // Non eliminare tema di default
        if ($theme['is_default']) {
            throw new Exception("Impossibile eliminare il tema di default");
        }

        $stmt = $this->db->prepare("DELETE FROM theme_identities WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Applica tema a pagina
     */
    public function applyThemeToPage($pageId, $themeAlias)
    {
        $theme = $this->getThemeByAlias($themeAlias);
        if (!$theme) {
            throw new Exception("Tema non trovato");
        }

        $sql = "UPDATE pages SET theme = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$themeAlias, $pageId]);
    }

    /**
     * Genera CSS temi
     */
    public function generateThemeCSS()
    {
        $themes = $this->getThemes(true);

        $css = "/**\n * Theme Colors - Auto-generated\n * Do not edit manually\n */\n\n";

        foreach ($themes as $theme) {
            $className = $theme['class_name'];
            
            $css .= "/* {$theme['name']} */\n";
            $css .= "html.{$className},\n";
            $css .= ".{$className} {\n";
            $css .= "    --color-primary: {$theme['primary_color']};\n";
            $css .= "    --color-secondary: {$theme['secondary_color']};\n";
            $css .= "    --color-accent: {$theme['accent_color']};\n";
            $css .= "    --color-info: {$theme['info_color']};\n";
            $css .= "    --color-success: {$theme['success_color']};\n";
            $css .= "    --color-warning: {$theme['warning_color']};\n";
            $css .= "    --color-error: {$theme['error_color']};\n";
            $css .= "}\n\n";
        }

        // Scrivi file CSS
        $result = file_put_contents($this->colorsFile, $css);

        if ($result === false) {
            throw new Exception("Impossibile scrivere file CSS");
        }

        return true;
    }

    /**
     * Ottieni tema di default
     */
    public function getDefaultTheme()
    {
        $stmt = $this->db->prepare("SELECT * FROM theme_identities WHERE is_default = 1 LIMIT 1");
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Rimuovi flag default da tutti i temi
     */
    protected function clearDefaultTheme()
    {
        $stmt = $this->db->prepare("UPDATE theme_identities SET is_default = 0");
        return $stmt->execute();
    }

    /**
     * Prepara colori con valori di default
     */
    protected function prepareColors($data)
    {
        return [
            'primary' => $data['primary_color'] ?? '#23a8eb',
            'secondary' => $data['secondary_color'] ?? '#1583b9',
            'accent' => $data['accent_color'] ?? 'rgb(34 211 238)',
            'info' => $data['info_color'] ?? '#5DADE2',
            'success' => $data['success_color'] ?? '#52bd7b',
            'warning' => $data['warning_color'] ?? '#F39C12',
            'error' => $data['error_color'] ?? '#E74C3C'
        ];
    }

    /**
     * Valida dati tema
     */
    protected function validateThemeData($data)
    {
        if (empty($data['name'])) {
            throw new Exception("Nome tema obbligatorio");
        }

        if (empty($data['alias'])) {
            throw new Exception("Alias tema obbligatorio");
        }

        // Valida formato alias
        if (!preg_match('/^[a-z0-9-]+$/', $data['alias'])) {
            throw new Exception("Alias non valido (solo lettere minuscole, numeri e trattini)");
        }

        return true;
    }

    /**
     * Esporta tema in JSON
     */
    public function exportTheme($id)
    {
        $theme = $this->getThemeById($id);
        
        // Rimuovi campi non esportabili
        unset($theme['id'], $theme['created_at'], $theme['updated_at']);

        return json_encode($theme, JSON_PRETTY_PRINT);
    }

    /**
     * Importa tema da JSON
     */
    public function importTheme($json)
    {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON non valido");
        }

        // Genera alias univoco se necessario
        if ($this->getThemeByAlias($data['alias'])) {
            $baseAlias = $data['alias'];
            $counter = 1;
            
            while ($this->getThemeByAlias($data['alias'])) {
                $data['alias'] = $baseAlias . '-' . $counter;
                $counter++;
            }
        }

        return $this->createTheme($data);
    }
}

