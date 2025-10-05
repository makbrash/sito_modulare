<?php
declare(strict_types=1);



/**
 * Service layer responsible for page management.
 */
final class PageService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function list(): array
    {
        $query = 'SELECT * FROM pages ORDER BY title';
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    public function find(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM pages WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $page = $stmt->fetch();

        if (!$page) {
            throw new RuntimeException('Pagina non trovata.');
        }

        return $page;
    }

    public function create(array $payload): array
    {
        admin_require($payload, ['slug', 'title']);

        $query = 'INSERT INTO pages (slug, title, description, template, layout_config, css_variables, status)
                  VALUES (:slug, :title, :description, :template, :layout_config, :css_variables, :status)';
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':slug' => $payload['slug'],
            ':title' => $payload['title'],
            ':description' => $payload['description'] ?? null,
            ':template' => $payload['template'] ?? 'default',
            ':layout_config' => isset($payload['layout_config']) ? json_encode($payload['layout_config']) : null,
            ':css_variables' => isset($payload['css_variables']) ? json_encode($payload['css_variables']) : null,
            ':status' => $payload['status'] ?? 'draft',
        ]);

        return $this->find((int) $this->db->lastInsertId());
    }

    public function update(int $id, array $payload): array
    {
        $query = 'UPDATE pages
                  SET slug = :slug,
                      title = :title,
                      description = :description,
                      template = :template,
                      layout_config = :layout_config,
                      css_variables = :css_variables,
                      status = :status,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id';

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':slug' => $payload['slug'] ?? null,
            ':title' => $payload['title'] ?? null,
            ':description' => $payload['description'] ?? null,
            ':template' => $payload['template'] ?? 'default',
            ':layout_config' => isset($payload['layout_config']) ? json_encode($payload['layout_config']) : null,
            ':css_variables' => isset($payload['css_variables']) ? json_encode($payload['css_variables']) : null,
            ':status' => $payload['status'] ?? 'draft',
            ':id' => $id,
        ]);

        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM pages WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
