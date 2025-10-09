<?php
/**
 * Page Service
 * Gestione logica business per le pagine
 */

namespace BolognaMarathon\Services;

use PDO;
use Exception;

class PageService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Ottieni tutte le pagine con filtri e paginazione
     */
    public function getPages($filters = [], $page = 1, $perPage = 20)
    {
        $where = [];
        $params = [];

        // Filtro per status
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        // Filtro per tema
        if (!empty($filters['theme'])) {
            $where[] = "theme = ?";
            $params[] = $filters['theme'];
        }

        // Ricerca per titolo o slug
        if (!empty($filters['search'])) {
            $where[] = "(title LIKE ? OR slug LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count totale
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM pages {$whereClause}");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Query principale con paginazione
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM pages {$whereClause} ORDER BY updated_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $pages = $stmt->fetchAll();

        return [
            'data' => $pages,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'pages' => ceil($total / $perPage)
            ]
        ];
    }

    /**
     * Ottieni pagina per ID
     */
    public function getPageById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM pages WHERE id = ?");
        $stmt->execute([$id]);
        $page = $stmt->fetch();

        if (!$page) {
            throw new Exception("Pagina non trovata");
        }

        return $page;
    }

    /**
     * Ottieni pagina per slug
     */
    public function getPageBySlug($slug)
    {
        $stmt = $this->db->prepare("SELECT * FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    /**
     * Crea nuova pagina
     */
    public function createPage($data)
    {
        $this->validatePageData($data);

        // Verifica slug univoco
        if ($this->getPageBySlug($data['slug'])) {
            throw new Exception("Slug già esistente");
        }

        $sql = "INSERT INTO pages (slug, title, description, template, layout_config, css_variables, meta_data, status, theme) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            $data['slug'],
            $data['title'],
            $data['description'] ?? null,
            $data['template'] ?? 'default',
            json_encode($data['layout_config'] ?? []),
            json_encode($data['css_variables'] ?? []),
            json_encode($data['meta_data'] ?? []),
            $data['status'] ?? 'draft',
            $data['theme'] ?? 'race-marathon'
        ]);

        if (!$success) {
            throw new Exception("Errore creazione pagina");
        }

        return $this->getPageById($this->db->lastInsertId());
    }

    /**
     * Aggiorna pagina esistente
     */
    public function updatePage($id, $data)
    {
        $page = $this->getPageById($id);

        // Valida solo campi presenti
        if (isset($data['slug']) && $data['slug'] !== $page['slug']) {
            if ($this->getPageBySlug($data['slug'])) {
                throw new Exception("Slug già esistente");
            }
        }

        $updates = [];
        $params = [];

        $allowedFields = ['slug', 'title', 'description', 'template', 'status', 'theme'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        $jsonFields = ['layout_config', 'css_variables', 'meta_data'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = json_encode($data[$field]);
            }
        }

        if (empty($updates)) {
            return $page;
        }

        $params[] = $id;
        $sql = "UPDATE pages SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->getPageById($id);
    }

    /**
     * Elimina pagina
     */
    public function deletePage($id)
    {
        $page = $this->getPageById($id);

        $stmt = $this->db->prepare("DELETE FROM pages WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Duplica pagina
     */
    public function duplicatePage($id, $newSlug = null)
    {
        $original = $this->getPageById($id);

        // Genera slug unico se non fornito
        if (!$newSlug) {
            $baseSlug = $original['slug'] . '-copy';
            $newSlug = $baseSlug;
            $counter = 1;
            
            while ($this->getPageBySlug($newSlug)) {
                $newSlug = $baseSlug . '-' . $counter;
                $counter++;
            }
        }

        $data = [
            'slug' => $newSlug,
            'title' => $original['title'] . ' (Copia)',
            'description' => $original['description'],
            'template' => $original['template'],
            'layout_config' => json_decode($original['layout_config'], true),
            'css_variables' => json_decode($original['css_variables'], true),
            'meta_data' => json_decode($original['meta_data'], true),
            'status' => 'draft', // Sempre draft per copie
            'theme' => $original['theme']
        ];

        $newPage = $this->createPage($data);

        // Duplica anche i moduli associati
        $this->duplicatePageModules($id, $newPage['id']);

        return $newPage;
    }

    /**
     * Cambia status pagina
     */
    public function publishPage($id, $publish = true)
    {
        $status = $publish ? 'published' : 'draft';
        return $this->updatePage($id, ['status' => $status]);
    }

    /**
     * Duplica moduli da una pagina all'altra
     */
    protected function duplicatePageModules($sourcePageId, $targetPageId)
    {
        $sql = "INSERT INTO module_instances (page_id, module_name, instance_name, config, order_index, parent_instance_id, is_active)
                SELECT ?, module_name, CONCAT(instance_name, '-copy'), config, order_index, parent_instance_id, is_active
                FROM module_instances
                WHERE page_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$targetPageId, $sourcePageId]);
    }

    /**
     * Valida dati pagina
     */
    protected function validatePageData($data)
    {
        if (empty($data['slug'])) {
            throw new Exception("Slug obbligatorio");
        }

        if (empty($data['title'])) {
            throw new Exception("Titolo obbligatorio");
        }

        // Valida formato slug
        if (!preg_match('/^[a-z0-9-]+$/', $data['slug'])) {
            throw new Exception("Slug non valido (solo lettere minuscole, numeri e trattini)");
        }

        return true;
    }
}

