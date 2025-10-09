<?php
/**
 * Module Service
 * Gestione logica business per i moduli
 */

namespace BolognaMarathon\Services;

use PDO;
use Exception;

class ModuleService
{
    private $db;
    private $modulesPath;

    public function __construct(PDO $db, $modulesPath = null)
    {
        $this->db = $db;
        $this->modulesPath = $modulesPath ?? (defined('MODULES_PATH') ? MODULES_PATH : __DIR__ . '/../../modules/');
    }

    /**
     * Ottieni tutti i moduli registrati
     */
    public function getModules($filters = [])
    {
        $where = [];
        $params = [];

        if (isset($filters['is_active'])) {
            $where[] = "is_active = ?";
            $params[] = $filters['is_active'] ? 1 : 0;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT * FROM modules_registry {$whereClause} ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Ottieni modulo per ID
     */
    public function getModuleById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM modules_registry WHERE id = ?");
        $stmt->execute([$id]);
        $module = $stmt->fetch();

        if (!$module) {
            throw new Exception("Modulo non trovato");
        }

        return $module;
    }

    /**
     * Ottieni modulo per nome
     */
    public function getModuleByName($name)
    {
        $stmt = $this->db->prepare("SELECT * FROM modules_registry WHERE name = ?");
        $stmt->execute([$name]);
        return $stmt->fetch();
    }

    /**
     * Sincronizza moduli da filesystem
     */
    public function syncModulesFromFilesystem()
    {
        $synced = 0;
        $errors = [];

        if (!is_dir($this->modulesPath)) {
            throw new Exception("Directory moduli non trovata: {$this->modulesPath}");
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

            // Cerca module.json
            $manifestPath = $moduleDir . '/module.json';
            if (!file_exists($manifestPath)) {
                $errors[] = "Manifest mancante per modulo: {$dir}";
                continue;
            }

            try {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = "JSON non valido per modulo: {$dir}";
                    continue;
                }

                // Registra o aggiorna modulo
                $this->registerModuleFromManifest($manifest, $dir);
                $synced++;

            } catch (Exception $e) {
                $errors[] = "Errore sync modulo {$dir}: " . $e->getMessage();
            }
        }

        return [
            'synced' => $synced,
            'errors' => $errors
        ];
    }

    /**
     * Registra modulo da manifest
     */
    protected function registerModuleFromManifest($manifest, $dir)
    {
        $name = $manifest['slug'] ?? $manifest['name'] ?? $dir;
        $componentPath = $manifest['component_path'] ?? "{$dir}/{$dir}.php";
        $cssClass = $manifest['css_class'] ?? $dir . '-module';
        $defaultConfig = $manifest['default_config'] ?? [];

        // Controlla se esiste già
        $existing = $this->getModuleByName($name);

        if ($existing) {
            // Aggiorna
            $sql = "UPDATE modules_registry 
                    SET component_path = ?, css_class = ?, default_config = ? 
                    WHERE name = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $componentPath,
                $cssClass,
                json_encode($defaultConfig),
                $name
            ]);
        } else {
            // Inserisci nuovo
            $sql = "INSERT INTO modules_registry (name, component_path, css_class, default_config, is_active) 
                    VALUES (?, ?, ?, ?, 1)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $name,
                $componentPath,
                $cssClass,
                json_encode($defaultConfig)
            ]);
        }
    }

    /**
     * Ottieni istanze moduli per pagina
     */
    public function getModuleInstances($pageId, $includeInactive = false)
    {
        $where = "page_id = ?";
        $params = [$pageId];

        if (!$includeInactive) {
            $where .= " AND is_active = 1";
        }

        $sql = "SELECT 
                    mi.*,
                    COALESCE(template.config, mi.config) as effective_config,
                    template.template_name,
                    template.id as template_master_id
                FROM module_instances mi
                LEFT JOIN module_instances template ON mi.template_instance_id = template.id AND template.is_template = 1
                WHERE {$where}
                ORDER BY COALESCE(mi.parent_instance_id, 0), mi.order_index";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $instances = $stmt->fetchAll();

        // Sostituisci config con effective_config
        foreach ($instances as &$instance) {
            if (isset($instance['effective_config'])) {
                $instance['config'] = $instance['effective_config'];
                unset($instance['effective_config']);
            }
        }

        return $instances;
    }

    /**
     * Crea nuova istanza modulo
     */
    public function createModuleInstance($data)
    {
        $this->validateModuleInstanceData($data);

        // Verifica che il modulo esista
        if (!$this->getModuleByName($data['module_name'])) {
            throw new Exception("Modulo non registrato: {$data['module_name']}");
        }

        $sql = "INSERT INTO module_instances 
                (page_id, module_name, instance_name, config, order_index, parent_instance_id, is_template, template_name, template_instance_id, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            $data['page_id'] ?? null,
            $data['module_name'],
            $data['instance_name'],
            json_encode($data['config'] ?? []),
            $data['order_index'] ?? 0,
            $data['parent_instance_id'] ?? null,
            $data['is_template'] ?? false,
            $data['template_name'] ?? null,
            $data['template_instance_id'] ?? null,
            $data['is_active'] ?? true
        ]);

        if (!$success) {
            throw new Exception("Errore creazione istanza modulo");
        }

        return $this->getModuleInstanceById($this->db->lastInsertId());
    }

    /**
     * Aggiorna istanza modulo
     */
    public function updateModuleInstance($id, $data)
    {
        $instance = $this->getModuleInstanceById($id);

        $updates = [];
        $params = [];

        $allowedFields = ['instance_name', 'order_index', 'parent_instance_id', 'is_active', 'template_instance_id'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (isset($data['config'])) {
            $updates[] = "config = ?";
            $params[] = json_encode($data['config']);
        }

        if (empty($updates)) {
            return $instance;
        }

        $params[] = $id;
        $sql = "UPDATE module_instances SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->getModuleInstanceById($id);
    }

    /**
     * Elimina istanza modulo
     */
    public function deleteModuleInstance($id)
    {
        $instance = $this->getModuleInstanceById($id);

        $stmt = $this->db->prepare("DELETE FROM module_instances WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Riordina moduli in una pagina
     */
    public function reorderModules($pageId, $orderMap)
    {
        $this->db->beginTransaction();

        try {
            foreach ($orderMap as $instanceId => $newOrder) {
                $sql = "UPDATE module_instances SET order_index = ? WHERE id = ? AND page_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$newOrder, $instanceId, $pageId]);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Ottieni istanza modulo per ID
     */
    public function getModuleInstanceById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM module_instances WHERE id = ?");
        $stmt->execute([$id]);
        $instance = $stmt->fetch();

        if (!$instance) {
            throw new Exception("Istanza modulo non trovata");
        }

        return $instance;
    }

    /**
     * Ottieni template globali
     */
    public function getGlobalTemplates($moduleName = null)
    {
        $where = "is_template = 1";
        $params = [];

        if ($moduleName) {
            $where .= " AND module_name = ?";
            $params[] = $moduleName;
        }

        $sql = "SELECT * FROM module_instances WHERE {$where} ORDER BY template_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Valida dati istanza modulo
     */
    protected function validateModuleInstanceData($data)
    {
        if (empty($data['module_name'])) {
            throw new Exception("Nome modulo obbligatorio");
        }

        if (empty($data['instance_name'])) {
            throw new Exception("Nome istanza obbligatorio");
        }

        return true;
    }
}

