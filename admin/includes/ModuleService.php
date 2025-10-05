<?php
declare(strict_types=1);



/**
 * Service layer for modules and module instances management.
 */
final class ModuleService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Returns all modules registered in the system.
     */
    public function listRegistry(): array
    {
        $query = 'SELECT * FROM modules_registry ORDER BY name';
        $stmt = $this->db->query($query);
        $modules = $stmt->fetchAll();

        foreach ($modules as &$module) {
            $module['default_config'] = $this->decodeJson($module['default_config']);
        }

        return $modules;
    }

    /**
     * Activates or deactivates a module from the registry.
     */
    public function toggleRegistry(int $moduleId, bool $active): void
    {
        $query = 'UPDATE modules_registry SET is_active = :active WHERE id = :id';
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':active' => $active ? 1 : 0,
            ':id' => $moduleId,
        ]);
    }

    /**
     * Returns module instances for a given page.
     */
    public function listInstancesForPage(int $pageId): array
    {
        $query = 'SELECT * FROM module_instances WHERE page_id = :page ORDER BY order_index';
        $stmt = $this->db->prepare($query);
        $stmt->execute([':page' => $pageId]);
        $instances = $stmt->fetchAll();

        foreach ($instances as &$instance) {
            $instance['config'] = $this->decodeJson($instance['config']);
        }

        return $instances;
    }

    /**
     * Creates or updates a module instance.
     */
    public function saveInstance(array $payload): array
    {
        admin_require($payload, ['page_id', 'module_name', 'instance_name', 'config']);
        $payload['config'] = admin_normalize_config($payload['config']);

        $instanceId = isset($payload['id']) ? (int) $payload['id'] : null;
        $orderIndex = isset($payload['order_index']) ? (int) $payload['order_index'] : 0;
        $configJson = json_encode($payload['config']);

        if ($instanceId) {
            $query = 'UPDATE module_instances
                      SET module_name = :module_name,
                          instance_name = :instance_name,
                          config = :config,
                          order_index = :order_index,
                          updated_at = CURRENT_TIMESTAMP
                      WHERE id = :id AND page_id = :page_id';
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':module_name' => $payload['module_name'],
                ':instance_name' => $payload['instance_name'],
                ':config' => $configJson,
                ':order_index' => $orderIndex,
                ':id' => $instanceId,
                ':page_id' => (int) $payload['page_id'],
            ]);
        } else {
            $query = 'INSERT INTO module_instances
                        (page_id, module_name, instance_name, config, order_index)
                      VALUES (:page_id, :module_name, :instance_name, :config, :order_index)';
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':page_id' => (int) $payload['page_id'],
                ':module_name' => $payload['module_name'],
                ':instance_name' => $payload['instance_name'],
                ':config' => $configJson,
                ':order_index' => $orderIndex,
            ]);
            $instanceId = (int) $this->db->lastInsertId();
        }

        return $this->findInstanceById($instanceId);
    }

    /**
     * Deletes an instance from a page.
     */
    public function deleteInstance(int $pageId, int $instanceId): void
    {
        $query = 'DELETE FROM module_instances WHERE id = :id AND page_id = :page_id';
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':id' => $instanceId,
            ':page_id' => $pageId,
        ]);
    }

    /**
     * Reorders module instances for a page.
     */
    public function reorderInstances(int $pageId, array $order): void
    {
        $this->db->beginTransaction();
        try {
            $query = 'UPDATE module_instances SET order_index = :order_index WHERE id = :id AND page_id = :page_id';
            $stmt = $this->db->prepare($query);

            foreach ($order as $item) {
                if (!isset($item['id'], $item['order_index'])) {
                    continue;
                }

                $stmt->execute([
                    ':order_index' => (int) $item['order_index'],
                    ':id' => (int) $item['id'],
                    ':page_id' => $pageId,
                ]);
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    /**
     * Returns the manifest for a module if available.
     */
    public function getManifest(string $moduleName): array
    {
        $moduleDir = MODULES_PATH . $moduleName;
        $manifestPath = $moduleDir . '/module.json';

        if (!is_dir($moduleDir) || !file_exists($manifestPath)) {
            throw new RuntimeException("Manifest non trovato per il modulo {$moduleName}");
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Manifest JSON non valido: ' . json_last_error_msg());
        }

        return $manifest;
    }

    /**
     * Returns an instance by ID.
     */
    public function findInstanceById(int $id): array
    {
        $query = 'SELECT * FROM module_instances WHERE id = :id';
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);
        $instance = $stmt->fetch();

        if (!$instance) {
            throw new RuntimeException('Istanza modulo non trovata.');
        }

        $instance['config'] = $this->decodeJson($instance['config']);

        return $instance;
    }

    private function decodeJson(null|string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $decoded ?: [];
    }
}
