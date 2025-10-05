<?php
declare(strict_types=1);



/**
 * Provides helpers for managing races and race results.
 */
final class RaceService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function listRaces(): array
    {
        $query = 'SELECT * FROM races ORDER BY date DESC';
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    public function listResults(int $limit = 100): array
    {
        $query = 'SELECT * FROM race_results ORDER BY race_id, position LIMIT :limit';
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createResult(array $payload): void
    {
        admin_require($payload, ['race_id', 'position', 'runner_name']);

        $query = 'INSERT INTO race_results (race_id, position, bib_number, runner_name, category, time_result)
                  VALUES (:race_id, :position, :bib_number, :runner_name, :category, :time_result)';
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':race_id' => (int) $payload['race_id'],
            ':position' => (int) $payload['position'],
            ':bib_number' => $payload['bib_number'] ?? null,
            ':runner_name' => $payload['runner_name'],
            ':category' => $payload['category'] ?? null,
            ':time_result' => $payload['time_result'] ?? null,
        ]);
    }
}
