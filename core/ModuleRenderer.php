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
            $moduleTree = $this->buildModuleInstanceTree($modules);
        } else {
            $modules = $this->getPageModules($page['id']);
            $moduleTree = [];
        }

        $cssVariables = $this->getCSSVariables($page);

        return [
            'page' => $page,
            'modules' => $modules,
            'css_variables' => $cssVariables,
            'use_instances' => $useInstances,
            'module_tree' => $moduleTree,
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
        $moduleTree = $this->buildModuleInstanceTree($modules);
        $cssVariables = $this->getCSSVariables($page);

        return [
            'page' => $page,
            'modules' => $modules,
            'css_variables' => $cssVariables,
            'use_instances' => true,
            'module_tree' => $moduleTree,
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
        $finalConfig = $this->mergeConfigWithDefaults($moduleName, $config);

        if (!empty($finalConfig['__inline_html']) && is_string($finalConfig['__inline_html'])) {
            return $finalConfig['__inline_html'];
        }

        // Buffer per catturare l'output
        ob_start();
        // Passa les variabili necessarie al modulo
        $renderer = $this;
        $config = $finalConfig;
        include $componentPath;
        $output = ob_get_clean();
        
        return $output;
    }

    /**
     * Restituisce il manifest JSON di un modulo.
     */
    public function getModuleManifest($moduleName) {
        $slug = $this->resolveModuleName($moduleName);
        $this->loadModuleManifests();
        return $this->cache['manifests'][$slug] ?? null;
    }

    /**
     * Restituisce la configurazione di default dichiarata dal modulo.
     */
    public function getModuleDefaultConfig($moduleName) {
        $manifest = $this->getModuleManifest($moduleName);
        if (is_array($manifest) && isset($manifest['default_config'])) {
            if (is_array($manifest['default_config'])) {
                return $manifest['default_config'];
            }

            $decoded = json_decode(json_encode($manifest['default_config']), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $moduleInfo = $this->getModuleInfo($moduleName);
        if ($moduleInfo && !empty($moduleInfo['default_config'])) {
            $decoded = json_decode($moduleInfo['default_config'], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Unisce configurazione personalizzata con i default del modulo.
     */
    public function mergeConfigWithDefaults($moduleName, array $config = []) {
        $defaults = $this->getModuleDefaultConfig($moduleName);
        if (empty($defaults)) {
            return $config;
        }

        return array_replace_recursive($defaults, $config);
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
        return $stmt->fetchAll();
    }
    
    /**
     * Ottiene le istanze di moduli di una pagina
     */
    public function getPageModuleInstances($pageId) {
        $sql = "SELECT mi.*
                FROM module_instances mi
                WHERE mi.page_id = ? AND mi.is_active = 1
                ORDER BY COALESCE(mi.parent_instance_id, 0), mi.order_index";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pageId]);
        return $stmt->fetchAll();
    }

    /**
     * Costruisce albero annidato delle istanze di moduli
     */
    public function buildModuleInstanceTree(array $instances): array
    {
        if (empty($instances)) {
            return [];
        }

        $byParent = [];
        foreach ($instances as $instance) {
            $parentId = $instance['parent_instance_id'] ?? null;
            if ($parentId !== null) {
                $parentId = (int)$parentId;
            }

            $byParent[$parentId ?? 0][] = $instance;
        }

        $build = function ($parentId) use (&$build, &$byParent) {
            $result = [];
            $bucketKey = $parentId ?? 0;
            if (!isset($byParent[$bucketKey])) {
                return $result;
            }

            foreach ($byParent[$bucketKey] as $instance) {
                $node = $instance;
                $node['children'] = $build((int)$instance['id']);
                $result[] = $node;
            }

            return $result;
        };

        return $build(null);
    }

    /**
     * Appiattisce un albero di istanze in lista
     */
    public function flattenModuleInstanceTree(array $tree): array
    {
        $flat = [];
        foreach ($tree as $node) {
            $nodeCopy = $node;
            $children = $nodeCopy['children'] ?? [];
            unset($nodeCopy['children']);
            $flat[] = $nodeCopy;
            if (!empty($children)) {
                $flat = array_merge($flat, $this->flattenModuleInstanceTree($children));
            }
        }

        return $flat;
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
                'modules/' . $slug . '/' . $slug . '.js',
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
            case 'highlights':
            case 'impactHighlights':
                return $this->getHighlightsData($config);
            case 'event-schedule':
            case 'eventSchedule':
                return $this->getEventScheduleData($config);
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
            case 'splash-logo':
            case 'splashLogo':
            case 'splash_logo':
                return $this->getSplashLogoData($config);
            case 'presentation':
            case 'presentation-hero':
            case 'hero-presentation':
                return $this->getPresentationData($config);
            case 'highlights':
            case 'news-highlights':
            case 'news-slider':
                return $this->getHighlightsData($config);
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
        $defaults = [
            'height' => 'min(100vh, 860px)',
            'eyebrow' => [
                'icon' => 'fa-regular fa-calendar',
                'label' => '2 MARZO 2026'
            ],
            'title' => 'Thermal Bologna Marathon',
            'subtitle' => 'Corri attraverso la storia',
            'description' => "Tre percorsi unici nel cuore di Bologna. Scegli la tua sfida e vivi un'esperienza indimenticabile tra storia, cultura e sport.",
            'background' => [
                'image' => 'assets/images/hero-bg.jpg',
                'position' => 'center',
                'size' => 'cover',
                'overlay' => 'linear-gradient(135deg, rgba(35, 168, 235, 0.82) 0%, rgba(220, 51, 94, 0.65) 100%)',
                'overlay_opacity' => 0.88
            ],
            'actions' => [
                [
                    'type' => 'button',
                    'text' => 'Scopri le gare',
                    'variant' => 'primary',
                    'size' => 'large',
                    'icon' => 'fa-solid fa-play',
                    'iconPosition' => 'left',
                    'href' => '#gare'
                ],
                [
                    'type' => 'button',
                    'text' => 'Iscriviti ora',
                    'variant' => 'ghost',
                    'size' => 'large',
                    'href' => '#iscrizioni'
                ]
            ],
            'stats' => [
                ['label' => 'Percorsi', 'value' => '3', 'icon' => 'fa-solid fa-route'],
                ['label' => 'Atleti attesi', 'value' => '12K+', 'icon' => 'fa-solid fa-users'],
                ['label' => 'Villaggio Expo', 'value' => '3 giorni', 'icon' => 'fa-solid fa-flag-checkered']
            ]
        ];

        $config = is_array($config) ? $config : [];
        $data = array_replace_recursive($defaults, $config);

        // Assicurati che actions e stats siano sempre array
        $data['actions'] = is_array($data['actions'] ?? null) ? $data['actions'] : [];
        $data['stats'] = is_array($data['stats'] ?? null) ? $data['stats'] : [];
        
        // Filtra solo gli elementi che sono array
        $data['actions'] = array_values(array_filter($data['actions'], 'is_array'));
        $data['stats'] = array_values(array_filter($data['stats'], 'is_array'));

        return $data;
    }

    /**
     * Dati per highlights cards
     */
    private function getHighlightsData($config) {
        $defaults = [
            'eyebrow' => 'Perché Bologna',
            'title' => 'Una maratona, mille motivi per esserci',
            'subtitle' => 'Un evento diffuso che abbraccia sportivi, famiglie e aziende con un programma ricco e inclusivo.',
            'items' => [
                [
                    'icon' => 'fa-solid fa-city',
                    'title' => 'Cuore storico',
                    'description' => 'Percorsi che attraversano i portici UNESCO e mostrano la città da una prospettiva unica.'
                ],
                [
                    'icon' => 'fa-solid fa-handshake-angle',
                    'title' => 'Community first',
                    'description' => 'Partnership con oltre 80 associazioni e una rete di volontari pronti ad accoglierti.'
                ],
                [
                    'icon' => 'fa-solid fa-bolt',
                    'title' => 'Servizi premium',
                    'description' => 'Logistica fluida, villaggio expo tematico e assistenza continua dal ritiro pettorale al dopo gara.'
                ]
            ],
            'cta' => [
                'text' => 'Scopri il programma completo',
                'variant' => 'ghost',
                'href' => '#programma'
            ]
        ];

        $config = is_array($config) ? $config : [];
        $data = array_replace_recursive($defaults, $config);

        // Assicurati che items sia sempre un array
        $data['items'] = is_array($data['items'] ?? null) ? $data['items'] : [];
        
        $data['items'] = array_values(array_filter($data['items'], function ($item) {
            return is_array($item) && (!empty($item['title']) || !empty($item['description']));
        }));

        return $data;
    }

    /**
     * Dati per il programma eventi
     */
    private function getEventScheduleData($config) {
        $defaults = [
            'eyebrow' => 'Programma ufficiale',
            'title' => 'Tre giorni di festa sportiva',
            'subtitle' => 'Dalla consegna pettorali alla cerimonia finale, ogni momento è pensato per creare energia in città.',
            'days' => [
                [
                    'label' => 'Venerdì',
                    'date' => '28 Febbraio',
                    'events' => [
                        [
                            'time' => '10:00',
                            'title' => 'Apertura Marathon Expo',
                            'location' => 'Piazza Maggiore',
                            'description' => 'Espositori, partner e attività di warm-up con ospiti speciali.'
                        ],
                        [
                            'time' => '18:30',
                            'title' => 'Talk ispirazionale',
                            'location' => 'Teatro Duse',
                            'description' => 'Storie di resilienza con campioni e ambassador della maratona.'
                        ]
                    ]
                ],
                [
                    'label' => 'Sabato',
                    'date' => '1 Marzo',
                    'events' => [
                        [
                            'time' => '09:30',
                            'title' => 'Family Run',
                            'location' => 'Giardini Margherita',
                            'description' => 'Percorso inclusivo di 5 km per famiglie, scuole e associazioni.'
                        ],
                        [
                            'time' => '15:00',
                            'title' => 'Ritiro pettorali',
                            'location' => 'Marathon Expo',
                            'description' => 'Ultimo slot per ritiro pettorali e briefing tecnico con i pacer.'
                        ]
                    ]
                ],
                [
                    'label' => 'Domenica',
                    'date' => '2 Marzo',
                    'events' => [
                        [
                            'time' => '08:30',
                            'title' => 'Partenza maratona & 30km dei Portici',
                            'location' => 'Via Rizzoli',
                            'description' => 'Start con onde dedicate e musica live per accompagnare gli atleti.'
                        ],
                        [
                            'time' => '12:30',
                            'title' => 'Cerimonia di premiazione',
                            'location' => 'Piazza Maggiore',
                            'description' => 'Live band, medaglie personalizzate e festa conclusiva aperta alla città.'
                        ]
                    ]
                ]
            ],
            'cta' => [
                'text' => 'Scarica il programma PDF',
                'variant' => 'primary',
                'href' => '#download-programma',
                'icon' => 'fa-solid fa-file-arrow-down',
                'iconPosition' => 'left'
            ]
        ];

        $config = is_array($config) ? $config : [];
        $data = array_replace_recursive($defaults, $config);

        // Assicurati che days sia sempre un array
        $data['days'] = is_array($data['days'] ?? null) ? $data['days'] : [];
        
        $data['days'] = array_values(array_filter($data['days'], function ($day) {
            return is_array($day) && (!empty($day['events']) || !empty($day['label']));
        }));

        foreach ($data['days'] as &$day) {
            if (!isset($day['events']) || !is_array($day['events'])) {
                $day['events'] = [];
                continue;
            }
            $day['events'] = array_values(array_filter($day['events'], function ($event) {
                return is_array($event) && (!empty($event['title']) || !empty($event['time']));
            }));
        }
        unset($day);

        return $data;
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
    
    /**
     * Ottiene il tema di una pagina
     */
    public function getPageTheme($pageId) {
        $stmt = $this->db->prepare("SELECT theme FROM pages WHERE id = ?");
        $stmt->execute([$pageId]);
        $page = $stmt->fetch();
        return $page['theme'] ?? 'marathon';
    }
    
    /**
     * Ottiene tutti i temi disponibili
     */
    public function getAvailableThemes() {
        $stmt = $this->db->prepare("SELECT * FROM theme_identities WHERE is_active = 1 ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Ottiene un tema specifico
     */
    public function getTheme($themeId) {
        $stmt = $this->db->prepare("SELECT * FROM theme_identities WHERE id = ? AND is_active = 1");
        $stmt->execute([$themeId]);
        return $stmt->fetch();
    }
    
    /**
     * Aggiorna il tema di una pagina
     */
    public function updatePageTheme($pageId, $theme) {
        $stmt = $this->db->prepare("UPDATE pages SET theme = ? WHERE id = ?");
        return $stmt->execute([$theme, $pageId]);
    }
    
    /**
     * Ottiene dati per il modulo Splash Logo
     */
    private function getSplashLogoData($config) {
        return [
            'logo_url' => $config['logo_url'] ?? 'assets/images/splash.svg',
            'duration' => $config['duration'] ?? 2500,
            'logo_size' => $config['logo_size'] ?? 100,
            'pulse_speed' => $config['pulse_speed'] ?? 2
        ];
    }
    
    /**
     * Ottiene dati per il modulo Presentation
     */
    private function getPresentationData($config) {
        // Statistiche di default
        $defaultStats = [
            [
                'icon' => 'fas fa-running',
                'number' => '10.000+',
                'label' => 'Runner'
            ],
            [
                'icon' => 'fas fa-globe',
                'number' => '50+',
                'label' => 'Nazioni'
            ],
            [
                'icon' => 'fas fa-music',
                'number' => '3',
                'label' => 'Giorni di Eventi'
            ]
        ];
        
        return [
            'title' => $config['title'] ?? 'DOVE LO SPORT INCONTRA',
            'subtitle' => $config['subtitle'] ?? 'LA STORIA',
            'description1' => $config['description1'] ?? 'La Maratona di Bologna è un grande evento dove lo sport si fonde con la storia, la cultura, l\'arte, la musica e il buon cibo di una delle città più antiche d\'Europa.',
            'description2' => $config['description2'] ?? 'Tre giorni di festa in Piazza Maggiore, una delle piazze più belle d\'Italia, offrendo a tutti i partecipanti diverse opportunità per visitare e scoprire una città unica.',
            'image_url' => $config['image_url'] ?? 'assets/images/marathon-start.jpg',
            'image_alt' => $config['image_alt'] ?? 'Maratona di Bologna',
            'image_position' => $config['image_position'] ?? 'right',
            'stats' => $config['stats'] ?? $defaultStats
        ];
    }
}
