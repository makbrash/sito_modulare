<?php
declare(strict_types=1);



/**
 * Handles CRUD operations for dynamic content entries.
 */
final class ContentService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function list(): array
    {
        $query = 'SELECT * FROM dynamic_content ORDER BY created_at DESC';
        $stmt = $this->db->query($query);
        $items = $stmt->fetchAll();

        foreach ($items as &$item) {
            $item['metadata'] = $this->decodeJson($item['metadata']);
        }

        return $items;
    }

    public function create(array $payload): array
    {
        admin_require($payload, ['content_type']);

        $query = 'INSERT INTO dynamic_content (content_type, title, content, metadata, is_active)
                  VALUES (:content_type, :title, :content, :metadata, :is_active)';
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':content_type' => $payload['content_type'],
            ':title' => $payload['title'] ?? null,
            ':content' => $payload['content'] ?? null,
            ':metadata' => isset($payload['metadata']) ? json_encode($payload['metadata']) : null,
            ':is_active' => isset($payload['is_active']) ? (int) $payload['is_active'] : 1,
        ]);

        return $this->find((int) $this->db->lastInsertId());
    }

    public function update(int $id, array $payload): array
    {
        $query = 'UPDATE dynamic_content
                  SET content_type = :content_type,
                      title = :title,
                      content = :content,
                      metadata = :metadata,
                      is_active = :is_active,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id';
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':content_type' => $payload['content_type'] ?? null,
            ':title' => $payload['title'] ?? null,
            ':content' => $payload['content'] ?? null,
            ':metadata' => isset($payload['metadata']) ? json_encode($payload['metadata']) : null,
            ':is_active' => isset($payload['is_active']) ? (int) $payload['is_active'] : 1,
            ':id' => $id,
        ]);

        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM dynamic_content WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function find(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM dynamic_content WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();

        if (!$item) {
            throw new RuntimeException('Contenuto non trovato.');
        }

        $item['metadata'] = $this->decodeJson($item['metadata']);

        return $item;
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
