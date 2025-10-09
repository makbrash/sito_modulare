<?php
/**
 * Themes API Endpoint
 * Gestione temi dinamici
 */

require_once '../../config/database.php';
require_once '../../core/API/BaseController.php';
require_once '../../core/Services/ThemeService.php';

use BolognaMarathon\API\BaseController;
use BolognaMarathon\Services\ThemeService;

class ThemesAPIController extends BaseController
{
    private $themeService;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->setCorsHeaders();
        $this->themeService = new ThemeService($db);
    }

    /**
     * Route principale
     */
    public function handle()
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $action = $this->getQuery('action');
            $id = $this->getQuery('id');

            // Route speciali
            if ($action === 'generate-css') {
                $this->generateCSS();
                return;
            }

            if ($action === 'apply-to-page') {
                $this->applyToPage();
                return;
            }

            if ($action === 'export' && $id) {
                $this->export($id);
                return;
            }

            if ($action === 'import') {
                $this->import();
                return;
            }

            if ($action === 'default') {
                if ($method === 'GET') {
                    $this->getDefault();
                } else if ($method === 'POST' && $id) {
                    $this->setDefault($id);
                }
                return;
            }

            // CRUD standard
            switch ($method) {
                case 'GET':
                    if ($id) {
                        $this->show($id);
                    } else {
                        $this->index();
                    }
                    break;

                case 'POST':
                    $this->store();
                    break;

                case 'PUT':
                case 'PATCH':
                    if ($id) {
                        $this->update($id);
                    } else {
                        $this->error('ID tema richiesto', 400);
                    }
                    break;

                case 'DELETE':
                    if ($id) {
                        $this->destroy($id);
                    } else {
                        $this->error('ID tema richiesto', 400);
                    }
                    break;

                default:
                    $this->error('Metodo non supportato', 405);
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Lista temi
     */
    private function index()
    {
        $activeOnly = $this->getQuery('active_only', false);
        
        $themes = $this->themeService->getThemes($activeOnly);
        $this->success($themes);
    }

    /**
     * Mostra tema singolo
     */
    private function show($id)
    {
        $theme = $this->themeService->getThemeById($id);
        $this->success($theme);
    }

    /**
     * Crea nuovo tema
     */
    private function store()
    {
        $data = $this->getRequestData();
        
        $this->validateRequired($data, ['name', 'alias']);

        $theme = $this->themeService->createTheme($data);
        
        $this->success($theme, 'Tema creato con successo', 201);
    }

    /**
     * Aggiorna tema
     */
    private function update($id)
    {
        $data = $this->getRequestData();
        
        $theme = $this->themeService->updateTheme($id, $data);
        
        $this->success($theme, 'Tema aggiornato con successo');
    }

    /**
     * Elimina tema
     */
    private function destroy($id)
    {
        $this->themeService->deleteTheme($id);
        
        $this->success([], 'Tema eliminato con successo');
    }

    /**
     * Genera CSS temi
     */
    private function generateCSS()
    {
        $this->requireMethod('POST');
        
        $this->themeService->generateThemeCSS();
        
        $this->success([], 'CSS temi generato con successo');
    }

    /**
     * Applica tema a pagina
     */
    private function applyToPage()
    {
        $this->requireMethod('POST');
        
        $data = $this->getRequestData();
        
        $this->validateRequired($data, ['page_id', 'theme_alias']);

        $this->themeService->applyThemeToPage($data['page_id'], $data['theme_alias']);
        
        $this->success([], 'Tema applicato alla pagina con successo');
    }

    /**
     * Ottieni tema di default
     */
    private function getDefault()
    {
        $theme = $this->themeService->getDefaultTheme();
        
        if (!$theme) {
            $this->error('Nessun tema di default impostato', 404);
        }
        
        $this->success($theme);
    }

    /**
     * Imposta tema di default
     */
    private function setDefault($id)
    {
        $theme = $this->themeService->updateTheme($id, ['is_default' => true]);
        
        $this->success($theme, 'Tema di default impostato');
    }

    /**
     * Esporta tema in JSON
     */
    private function export($id)
    {
        $json = $this->themeService->exportTheme($id);
        
        // Set headers per download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="theme-' . $id . '.json"');
        echo $json;
        exit;
    }

    /**
     * Importa tema da JSON
     */
    private function import()
    {
        $this->requireMethod('POST');
        
        $data = $this->getRequestData();
        
        if (empty($data['json'])) {
            $this->error('JSON tema richiesto', 400);
        }

        $theme = $this->themeService->importTheme($data['json']);
        
        $this->success($theme, 'Tema importato con successo', 201);
    }
}

// Inizializza database e controller
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $controller = new ThemesAPIController($db);
    $controller->handle();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore del server'
    ]);
}

