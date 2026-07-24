<?php

namespace App\VotingSystem\Models;

use App\VotingSystem\Core\Model;
use PDO;

class Candidate extends Model
{
    public function find(int $id): ?array
    {
        $statement = $this->db()->prepare('SELECT * FROM candidates WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    public function forPosition(int $positionId): array
    {
        $statement = $this->db()->prepare('SELECT * FROM candidates WHERE position_id = :position_id ORDER BY sort_order, name');
        $statement->execute(['position_id' => $positionId]);

        return $statement->fetchAll();
    }

    public function create(array $data): void
    {
        $statement = $this->db()->prepare(
            'INSERT INTO candidates (position_id, name, party, image_path, image_blob, image_mime, sort_order)
             VALUES (:position_id, :name, :party, :image_path, :image_blob, :image_mime, :sort_order)'
        );
        $statement->bindValue(':position_id', (int) $data['position_id'], PDO::PARAM_INT);
        $statement->bindValue(':name', trim($data['name']));
        $statement->bindValue(':party', trim($data['party'] ?? ''));
        $statement->bindValue(':image_path', $this->cleanImagePath($data['image_path'] ?? ''));
        $statement->bindValue(':image_blob', $data['image_blob'] ?? null, PDO::PARAM_LOB);
        $statement->bindValue(':image_mime', $this->cleanImageMime((string) ($data['image_mime'] ?? '')));
        $statement->bindValue(':sort_order', (int) ($data['sort_order'] ?? 0), PDO::PARAM_INT);
        $statement->execute();
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE candidates SET
                    position_id = :position_id,
                    name = :name,
                    party = :party,
                    image_path = :image_path,
                    sort_order = :sort_order';

        if (array_key_exists('image_blob', $data)) {
            $sql .= ', image_blob = :image_blob, image_mime = :image_mime';
        }

        $sql .= ' WHERE id = :id LIMIT 1';

        $statement = $this->db()->prepare($sql);
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->bindValue(':position_id', (int) $data['position_id'], PDO::PARAM_INT);
        $statement->bindValue(':name', trim($data['name']));
        $statement->bindValue(':party', trim($data['party'] ?? ''));
        $statement->bindValue(':image_path', $this->cleanImagePath($data['image_path'] ?? ''));
        $statement->bindValue(':sort_order', (int) ($data['sort_order'] ?? 0), PDO::PARAM_INT);

        if (array_key_exists('image_blob', $data)) {
            $statement->bindValue(':image_blob', $data['image_blob'], PDO::PARAM_LOB);
            $statement->bindValue(':image_mime', $this->cleanImageMime((string) ($data['image_mime'] ?? '')));
        }

        $statement->execute();
    }

    public function delete(int $id): bool
    {
        $statement = $this->db()->prepare('DELETE FROM candidates WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() === 1;
    }

    private function cleanImagePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '';
        }

        if (
            preg_match('/^https?:\/\//i', $path)
            || str_contains($path, '..')
            || str_contains($path, '\\')
            || str_starts_with($path, '//')
            || str_starts_with($path, '/')
        ) {
            return '';
        }

        $extension = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        if (!in_array($extension, $allowedExtensions, true)) {
            return '';
        }

        return $path;
    }

    private function cleanImageMime(string $mime): string
    {
        return in_array($mime, ['image/jpeg', 'image/png'], true) ? $mime : '';
    }
}
