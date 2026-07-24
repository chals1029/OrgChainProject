<?php

namespace App\VotingSystem\Models;

use App\VotingSystem\Core\Model;

class Position extends Model
{
    public function forElection(int $electionId): array
    {
        $statement = $this->db()->prepare('SELECT * FROM positions WHERE election_id = :election_id ORDER BY sort_order, id');
        $statement->execute(['election_id' => $electionId]);
        $positions = $statement->fetchAll();

        if ($positions === []) {
            return [];
        }

        $positionIds = array_map(static fn (array $p): int => (int) $p['id'], $positions);
        $placeholders = implode(',', array_fill(0, count($positionIds), '?'));

        $candidateStatement = $this->db()->prepare(
            'SELECT id, position_id, name, party, image_path, image_mime, sort_order, created_at
             FROM candidates
             WHERE position_id IN (' . $placeholders . ')
             ORDER BY position_id, sort_order, name'
        );
        $candidateStatement->execute($positionIds);
        $candidates = $candidateStatement->fetchAll();

        $byPosition = array_fill_keys($positionIds, []);

        foreach ($candidates as $candidate) {
            $pid = (int) $candidate['position_id'];
            $byPosition[$pid][] = $candidate;
        }

        foreach ($positions as &$position) {
            $position['candidates'] = $byPosition[(int) $position['id']] ?? [];
        }

        return $positions;
    }

    public function all(): array
    {
        return $this->db()->query('SELECT * FROM positions ORDER BY sort_order, id')->fetchAll();
    }
}
