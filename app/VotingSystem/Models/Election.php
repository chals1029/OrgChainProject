<?php

namespace App\VotingSystem\Models;

use App\VotingSystem\Core\Model;

class Election extends Model
{
    public function current(): ?array
    {
        $statement = $this->db()->query('SELECT * FROM elections ORDER BY id DESC');
        $elections = $statement->fetchAll();

        if (!$elections) {
            return null;
        }

        foreach ($elections as $election) {
            $election = $this->withEffectiveStatus($election);

            if ($election['status'] === 'open') {
                return $election;
            }
        }

        return $this->withEffectiveStatus($elections[0]);
    }

    public function updateBallotContent(int $id, array $fields): void
    {
        $statement = $this->db()->prepare(
            'UPDATE elections SET
                ballot_card_kicker = :ballot_card_kicker,
                ballot_card_heading = :ballot_card_heading,
                ballot_card_body = :ballot_card_body,
                ballot_card_image_path = :ballot_card_image_path
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'ballot_card_kicker' => $fields['ballot_card_kicker'],
            'ballot_card_heading' => $fields['ballot_card_heading'],
            'ballot_card_body' => $fields['ballot_card_body'],
            'ballot_card_image_path' => $fields['ballot_card_image_path'],
        ]);
    }

    public function updateSettings(int $id, array $fields): void
    {
        $statement = $this->db()->prepare(
            'UPDATE elections SET
                title = :title,
                status = :status,
                start_at = :start_at,
                end_at = :end_at,
                announcement = :announcement,
                announcement_expires_at = :announcement_expires_at,
                instructions = :instructions
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'title' => $fields['title'],
            'status' => $fields['status'],
            'start_at' => $fields['start_at'],
            'end_at' => $fields['end_at'],
            'announcement' => $fields['announcement'],
            'announcement_expires_at' => $fields['announcement_expires_at'],
            'instructions' => $fields['instructions'],
        ]);
    }

    public function effectiveStatus(array $election): string
    {
        $configuredStatus = (string) ($election['configured_status'] ?? $election['status'] ?? 'pending');

        return in_array($configuredStatus, ['pending', 'open', 'closed'], true) ? $configuredStatus : 'pending';
    }

    private function withEffectiveStatus(array $election): array
    {
        $election['configured_status'] = (string) ($election['status'] ?? 'pending');
        $election['status'] = $this->effectiveStatus($election);

        return $election;
    }

}
