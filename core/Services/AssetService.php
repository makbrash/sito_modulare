<?php
/**
 * Asset Service
 * Gestione collezione asset CSS/JS dei moduli
 */

namespace BolognaMarathon\Services;

use PDO;

class AssetService
{
    private $modulesPath;
    private $cache = [];

    public function __construct($modulesPath = null)
    {
        $this->modulesPath = $modulesPath ?? (defined('MODULES_PATH') ? MODULES_PATH : __DIR__ . '/../../modules/');
    }

    /**
     * Raccoglie asset CSS/JS per moduli di una pagina
     */
    public function collectModuleAssets(array $modules)
    {
        $css = [];
        $js = [];
        $visited = [];

        foreach ($modules as $module) {
            $moduleName = is_array($module) && isset($module['module_name']) 
                ? $module['module_name'] 
                : (is_string($module) ? $module : '');

            if (empty($moduleName) || isset($visited[$moduleName])) {
                continue;
            }

            $visited[$moduleName] = true;

            // Ottieni asset dal manifest
            $manifest = $this->getModuleManifest($moduleName);

            if ($manifest && isset($manifest['assets'])) {
                // CSS dal manifest
                if (!empty($manifest['assets']['css'])) {
                    foreach ($manifest['assets']['css'] as $cssPath) {
                        $normalized = $this->normalizePath($cssPath);
                        if ($normalized && !isset($css[$normalized])) {
                            $css[$normalized] = true;
                        }
                    }
                }

                // JS dal manifest
                if (!empty($manifest['assets']['js'])) {
                    foreach ($manifest['assets']['js'] as $jsPath) {
                        $normalized = $this->normalizePath($jsPath);
                        if ($normalized && !isset($js[$normalized])) {
                            $js[$normalized] = true;
                        }
                    }
                }
            }

            // Fallback: cerca file standard
            $this->addFallbackAssets($moduleName, $css, $js);
        }

        return [
            'css' => array_keys($css),
            'js' => array_keys($js)
        ];
    }

    /**
     * Raccoglie asset vendor esterni
     */
    public function collectVendorAssets(array $modules)
    {
        $css = [];
        $js = [];
        $visited = [];

        foreach ($modules as $module) {
            $moduleName = is_array($module) && isset($module['module_name']) 
                ? $module['module_name'] 
                : (is_string($module) ? $module : '');

            if (empty($moduleName) || isset($visited[$moduleName])) {
                continue;
            }

            $visited[$moduleName] = true;

            $manifest = $this->getModuleManifest($moduleName);

            if ($manifest && isset($manifest['assets']['vendors'])) {
                foreach ($manifest['assets']['vendors'] as $vendor) {
                    if (!empty($vendor['css'])) {
                        foreach ($vendor['css'] as $href) {
                            $css[$href] = true;
                        }
                    }
                    if (!empty($vendor['js'])) {
                        foreach ($vendor['js'] as $src) {
                            $js[$src] = true;
                        }
                    }
                }
            }

            // Gestisci dipendenze
            if ($manifest && !empty($manifest['dependencies'])) {
                $depAssets = $this->collectVendorAssets($manifest['dependencies']);
                foreach ($depAssets['css'] as $href) {
                    $css[$href] = true;
                }
                foreach ($depAssets['js'] as $src) {
                    $js[$src] = true;
                }
            }
        }

        return [
            'css' => array_keys($css),
            'js' => array_keys($js)
        ];
    }

    /**
     * Ottieni manifest di un modulo
     */
    public function getModuleManifest($moduleName)
    {
        if (isset($this->cache['manifests'][$moduleName])) {
            return $this->cache['manifests'][$moduleName];
        }

        $slug = $this->resolveModuleName($moduleName);
        $manifestPath = $this->modulesPath . $slug . '/module.json';

        if (!file_exists($manifestPath)) {
            return null;
        }

        $content = file_get_contents($manifestPath);
        $manifest = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $this->cache['manifests'][$moduleName] = $manifest;
            return $manifest;
        }

