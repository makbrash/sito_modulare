<?php

namespace BolognaMarathon\Services;

use PDO;

/**
 * Configuration Manager Service
 * Gestisce configurazioni default e merge per moduli
 */
class ConfigManager
{
    private $db;
    private $assetCollector;
    private $cache = [];

    public function __construct(PDO $db, AssetCollector $assetCollector)
    {
        $this->db = $db;
        $this->assetCollector = $assetCollector;
    }

    /**
     * Ottiene la configurazione di default di un modulo
     */
    public function getModuleDefaultConfig(string $moduleName): array
    {
        $cacheKey = "default_config_{$moduleName}";
        
        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $this->loadDefaultConfig($moduleName);
        }

        return $this->cache[$cacheKey];
    }

    /**
     * Unisce configurazione personalizzata con i default del modulo
     */
    public function mergeConfigWithDefaults(string $moduleName, array $config = []): array
    {
        $defaults = $this->getModuleDefaultConfig($moduleName);
        
        if (empty($defaults)) {
            return $config;
        }

        return array_replace_recursive($defaults, $config);
    }

    /**
     * Carica configurazione default da manifest o database
     */
    private function loadDefaultConfig(string $moduleName): array
    {
        // 1) Prova dal manifest
        $manifest = $this->assetCollector->getModuleManifest($moduleName);
        if ($manifest && isset($manifest['default_config'])) {
            if (is_array($manifest['default_config'])) {
                return $manifest['default_config'];
            }

            // Se è JSON string, decodifica
            $decoded = json_decode(json_encode($manifest['default_config']), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // 2) Fallback al database
        $moduleInfo = $this->getModuleInfoFromDb($moduleName);
        if ($moduleInfo && !empty($moduleInfo['default_config'])) {
            $decoded = json_decode($moduleInfo['default_config'], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Ottiene informazioni modulo dal database
     */
    private function getModuleInfoFromDb(string $moduleName): ?array
    {
        $cacheKey = "module_info_{$moduleName}";
        
        if (!isset($this->cache[$cacheKey])) {
            $resolvedName = $this->assetCollector->resolveModuleName($moduleName);
            
            $sql = "SELECT * FROM modules_registry WHERE name = ? AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$resolvedName]);
            
            $this->cache[$cacheKey] = $stmt->fetch();
        }

        return $this->cache[$cacheKey];
    }

    /**
     * Valida configurazione modulo contro schema UI
     */
    public function validateModuleConfig(string $moduleName, array $config): array
    {
        $manifest = $this->assetCollector->getModuleManifest($moduleName);
        
        if (!$manifest || !isset($manifest['ui_schema'])) {
            return $config; // Nessuno schema, accetta tutto
        }

        $schema = $manifest['ui_schema'];
        $validatedConfig = [];

        foreach ($schema as $field => $fieldConfig) {
            $value = $config[$field] ?? null;
            
            // Tipo di validazione basato su schema
            switch ($fieldConfig['type'] ?? 'string') {
                case 'string':
                    $validatedConfig[$field] = is_string($value) ? $value : ($fieldConfig['default'] ?? '');
                    break;
                    
                case 'number':
                case 'integer':
                    $validatedConfig[$field] = is_numeric($value) ? (int)$value : ($fieldConfig['default'] ?? 0);
                    break;
                    
                case 'boolean':
                    $validatedConfig[$field] = (bool)($value ?? ($fieldConfig['default'] ?? false));
                    break;
                    
                case 'array':
                    $validatedConfig[$field] = is_array($value) ? $value : ($fieldConfig['default'] ?? []);
                    break;
                    
                case 'object':
                    $validatedConfig[$field] = is_array($value) ? $value : ($fieldConfig['default'] ?? []);
                    break;
                    
                case 'select':
                case 'radio':
                    $options = $fieldConfig['options'] ?? [];
                    $validatedConfig[$field] = in_array($value, $options) ? $value : ($fieldConfig['default'] ?? ($options[0] ?? ''));
                    break;
                    
                default:
                    $validatedConfig[$field] = $value ?? ($fieldConfig['default'] ?? null);
                    break;
            }
        }

        return $validatedConfig;
    }

    /**
     * Ottiene schema UI per un modulo
     */
    public function getModuleUISchema(string $moduleName): array
    {
        $manifest = $this->assetCollector->getModuleManifest($moduleName);
        return $manifest['ui_schema'] ?? [];
    }

    /**
     * Pulisce la cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
