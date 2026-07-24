<?php

namespace App\VotingSystem\Models;

use App\VotingSystem\Core\Model;
use App\VotingSystem\Core\VoteBlockchain;
use InvalidArgumentException;
use RuntimeException;

class Vote extends Model
{
    /**
     * @param array<int, mixed> $choices keyed by position id; value is candidate id(s). Empty/absent = no vote for that position (implicit abstain).
     */
    public function submit(int $electionId, int $voterId, array $choices): string
    {
        $positions = (new Position())->forElection($electionId);
        $validated = $this->validateChoices($positions, $choices);
        $referenceCode = $this->referenceCode();
        $pdo = $this->db();
        $createdAt = date('Y-m-d H:i:s');

        $pdo->beginTransaction();

        try {
            $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
            $voterSql = 'SELECT * FROM voters WHERE id = :id LIMIT 1';

            if ($driver === 'mysql') {
                $voterSql .= ' FOR UPDATE';
            }

            $voterStatement = $pdo->prepare($voterSql);
            $voterStatement->execute(['id' => $voterId]);
            $voter = $voterStatement->fetch();

            if ($voter === false) {
                throw new RuntimeException('Voter record was not found.');
            }

            if ((int) $voter['has_voted'] === 1) {
                throw new RuntimeException('This voter has already submitted a ballot.');
            }

            $createdAt = date('Y-m-d H:i:s');

            $receipt = $pdo->prepare(
                'INSERT INTO vote_receipts (election_id, voter_id, reference_code, created_at)
                 VALUES (:election_id, :voter_id, :reference_code, :created_at)'
            );
            $receipt->execute([
                'election_id' => $electionId,
                'voter_id' => $voterId,
                'reference_code' => $referenceCode,
                'created_at' => $createdAt,
            ]);

            $statement = $pdo->prepare(
                'INSERT INTO votes (election_id, voter_id, position_id, candidate_id, created_at)
                 VALUES (:election_id, :voter_id, :position_id, :candidate_id, :created_at)'
            );

            foreach ($validated as $positionId => $candidateIds) {
                foreach ($candidateIds as $candidateId) {
                    $statement->execute([
                        'election_id' => $electionId,
                        'voter_id' => $voterId,
                        'position_id' => $positionId,
                        'candidate_id' => $candidateId,
                        'created_at' => $createdAt,
                    ]);
                }
            }

            $markVoted = $pdo->prepare(
                'UPDATE voters
                 SET has_voted = 1, voted_at = :voted_at
                 WHERE id = :id AND has_voted = 0'
            );
            $markVoted->execute([
                'id' => $voterId,
                'voted_at' => $createdAt,
            ]);

            if ($markVoted->rowCount() !== 1) {
                throw new RuntimeException('This voter has already submitted a ballot.');
            }

            $pdo->commit();
        } catch (\PDOException $exception) {
            $pdo->rollBack();

            if ($this->isDuplicateKey($exception)) {
                throw new RuntimeException('This voter has already submitted a ballot.');
            }

            throw $exception;
        } catch (\Throwable $throwable) {
            $pdo->rollBack();
            throw $throwable;
        }

        $this->sealReceipt($electionId, $voterId, $referenceCode, $validated, $createdAt);

        return $referenceCode;
    }

    /**
     * @param array<int, array<int>> $validatedChoices
     */
    private function sealReceipt(
        int $electionId,
        int $voterId,
        string $referenceCode,
        array $validatedChoices,
        string $createdAt
    ): void {
        try {
            $chain = (new VoteBlockchain())->seal(
                $electionId,
                $referenceCode,
                $voterId,
                $validatedChoices,
                $createdAt
            );

            $pdo = $this->db();
            $update = $pdo->prepare(
                'UPDATE vote_receipts
                 SET previous_hash = :previous_hash,
                     block_hash = :block_hash,
                     ballot_root = :ballot_root,
                     voter_commitment = :voter_commitment,
                     nodes_confirmed = :nodes_confirmed,
                     node_confirmations = :node_confirmations
                 WHERE reference_code = :reference_code'
            );
            $update->execute([
                'previous_hash' => $chain['previous_hash'],
                'block_hash' => $chain['block_hash'],
                'ballot_root' => $chain['ballot_root'],
                'voter_commitment' => $chain['voter_commitment'],
                'nodes_confirmed' => (int) $chain['nodes_confirmed'],
                'node_confirmations' => json_encode($chain['node_confirmations'], JSON_UNESCAPED_SLASHES),
                'reference_code' => $referenceCode,
            ]);
        } catch (\Throwable $exception) {
            error_log('Vote chain seal failed for '.$referenceCode.': '.$exception->getMessage());
        }
    }

