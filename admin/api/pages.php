<?php
/**
 * Pages API Endpoint
 * Gestione CRUD pagine
 */

require_once '../../config/database.php';
require_once '../../core/API/BaseController.php';
require_once '../../core/Services/PageService.php';

use BolognaMarathon\API\BaseController;
use BolognaMarathon\Services\PageService;

class PagesAPIController extends BaseController
{
    private $pageService;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->setCorsHeaders();
        $this->pageService = new PageService($db);
    }

    /**
     * Route principale
     */
    public function handle()
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $id = $this->getQuery('id');

            switch ($method) {
                case 'GET':
                    if ($id) {
                        $this->show($id);
                    } else {
                        $this->index();
                    }
                    break;

                case 'POST':
                    if ($this->getQuery('action') === 'duplicate' && $id) {
                        $this->duplicate($id);
                    } else {
                        $this->store();
                    }
                    break;

                case 'PUT':
                case 'PATCH':
                    if ($id) {
                        $this->update($id);
                    } else {
                        $this->error('ID pagina richiesto', 400);
                    }
                    break;

                case 'DELETE':
                    if ($id) {
                        $this->destroy($id);
                    } else {
                        $this->error('ID pagina richiesto', 400);
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
     * Lista pagine
     */
    private function index()
    {
        $filters = [
            'status' => $this->getQuery('status'),
            'theme' => $this->getQuery('theme'),
            'search' => $this->getQuery('search')
        ];

        $page = (int)$this->getQuery('page', 1);
        $perPage = (int)$this->getQuery('per_page', 20);

        $result = $this->pageService->getPages($filters, $page, $perPage);
        
        $this->success($result);
    }

    /**
     * Mostra pagina singola
     */
    private function show($id)
    {
        $page = $this->pageService->getPageById($id);
        $this->success($page);
    }

    /**
     * Crea nuova pagina
     */
    private function store()
    {
        $data = $this->getRequestData();
        
        $this->validateRequired($data, ['slug', 'title']);

        $page = $this->pageService->createPage($data);
        
        $this->success($page, 'Pagina creata con successo', 201);
    }

    /**
     * Aggiorna pagina
     */
    private function update($id)
    {
        $data = $this->getRequestData();
        
        $page = $this->pageService->updatePage($id, $data);
        
        $this->success($page, 'Pagina aggiornata con successo');
    }

    /**
     * Elimina pagina
     */
    private function destroy($id)
    {
        $this->pageService->deletePage($id);
        
        $this->success([], 'Pagina eliminata con successo');
    }

    /**
     * Duplica pagina
     */
    private function duplicate($id)
    {
        $data = $this->getRequestData();
        $newSlug = $data['slug'] ?? null;

        $page = $this->pageService->duplicatePage($id, $newSlug);
        
        $this->success($page, 'Pagina duplicata con successo', 201);
    }
}

// Inizializza database e controller
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $controller = new PagesAPIController($db);
    $controller->handle();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore del server'
    ]);
}

