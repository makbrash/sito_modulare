<?php
/**
 * Modules API Endpoint
 * Gestione moduli e istanze moduli
 */

require_once '../../config/database.php';
require_once '../../core/API/BaseController.php';
require_once '../../core/Services/ModuleService.php';

use BolognaMarathon\API\BaseController;
use BolognaMarathon\Services\ModuleService;

class ModulesAPIController extends BaseController
{
    private $moduleService;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->setCorsHeaders();
        $this->moduleService = new ModuleService($db);
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
            if ($action === 'sync') {
                $this->sync();
                return;
            }

            if ($action === 'instances') {
                $pageId = $this->getQuery('page_id');
                if ($pageId) {
                    $this->getInstances($pageId);
                } else {
                    $this->error('page_id richiesto', 400);
                }
                return;
            }

            if ($action === 'templates') {
                $this->getTemplates();
                return;
            }

            if ($action === 'reorder') {
                $this->reorder();
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
                        $this->error('ID richiesto', 400);
                    }
                    break;

                case 'DELETE':
                    if ($id) {
                        $this->destroy($id);
                    } else {
                        $this->error('ID richiesto', 400);
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
     * Lista moduli registrati
     */
    private function index()
    {
        $filters = [
            'is_active' => $this->getQuery('is_active')
        ];

        $modules = $this->moduleService->getModules($filters);
        $this->success($modules);
    }

    /**
     * Mostra modulo singolo
     */
    private function show($id)
    {
        $module = $this->moduleService->getModuleById($id);
        $this->success($module);
    }

    /**
     * Crea istanza modulo
     */
    private function store()
    {
        $data = $this->getRequestData();
        
        $this->validateRequired($data, ['module_name', 'instance_name']);

        $instance = $this->moduleService->createModuleInstance($data);
        
        $this->success($instance, 'Istanza modulo creata con successo', 201);
    }

    /**
     * Aggiorna istanza modulo
     */
    private function update($id)
    {
        $data = $this->getRequestData();
        
        $instance = $this->moduleService->updateModuleInstance($id, $data);
        
        $this->success($instance, 'Istanza modulo aggiornata con successo');
    }

    /**
     * Elimina istanza modulo
     */
    private function destroy($id)
    {
        $this->moduleService->deleteModuleInstance($id);
        
        $this->success([], 'Istanza modulo eliminata con successo');
    }

    /**
     * Sincronizza moduli da filesystem
     */
    private function sync()
    {
        $this->requireMethod('POST');
        
        $result = $this->moduleService->syncModulesFromFilesystem();
        
        $message = "{$result['synced']} moduli sincronizzati";
        if (!empty($result['errors'])) {
            $message .= " con errori";
        }
        
        $this->success($result, $message);
    }

    /**
     * Ottieni istanze moduli per pagina
     */
    private function getInstances($pageId)
    {
        $includeInactive = $this->getQuery('include_inactive', false);
        
        $instances = $this->moduleService->getModuleInstances($pageId, $includeInactive);
        
        $this->success($instances);
    }

    /**
     * Ottieni template globali
     */
    private function getTemplates()
    {
        $moduleName = $this->getQuery('module_name');
        
        $templates = $this->moduleService->getGlobalTemplates($moduleName);
        
        $this->success($templates);
    }

    /**
     * Riordina moduli in una pagina
     */
    private function reorder()
    {
        $this->requireMethod('POST');
        
        $data = $this->getRequestData();
        
        $this->validateRequired($data, ['page_id', 'order']);

        $pageId = $data['page_id'];
        $orderMap = $data['order']; // [instanceId => newOrder, ...]

        $this->moduleService->reorderModules($pageId, $orderMap);
        
        $this->success([], 'Moduli riordinati con successo');
    }
}

// Inizializza database e controller
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $controller = new ModulesAPIController($db);
    $controller->handle();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore del server'
    ]);
}

