<?php
/**
 * Module Renderer System
 * Sistema di rendering moduli SSR per Bologna Marathon
 */

class ModuleRenderer {
    private $db;
    private $modulesPath;
    private $cache = [];
    private $nestedModules = []; // Traccia moduli annidati
    
    public function __construct($database, $modulesPath = null) {
        $this->db = $database;
        $this->modulesPath = $modulesPath ?? (defined('MODULES_PATH') ? MODULES_PATH : __DIR__ . '/../modules/');
    }
    
    /**
     * Renderizza una pagina completa con i suoi moduli
     */
    public function renderPage($slug, $useInstances = false) {
        $page = $this->getPage($slug);
        if (!$page) {
            throw new Exception("Pagina non trovata: $slug");
        }
        
        if ($useInstances) {
            $modules = $this->getPageModuleInstances($page['id']);
        } else {
            $modules = $this->getPageModules($page['id']);
        }
        
        $cssVariables = $this->getCSSVariables($page);
        
        return [
            'page' => $page,
            'modules' => $modules,
            'css_variables' => $cssVariables,
            'use_instances' => $useInstances
        ];
    }
    
    /**
     * Reset moduli annidati per nuova pagina
     * Da chiamare prima del rendering della pagina
     */
    public function resetNestedModules() {
        $this->nestedModules = [];
    }
    
    /**
     * Renderizza una pagina per ID con istanze di moduli
     */
    public function renderPageById($pageId) {
        $page = $this->getPageById($pageId);
        if (!$page) {
            throw new Exception("Pagina non trovata: ID $pageId");
        }
        
        $modules = $this->getPageModuleInstances($pageId);
        $cssVariables = $this->getCSSVariables($page);
        
        return [
            'page' => $page,
            'modules' => $modules,
            'css_variables' => $cssVariables,
            'use_instances' => true
        ];
    }
    
    /**
     * Renderizza un singolo modulo
     */
    public function renderModule($moduleName, $config = []) {
        // Traccia modulo annidato
        $this->nestedModules[$moduleName] = true;

        $moduleInfo = $this->getModuleInfo($moduleName);
        if (!$moduleInfo) {
            throw new Exception("Modulo non trovato: $moduleName");
        }

        $componentPath = $this->modulesPath . $moduleInfo['component_path'];
        if (!file_exists($componentPath)) {
            throw new Exception("Componente non trovato: $componentPath");
        }

        // Merge config default con config passato
        $defaultConfig = json_decode($moduleInfo['default_config'], true) ?? [];
        $finalConfig = array_merge($defaultConfig, is_array($config) ? $config : []);

        // Buffer per catturare l'output
        ob_start();
        // Passa les variabili necessarie al modulo
        $renderer = $this;
        $config = $finalConfig;
        $module = $moduleInfo;
        include $componentPath;
        $output = ob_get_clean();

        return $output;
    }

    /**
     * Renderizza i moduli annidati per uno slot specifico.
     */
    public function renderChildren(array $config, string $slot = 'default'): string
    {
        $children = $this->getChildrenForSlot($config, $slot);
        if (empty($children)) {
            return '';
        }

        $output = '';
        foreach ($children as $child) {
            $childModule = $child['module'] ?? $child['module_name'] ?? null;
            if (!$childModule) {
                continue;
            }

            $childConfig = $child['config'] ?? [];
            $output .= $this->renderModule($childModule, $childConfig);
        }

        return $output;
    }

    /**
     * Restituisce i figli configurati per uno slot.
     */
    public function getChildrenForSlot(array $config, string $slot = 'default'): array
    {
        if (!isset($config['children'])) {
            return [];
        }

        $children = $config['children'];

        if (isset($children[$slot]) && is_array($children[$slot])) {
            return $children[$slot];
        }

        if ($slot === 'default' && is_array($children) && self::isSequential($children)) {
            return $children;
        }

        return [];
    }

    private static function isSequential(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }
    
