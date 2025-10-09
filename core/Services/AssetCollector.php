<?php

namespace BolognaMarathon\Services;

use PDO;

/**
 * Asset Collection Service
 * Gestisce la raccolta di CSS/JS assets per moduli e pagine
 */
class AssetCollector
{
    private $modulesPath;
    private $cache = [];

    public function __construct(string $modulesPath)
    {
        $this->modulesPath = $modulesPath;
    }

    /**
     * Carica e indicizza i manifest dei moduli (module.json)
     */
    private function loadModuleManifests(): void
    {
        if (isset($this->cache['manifests_loaded']) && $this->cache['manifests_loaded'] === true) {
            return;
        }

        $this->cache['manifests'] = [];
        $this->cache['alias_to_slug'] = [];

        if (!is_dir($this->modulesPath)) {
            $this->cache['manifests_loaded'] = true;
            return;
        }

        $dirs = scandir($this->modulesPath);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $moduleDir = $this->modulesPath . $dir;
            if (!is_dir($moduleDir)) {
                continue;
            }

            $manifestPath = $moduleDir . '/module.json';
            if (file_exists($manifestPath)) {
                $content = file_get_contents($manifestPath);
                $json = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    $slug = $json['slug'] ?? $dir;
                    $this->cache['manifests'][$slug] = $json;

                    // Mappa alias -> slug
                    if (isset($json['aliases']) && is_array($json['aliases'])) {
                        foreach ($json['aliases'] as $alias) {
                            $this->cache['alias_to_slug'][$alias] = $slug;
                        }
                    }
                }
            }
        }

        $this->cache['manifests_loaded'] = true;
    }

    /**
     * Risolve un nome modulo (alias o slug) nello slug canonico
     */
    public function resolveModuleName(string $name): string
    {
        $this->loadModuleManifests();

        // Controlla alias
        if (isset($this->cache['alias_to_slug'][$name])) {
            return $this->cache['alias_to_slug'][$name];
        }

        // Se esiste un manifest con slug uguale al nome
        if (isset($this->cache['manifests'][$name])) {
            return $name;
        }

        // Se esiste una cartella con lo stesso nome
        if (is_dir($this->modulesPath . $name)) {
            return $name;
        }

        return $name;
    }

    /**
     * Ottiene il manifest di un modulo
     */
    public function getModuleManifest(string $moduleName): ?array
    {
        $slug = $this->resolveModuleName($moduleName);
        $this->loadModuleManifests();
        return $this->cache['manifests'][$slug] ?? null;
    }

    /**
     * Raccoglie asset vendor per i moduli di pagina (da manifest)
     */
    public function collectVendorAssets(array $pageModules): array
    {
        $this->loadModuleManifests();
        $css = [];
        $js = [];
        $visited = [];

        foreach ($pageModules as $pm) {
            $name = $pm['module_name'] ?? '';
            if ($name === '') {
                continue;
            }

            $slug = $this->resolveModuleName($name);
            $this->gatherVendorAssetsForSlug($slug, $css, $js, $visited);
        }

        return [
            'css' => array_keys($css),
            'js' => array_keys($js)
        ];
    }

    /**
     * Raccoglie asset CSS/JS dei moduli usati in pagina (deduplicati)
     */
    public function collectModuleAssets(array $pageModules, array $nestedModules = []): array
    {
        $this->loadModuleManifests();
        $css = [];
        $js = [];
        $visitedSlugs = [];

        // Aggiungi moduli annidati ai moduli della pagina
        $allModules = $pageModules;
        foreach ($nestedModules as $moduleName => $used) {
            if ($used) {
                $allModules[] = ['module_name' => $moduleName];
            }
        }

        foreach ($allModules as $pm) {
            $name = $pm['module_name'] ?? '';
            if ($name === '') {
                continue;
            }

            $slug = $this->resolveModuleName($name);
            if (isset($visitedSlugs[$slug])) {
                continue;
            }
            $visitedSlugs[$slug] = true;

            // 1) Dal manifest, se presente
            $manifest = $this->cache['manifests'][$slug] ?? null;
            if ($manifest && isset($manifest['assets']) && is_array($manifest['assets'])) {
                // CSS dal manifest (con fallback da SCSS->CSS)
                if (isset($manifest['assets']['css']) && is_array($manifest['assets']['css'])) {
                    foreach ($manifest['assets']['css'] as $href) {
                        $candidate = $this->normalizeCssPath($href, $slug);
                        if ($candidate && file_exists(__DIR__ . '/../../' . $candidate)) {
                            $css[$candidate] = true;
                        }
                    }
                }

                // JS dal manifest
                if (isset($manifest['assets']['js']) && is_array($manifest['assets']['js'])) {
                    foreach ($manifest['assets']['js'] as $src) {
                        $candidateJs = $this->normalizeJsPath($src, $slug);
                        if ($candidateJs && file_exists(__DIR__ . '/../../' . $candidateJs)) {
                            $js[$candidateJs] = true;
                        }
                    }
                }
            }

            // 2) Fallback convenzioni standard
            $this->addFallbackAssets($slug, $css, $js);
        }

        return [
            'css' => array_keys($css),
            'js' => array_keys($js)
        ];
    }

    /**
     * Aggiunge asset di fallback usando convenzioni standard
     */
    private function addFallbackAssets(string $slug, array &$css, array &$js): void
    {
        // CSS fallback
        $fallbackCss = [
            'modules/' . $slug . '/' . $slug . '.css',
            'assets/css/modules/' . $slug . '.css'
        ];

        foreach ($fallbackCss as $path) {
            if (!isset($css[$path]) && file_exists(__DIR__ . '/../../' . $path)) {
                $css[$path] = true;
                break;
            }
        }

        // JS fallback
        $fallbackJs = [
            'modules/' . $slug . '/' . $slug . '.js',
            'assets/js/modules/' . $slug . '.js'
        ];

        foreach ($fallbackJs as $path) {
            if (!isset($js[$path]) && file_exists(__DIR__ . '/../../' . $path)) {
                $js[$path] = true;
                break;
            }
        }
    }

    /**
     * Converte eventuali riferimenti SCSS a CSS e normalizza path
     */
    private function normalizeCssPath(string $href, string $slug): string
    {
        // Se punta a SCSS dei modules, converti a CSS
        if (preg_match('#assets/scss/modules/_?([a-z0-9\-]+)\.scss#i', $href, $m)) {
            return 'assets/css/modules/' . $m[1] . '.css';
        }

        // Se punta a SCSS generici, prova a sostituire scss->css
        if (preg_match('#\.scss$#i', $href)) {
            $candidate = preg_replace('#assets/scss#', 'assets/css', $href);
            $candidate = preg_replace('#\.scss$#i', '.css', $candidate);
            return $candidate;
        }

        return $href;
    }

    /**
     * Normalizza path JS per moduli
     */
    private function normalizeJsPath(string $src, string $slug): string
    {
        // Se punta a JS dei modules, converti al nuovo percorso
        if (preg_match('#assets/js/modules/([a-z0-9\-]+)\.js#i', $src, $m)) {
            return 'modules/' . $m[1] . '/' . $m[1] . '.js';
        }

        return $src;
    }

    /**
     * Raccoglie vendor assets per un modulo specifico
     */
    private function gatherVendorAssetsForSlug(string $slug, array &$css, array &$js, array &$visited): void
    {
        if (isset($visited[$slug])) {
            return;
        }
        $visited[$slug] = true;

        $manifest = $this->cache['manifests'][$slug] ?? null;
        if (!$manifest) {
            return;
        }

        // Asset vendor dal manifest
        if (isset($manifest['assets']['vendors']) && is_array($manifest['assets']['vendors'])) {
            foreach ($manifest['assets']['vendors'] as $vendor) {
                if (isset($vendor['css']) && is_array($vendor['css'])) {
                    foreach ($vendor['css'] as $href) {
                        $css[$href] = true;
                    }
                }
                if (isset($vendor['js']) && is_array($vendor['js'])) {
                    foreach ($vendor['js'] as $src) {
                        $js[$src] = true;
                    }
                }
            }
        }

        // Dipendenze ricorsive
        if (isset($manifest['dependencies']) && is_array($manifest['dependencies'])) {
            foreach ($manifest['dependencies'] as $depName) {
                $depSlug = $this->resolveModuleName($depName);
                $this->gatherVendorAssetsForSlug($depSlug, $css, $js, $visited);
            }
        }
    }

    /**
     * Pulisce la cache (utile per testing o reload)
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