    public function resetForVoter(int $voterId): array
    {
        $pdo = $this->db();
        $pdo->beginTransaction();

        try {
            $deleteVotes = $pdo->prepare('DELETE FROM votes WHERE voter_id = :voter_id');
            $deleteVotes->execute(['voter_id' => $voterId]);
            $votesDeleted = $deleteVotes->rowCount();

            $deleteReceipts = $pdo->prepare('DELETE FROM vote_receipts WHERE voter_id = :voter_id');
            $deleteReceipts->execute(['voter_id' => $voterId]);
            $receiptsDeleted = $deleteReceipts->rowCount();

            $resetVoter = $pdo->prepare('UPDATE voters SET has_voted = 0, voted_at = NULL WHERE id = :id');
            $resetVoter->execute(['id' => $voterId]);

            $pdo->commit();
        } catch (\Throwable $throwable) {
            $pdo->rollBack();
            throw $throwable;
        }

        return [
            'votes_deleted' => $votesDeleted,
            'receipts_deleted' => $receiptsDeleted,
        ];
    }

    public function resetAll(): array
    {
        $pdo = $this->db();
        $pdo->beginTransaction();

        try {
            $votesDeleted = $pdo->exec('DELETE FROM votes');
            $receiptsDeleted = $pdo->exec('DELETE FROM vote_receipts');
            $votersReset = $pdo->exec('UPDATE voters SET has_voted = 0, voted_at = NULL');

            $pdo->commit();
        } catch (\Throwable $throwable) {
            $pdo->rollBack();
            throw $throwable;
        }

        (new VoteBlockchain())->purgeAllLedgers();

        return [
            'votes_deleted' => (int) $votesDeleted,
            'receipts_deleted' => (int) $receiptsDeleted,
            'voters_reset' => (int) $votersReset,
        ];
    }

    /**
     * One vote per contested position maximum; zero selections = implicit abstain (no vote rows).
     *
     * @param array<int, mixed> $choices
     * @return array<int, array<int>>
     */
    private function validateChoices(array $positions, array $choices): array
    {
        $validated = [];
        $errors = [];

        foreach ($positions as $position) {
            $positionId = (int) $position['id'];
            $candidateIdsAllowed = array_map(fn (array $candidate): int => (int) $candidate['id'], $position['candidates']);

            if ($candidateIdsAllowed === []) {
                continue;
            }

            $raw = $choices[$positionId] ?? [];
            $selected = is_array($raw) ? $raw : [$raw];
            $selected = array_values(array_unique(array_filter(array_map('intval', $selected), fn (int $id): bool => $id > 0)));

            if (count($selected) === 0) {
                $validated[$positionId] = [];

                continue;
            }

            if (count($selected) > 1) {
                $errors[] = $position['title'] . ' allows only one selection. Uncheck extras or pick a single candidate.';
                continue;
            }

            foreach ($selected as $candidateId) {
                if (!in_array($candidateId, $candidateIdsAllowed, true)) {
                    $errors[] = 'Invalid candidate selected for ' . $position['title'] . '.';
                    continue 2;
                }
            }

            $validated[$positionId] = $selected;
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        return $validated;
    }

    private function referenceCode(): string
    {
        return 'SSC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    private function isDuplicateKey(\PDOException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? $exception->getCode();
        $driverCode = (string) ($exception->errorInfo[1] ?? '');

        return $sqlState === '23000' || $driverCode === '1062' || $driverCode === '19';
    }
}