    /**
     * Ottiene informazioni su una pagina
     */
    public function getPage($slug) {
        $sql = "SELECT * FROM pages WHERE slug = ? AND status = 'published'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }
    
    /**
     * Ottiene informazioni su una pagina per ID
     */
    public function getPageById($pageId) {
        $sql = "SELECT * FROM pages WHERE id = ? AND status = 'published'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pageId]);
        return $stmt->fetch();
    }
    
    /**
     * Ottiene i moduli di una pagina
     */
    public function getPageModules($pageId) {
        // Non vincolare il join ai nomi, così gli alias possono essere risolti a runtime
        $sql = "SELECT pm.*
                FROM page_modules pm
                WHERE pm.page_id = ? AND pm.is_active = 1
                ORDER BY pm.order_index";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pageId]);
        $modules = $stmt->fetchAll();

        foreach ($modules as &$module) {
            $module['config'] = $this->decodeConfig($module['config'] ?? null);
        }

        return $modules;
    }
    
    /**
     * Ottiene le istanze di moduli di una pagina
     */
    public function getPageModuleInstances($pageId) {
        $sql = "SELECT mi.*
                FROM module_instances mi
                WHERE mi.page_id = ? AND mi.is_active = 1
                ORDER BY mi.order_index";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pageId]);
        $instances = $stmt->fetchAll();

        foreach ($instances as &$instance) {
            $instance['config'] = $this->decodeConfig($instance['config'] ?? null);
        }

        return $instances;
    }

    private function decodeConfig($config): array
    {
        if (is_array($config)) {
            return $config;
        }

        if (!is_string($config) || $config === '') {
            return [];
        }

        $decoded = json_decode($config, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }

        return $decoded ?: [];
    }
    
    /**
     * Ottiene informazioni su un modulo
     */
    private function getModuleInfo($moduleName) {
        // Risolvi alias -> slug
        $resolvedName = $this->resolveModuleName($moduleName);
        if (!isset($this->cache['modules'][$resolvedName])) {
            $sql = "SELECT * FROM modules_registry WHERE name = ? AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$resolvedName]);
            $this->cache['modules'][$resolvedName] = $stmt->fetch();
            // Cache anche per il nome originale se diverso
            if ($resolvedName !== $moduleName) {
                $this->cache['modules'][$moduleName] = $this->cache['modules'][$resolvedName];
            }
        }
        return $this->cache['modules'][$resolvedName] ?? null;
    }

    /**
     * Carica e indicizza i manifest dei moduli (module.json)
     */
    private function loadModuleManifests() {
        if (isset($this->cache['manifests_loaded']) && $this->cache['manifests_loaded'] === true) {
            return;
        }
        $this->cache['manifests'] = [];
        $this->cache['alias_to_slug'] = [];
        if (is_dir($this->modulesPath)) {
            $dirs = scandir($this->modulesPath);
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') { continue; }
                $moduleDir = $this->modulesPath . $dir;
                if (!is_dir($moduleDir)) { continue; }
                $manifestPath = $moduleDir . '/module.json';
                if (file_exists($manifestPath)) {
                    $content = file_get_contents($manifestPath);
                    $json = json_decode($content, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                        $slug = isset($json['slug']) ? $json['slug'] : $dir;
                        $this->cache['manifests'][$slug] = $json;
                        if (isset($json['aliases']) && is_array($json['aliases'])) {
                            foreach ($json['aliases'] as $alias) {
                                $this->cache['alias_to_slug'][$alias] = $slug;
                            }
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
    private function resolveModuleName($name) {
        $this->loadModuleManifests();
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
     * Raccoglie asset vendor per i moduli di pagina (da manifest)
     */
    public function collectVendorAssets(array $pageModules) {
        $this->loadModuleManifests();
        $css = [];
        $js = [];
        $visited = [];
        foreach ($pageModules as $pm) {
            $name = isset($pm['module_name']) ? $pm['module_name'] : '';
            if ($name === '') { continue; }
            $slug = $this->resolveModuleName($name);
            $this->gatherVendorAssetsForSlug($slug, $css, $js, $visited);
        }
        return [
            'css' => array_keys($css),
            'js' => array_keys($js)
        ];
    }

    private function gatherVendorAssetsForSlug($slug, array &$css, array &$js, array &$visited) {
        if (isset($visited[$slug])) { return; }
        $visited[$slug] = true;
        $manifest = $this->cache['manifests'][$slug] ?? null;
        if (!$manifest) { return; }
        if (isset($manifest['assets']['vendors']) && is_array($manifest['assets']['vendors'])) {
            foreach ($manifest['assets']['vendors'] as $vendor) {
                if (isset($vendor['css']) && is_array($vendor['css'])) {
                    foreach ($vendor['css'] as $href) { $css[$href] = true; }
                }
                if (isset($vendor['js']) && is_array($vendor['js'])) {
                    foreach ($vendor['js'] as $src) { $js[$src] = true; }
                }
            }
        }
        if (isset($manifest['dependencies']) && is_array($manifest['dependencies'])) {
            foreach ($manifest['dependencies'] as $depName) {
                $depSlug = $this->resolveModuleName($depName);
                $this->gatherVendorAssetsForSlug($depSlug, $css, $js, $visited);
            }
        }
    }
    
    /**
     * Genera CSS Variables per la pagina
     */
    private function getCSSVariables($page) {
        $cssVars = json_decode($page['css_variables'], true) ?? [];
        $layoutConfig = json_decode($page['layout_config'], true) ?? [];
        
        // Restituisce solo le variabili definite nel database
        // Le variabili di default sono gestite dal CSS principale
        return $cssVars;
    }
    
    // applyCSSVariables non è più utilizzata: rimosso per pulizia

    /**
     * Raccoglie asset CSS/JS dei moduli usati in pagina (deduplicati)
     * Ritorna percorsi relativi servibili dal web server
     */
    public function collectModuleAssets(array $pageModules) {
        $this->loadModuleManifests();
        $css = [];
        $js = [];
        $visitedSlugs = [];

        // Aggiungi moduli annidati ai moduli della pagina
        $allModules = $pageModules;
        foreach ($this->nestedModules as $moduleName => $used) {
            if ($used) {
                $allModules[] = ['module_name' => $moduleName];
            }
        }

        foreach ($allModules as $pm) {
            $name = isset($pm['module_name']) ? $pm['module_name'] : '';
            if ($name === '') { continue; }
            $slug = $this->resolveModuleName($name);
            if (isset($visitedSlugs[$slug])) { continue; }
            $visitedSlugs[$slug] = true;

            // 1) Dal manifest, se presente
            $manifest = $this->cache['manifests'][$slug] ?? null;
            if ($manifest && isset($manifest['assets']) && is_array($manifest['assets'])) {
                // CSS dal manifest (con fallback da SCSS->CSS)
                if (isset($manifest['assets']['css']) && is_array($manifest['assets']['css'])) {
                    foreach ($manifest['assets']['css'] as $href) {
                        $candidate = $this->normalizeCssPath($href, $slug);
                        if ($candidate && file_exists(__DIR__ . '/../' . $candidate)) {
                            $css[$candidate] = true;
                        }
                    }
                }
                // JS dal manifest
                if (isset($manifest['assets']['js']) && is_array($manifest['assets']['js'])) {
                    foreach ($manifest['assets']['js'] as $src) {
                        $candidateJs = $this->normalizeJsPath($src, $slug);
                        if ($candidateJs && file_exists(__DIR__ . '/../' . $candidateJs)) {
                            $js[$candidateJs] = true;
                        }
                    }
                }
            }

            // 2) Fallback convenzioni standard
            $fallbackCss = [
                'modules/' . $slug . '/' . $slug . '.css',
                'assets/css/modules/' . $slug . '.css'
            ];
            foreach ($fallbackCss as $path) {
                if (!isset($css[$path]) && file_exists(__DIR__ . '/../' . $path)) {
                    $css[$path] = true;
                    break;
                }
            }

            $fallbackJs = [
                'assets/js/modules/' . $slug . '.js'
            ];
            foreach ($fallbackJs as $path) {
                if (!isset($js[$path]) && file_exists(__DIR__ . '/../' . $path)) {
                    $js[$path] = true;
                    break;
                }
            }
        }

        return [
            'css' => array_keys($css),
            'js'  => array_keys($js)
        ];
    }

    /**
     * Converte eventuali riferimenti SCSS a CSS e normalizza path
     */
    private function normalizeCssPath($href, $slug) {
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

    /** Normalizza path JS per moduli */
    private function normalizeJsPath($src, $slug) {
        // Se punta a JS dei modules, converti al nuovo percorso
        if (preg_match('#assets/js/modules/([a-z0-9\-]+)\.js#i', $src, $m)) {
            return 'modules/' . $m[1] . '/' . $m[1] . '.js';
        }
        return $src;
    }
    
    /**
     * Ottiene dati per un modulo specifico
     */
    public function getModuleData($moduleName, $config = []) {
        $resolved = $this->resolveModuleName($moduleName);
        switch ($resolved) {
            case 'results':
            case 'resultsTable':
                return $this->getResultsData($config);
            case 'hero':
            case 'actionHero':
                return $this->getHeroData($config);
            case 'text':
            case 'richText':
                return $this->getTextData($config);
            case 'footer':
                return $this->getFooterData($config);
            case 'race-cards':
            case 'raceCards':
                return $this->getRaceCardsData($config);
            case 'button':
                return $this->getButtonData($config);
            default:
                return [];
        }
    }
    
    /**
     * Ottiene dati risultati gara
     */
    private function getResultsData($config) {
        $raceId = $config['race_id'] ?? 1;
        $limit = (int)($config['limit'] ?? 50);
        
        // Sanitizza il limite per sicurezza
        $limit = max(1, min(1000, $limit));
        
        $sql = "SELECT * FROM race_results WHERE race_id = ? ORDER BY position LIMIT " . $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$raceId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Ottiene dati hero
     */
    private function getHeroData($config) {
        return [
            'title' => $config['title'] ?? 'Bologna Marathon',
            'subtitle' => $config['subtitle'] ?? 'La corsa più bella d\'Italia',
            'image' => $config['image'] ?? 'hero-bg.jpg',
            'layout' => $config['layout'] ?? '2col'
        ];
    }
    
    /**
     * Ottiene dati testo (supporta dynamic_content)
     */
    private function getTextData($config) {
        // Se è specificato un contenuto dinamico, leggerlo dal DB
        if (!empty($config['content_id'])) {
            $stmt = $this->db->prepare("SELECT content FROM dynamic_content WHERE id = ? AND is_active = 1");
            $stmt->execute([(int)$config['content_id']]);
            $row = $stmt->fetch();
            if ($row && isset($row['content'])) {
                return [
                    'content' => $row['content'],
                    'wrapper' => $config['wrapper'] ?? 'article'
                ];
            }
        }
        if (!empty($config['content_type'])) {
            // Prende il più recente per tipo
            $stmt = $this->db->prepare("SELECT content FROM dynamic_content WHERE content_type = ? AND is_active = 1 ORDER BY updated_at DESC LIMIT 1");
            $stmt->execute([$config['content_type']]);
            $row = $stmt->fetch();
            if ($row && isset($row['content'])) {
                return [
                    'content' => $row['content'],
                    'wrapper' => $config['wrapper'] ?? 'article'
                ];
            }
        }
        
        // Fallback a contenuto passato via config
        return [
            'content' => $config['content'] ?? '',
            'wrapper' => $config['wrapper'] ?? 'article'
        ];
    }
    
    /**
     * Ottiene dati footer
     */
    private function getFooterData($config) {
        return [
            'columns' => $config['columns'] ?? 4,
            'social' => $config['social'] ?? true,
            'copyright' => $config['copyright'] ?? '&copy; 2025 Bologna Marathon. Tutti i diritti riservati.'
        ];
    }
    
    /**
     * Ottiene dati race cards dal DB con eventuali override
     */
    private function getRaceCardsData($config) {
        // Legge gare (per semplicità: le prime 3 attive/completed ordinate per id)
        $stmt = $this->db->query("SELECT id, name, distance, status FROM races ORDER BY id ASC LIMIT 3");
        $rows = $stmt ? $stmt->fetchAll() : [];
        
        // Override opzionali passati da config (per tag/descrizione/dettagli/testo bottone)
        $overrides = isset($config['race_meta']) && is_array($config['race_meta']) ? $config['race_meta'] : [];
        
        $cards = [];
        foreach ($rows as $index => $race) {
            $raceId = (int)$race['id'];
            $meta = $overrides[$raceId] ?? [];
            
            // Prova a derivare uno slug per assegnare classi colore
            $nameUpper = strtoupper($race['name']);
            $slug = 'generic';
            if (strpos($nameUpper, 'MARAT') !== false) { $slug = 'marathon'; }
            elseif (strpos($nameUpper, 'PORTICI') !== false || strpos($nameUpper, '30K') !== false) { $slug = 'portici'; }
            elseif (strpos($nameUpper, 'RUN TUNE') !== false || strpos($nameUpper, 'HALF') !== false || strpos($nameUpper, 'MEZZA') !== false) { $slug = 'runtune'; }
            
            $cards[] = [
                'id' => $raceId,
                'slug' => $slug,
                'title' => $meta['title'] ?? strtoupper($race['name']),
                'distance' => $meta['distance'] ?? (isset($race['distance']) ? strtoupper($race['distance']) : ''),
                'tag' => $meta['tag'] ?? ($slug === 'marathon' ? 'GARA REGINA' : ($slug === 'portici' ? 'PATRIMONIO UNESCO' : 'ACCESSIBILE A TUTTI')),
                'description' => $meta['description'] ?? '',
                'details' => $meta['details'] ?? [],
                'button_text' => $meta['button_text'] ?? ('ISCRIVITI A ' . strtoupper($race['name']))
            ];
        }
        
        return [
            'layout' => $config['layout'] ?? 'vertical',
            'cards' => $cards
        ];
    }
    
    /**
     * Ottiene dati button
     */
    private function getButtonData($config) {
        return [
            'text' => $config['text'] ?? 'Click me',
            'variant' => $config['variant'] ?? 'primary',
            'size' => $config['size'] ?? 'medium',
            'href' => $config['href'] ?? null,
            'target' => $config['target'] ?? '_self',
            'icon' => $config['icon'] ?? null,
            'iconPosition' => $config['iconPosition'] ?? 'left',
            'disabled' => $config['disabled'] ?? false,
            'loading' => $config['loading'] ?? false,
            'fullWidth' => $config['fullWidth'] ?? false
        ];
    }
}
