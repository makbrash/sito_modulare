<?php

namespace BolognaMarathon\Services;

use PDO;

/**
 * Module Data Provider Service
 * Fornisce dati specifici per ogni tipo di modulo
 */
class ModuleDataProvider
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Ottiene dati per un modulo specifico
     */
    public function getModuleData(string $moduleName, array $config = []): array
    {
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
                
            case 'partner':
            case 'sponsors':
                return $this->getPartnerData($config);
                
            default:
                return [];
        }
    }

    /**
     * Risolve nome modulo (alias -> slug canonico)
     */
    private function resolveModuleName(string $name): string
    {
        $aliases = [
            'resultsTable' => 'results',
            'actionHero' => 'hero',
            'impactHighlights' => 'highlights',
            'eventSchedule' => 'event-schedule',
            'richText' => 'text',
            'raceCards' => 'race-cards',
            'splashLogo' => 'splash-logo',
            'splash_logo' => 'splash-logo',
            'presentation-hero' => 'presentation',
            'hero-presentation' => 'presentation',
        ];

        return $aliases[$name] ?? $name;
    }

    /**
     * Ottiene dati risultati gara
     */
    private function getResultsData(array $config): array
    {
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
    private function getHeroData(array $config): array
    {
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
    private function getHighlightsData(array $config): array
    {
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
    private function getEventScheduleData(array $config): array
    {
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
    private function getTextData(array $config): array
    {
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
    private function getFooterData(array $config): array
    {
        return [
            'columns' => $config['columns'] ?? 4,
            'social' => $config['social'] ?? true,
            'copyright' => $config['copyright'] ?? '&copy; 2025 Bologna Marathon. Tutti i diritti riservati.'
        ];
    }

    /**
     * Ottiene dati race cards dal DB con eventuali override
     */
    private function getRaceCardsData(array $config): array
    {
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
    private function getButtonData(array $config): array
    {
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
     * Ottiene dati per il modulo Splash Logo
     */
    private function getSplashLogoData(array $config): array
    {
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
    private function getPresentationData(array $config): array
    {
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

    /**
     * Ottiene tutti gli sponsor dal database
     */
    public function getSponsors(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, category, group_type, image_path, website_url, description, is_active, sort_order
                FROM sponsors 
                WHERE is_active = 1 
                ORDER BY sort_order ASC, name ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Errore nel caricamento sponsor: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ottiene dati per il modulo partner
     */
    private function getPartnerData(array $config): array
    {
        return [
            'title' => $config['title'] ?? 'I Nostri Partner',
            'show_group1' => $config['show_group1'] ?? true,
            'show_group2' => $config['show_group2'] ?? true,
            'show_group3' => $config['show_group3'] ?? true
        ];
    }
}