        return null;
    }

    /**
     * Risolve nome modulo (gestisce alias)
     */
    protected function resolveModuleName($name)
    {
        // Cerca tra tutti i manifest per alias
        $this->loadAllManifests();

        if (isset($this->cache['aliases'][$name])) {
            return $this->cache['aliases'][$name];
        }

        // Se esiste directory con quel nome
        if (is_dir($this->modulesPath . $name)) {
            return $name;
        }

        return $name;
    }

    /**
     * Carica tutti i manifest per costruire mappa alias
     */
    protected function loadAllManifests()
    {
        if (isset($this->cache['manifests_loaded'])) {
            return;
        }

        $this->cache['aliases'] = [];

        if (!is_dir($this->modulesPath)) {
            return;
        }

        $dirs = scandir($this->modulesPath);

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || $dir === 'docs') {
                continue;
            }

            $moduleDir = $this->modulesPath . $dir;
            if (!is_dir($moduleDir)) {
                continue;
            }

            $manifestPath = $moduleDir . '/module.json';
            if (file_exists($manifestPath)) {
                $content = file_get_contents($manifestPath);
                $manifest = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($manifest)) {
                    $slug = $manifest['slug'] ?? $dir;
                    $this->cache['manifests'][$slug] = $manifest;

                    // Registra alias
                    if (!empty($manifest['aliases'])) {
                        foreach ($manifest['aliases'] as $alias) {
                            $this->cache['aliases'][$alias] = $slug;
                        }
                    }
                }
            }
        }

        $this->cache['manifests_loaded'] = true;
    }

    /**
     * Normalizza path asset
     */
    protected function normalizePath($path)
    {
        // Converti SCSS a CSS se necessario
        if (preg_match('#\.scss$#i', $path)) {
            $path = preg_replace('#/scss/#', '/css/', $path);
            $path = preg_replace('#\.scss$#i', '.css', $path);
        }

        // Verifica che il file esista
        $fullPath = __DIR__ . '/../../' . $path;
        if (!file_exists($fullPath)) {
            return null;
        }

        return $path;
    }

    /**
     * Aggiunge asset fallback se non specificati nel manifest
     */
    protected function addFallbackAssets($moduleName, &$css, &$js)
    {
        // CSS fallback
        $cssPath = "modules/{$moduleName}/{$moduleName}.css";
        $fullCssPath = __DIR__ . '/../../' . $cssPath;
        
        if (file_exists($fullCssPath) && !isset($css[$cssPath])) {
            $css[$cssPath] = true;
        }

        // JS fallback
        $jsPath = "modules/{$moduleName}/{$moduleName}.js";
        $fullJsPath = __DIR__ . '/../../' . $jsPath;
        
        if (file_exists($fullJsPath) && !isset($js[$jsPath])) {
            $js[$jsPath] = true;
        }
    }

    /**
     * Minifica CSS (semplice)
     */
    public function minifyCSS($css)
    {
        // Rimuovi commenti
        $css = preg_replace('!/\*.*?\*/!s', '', $css);
        
        // Rimuovi whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        
        return trim($css);
    }

    /**
     * Minifica JS (semplice)
     */
    public function minifyJS($js)
    {
        // Rimuovi commenti single-line
        $js = preg_replace('#//.*$#m', '', $js);
        
        // Rimuovi commenti multi-line
        $js = preg_replace('#/\*.*?\*/#s', '', $js);
        
        // Rimuovi whitespace eccessivo
        $js = preg_replace('/\s+/', ' ', $js);
        
        return trim($js);
    }

    /**
     * Combina file CSS
     */
    public function combineCSS(array $files)
    {
        $combined = '';

        foreach ($files as $file) {
            $fullPath = __DIR__ . '/../../' . $file;
            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);
                $combined .= "/* {$file} */\n{$content}\n\n";
            }
        }

        return $combined;
    }

    /**
     * Combina file JS
     */
    public function combineJS(array $files)
    {
        $combined = '';

        foreach ($files as $file) {
            $fullPath = __DIR__ . '/../../' . $file;
            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);
                $combined .= "/* {$file} */\n{$content}\n\n";
            }
        }

        return $combined;
    }
}

