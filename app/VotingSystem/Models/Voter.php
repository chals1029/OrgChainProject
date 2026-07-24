<?php

namespace App\VotingSystem\Models;

use App\VotingSystem\Core\Model;

class Voter extends Model
{
    public function verify(string $srCode, string $email): ?array
    {
        $sql = 'SELECT * FROM voters WHERE UPPER(sr_code) = UPPER(:sr_code) AND LOWER(email) = LOWER(:email) LIMIT 1';
        $params = [
            'sr_code' => trim($srCode),
            'email' => trim($email),
        ];

        $statement = $this->db()->prepare($sql);
        $statement->execute($params);
        $voter = $statement->fetch();

        return $voter ?: null;
    }

    public function find(int $id): ?array
    {
        $statement = $this->db()->prepare('SELECT * FROM voters WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $voter = $statement->fetch();

        return $voter ?: null;
    }

    public function all(int $limit = 100): array
    {
        $statement = $this->db()->prepare('SELECT * FROM voters ORDER BY college, program, full_name LIMIT :limit');
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function filtered(?string $srCode = null, ?string $college = null, int $limit = 250): array
    {
        [$where, $params] = $this->filterQueryParts($srCode, $college);
        $sql = 'SELECT * FROM voters'
            . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY college, program, full_name LIMIT :limit';

        $statement = $this->db()->prepare($sql);

        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }

        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function filteredCount(?string $srCode = null, ?string $college = null): int
    {
        [$where, $params] = $this->filterQueryParts($srCode, $college);
        $sql = 'SELECT COUNT(*) FROM voters'
            . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '');

        $statement = $this->db()->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    public function colleges(): array
    {
        $statement = $this->db()->query(
            'SELECT DISTINCT TRIM(college) AS college
             FROM voters
             WHERE TRIM(COALESCE(college, \'\')) <> \'\'
             ORDER BY TRIM(college)'
        );

        $colleges = array_column($statement->fetchAll(), 'college');

        usort($colleges, static function (string $left, string $right): int {
            return \college_sort_rank($left) <=> \college_sort_rank($right)
                ?: strcmp(\college_abbreviation($left), \college_abbreviation($right));
        });

        return $colleges;
    }

    public function markVoted(int $id): void
    {
        $statement = $this->db()->prepare('UPDATE voters SET has_voted = 1, voted_at = :voted_at WHERE id = :id');
        $statement->execute([
            'id' => $id,
            'voted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function create(array $data): void
    {
        $statement = $this->db()->prepare(
            'INSERT INTO voters (sr_code, full_name, email, college, program, year_level, grade_level, has_voted)
             VALUES (:sr_code, :full_name, :email, :college, :program, :year_level, :grade_level, 0)'
        );

        $statement->execute($this->clean($data));
    }

    public function upsert(array $data): void
    {
        $clean = $this->clean($data);
        $existing = $this->findBySrCode($clean['sr_code']);

        if ($existing) {
            $statement = $this->db()->prepare(
                'UPDATE voters
                 SET full_name = :full_name, email = :email, college = :college,
                    program = :program, year_level = :year_level, grade_level = :grade_level
                 WHERE sr_code = :sr_code'
            );
            $statement->execute($clean);
            return;
        }

        $this->create($clean);
    }

    public function update(int $id, array $data): void
    {
        $clean = $this->clean($data);
        $clean['id'] = $id;

        $statement = $this->db()->prepare(
            'UPDATE voters
             SET sr_code = :sr_code, full_name = :full_name, email = :email, college = :college,
                program = :program, year_level = :year_level, grade_level = :grade_level
             WHERE id = :id'
        );
        $statement->execute($clean);
    }

    /**
     * Bulk import voters using MySQL INSERT ... ON DUPLICATE KEY UPDATE.
     * Processes rows in batches for performance on large CSV files.
     */
    public function bulkUpsert(array $rows, int $batchSize = 500): int
    {
        $db = $this->db();
        $count = 0;
        $batches = array_chunk($rows, $batchSize);

        foreach ($batches as $batch) {
            $placeholders = [];
            $values = [];
            $i = 0;

            foreach ($batch as $row) {
                $clean = $this->clean($row);
                if (empty($clean['sr_code'])) {
                    continue;
                }

                $placeholders[] = "(:sr_{$i}, :fn_{$i}, :em_{$i}, :co_{$i}, :pr_{$i}, :yl_{$i}, :gl_{$i}, 0)";
                $values["sr_{$i}"] = $clean['sr_code'];
                $values["fn_{$i}"] = $clean['full_name'];
                $values["em_{$i}"] = $clean['email'];
                $values["co_{$i}"] = $clean['college'];
                $values["pr_{$i}"] = $clean['program'];
                $values["yl_{$i}"] = $clean['year_level'];
                $values["gl_{$i}"] = $clean['grade_level'];
                $count++;
                $i++;
            }

            if (empty($placeholders)) {
                continue;
            }

            $sql = 'INSERT INTO voters (sr_code, full_name, email, college, program, year_level, grade_level, has_voted) VALUES '
                 . implode(', ', $placeholders)
                 . ' ON DUPLICATE KEY UPDATE'
                 . ' full_name = VALUES(full_name),'
                 . ' email = VALUES(email),'
                 . ' college = VALUES(college),'
                 . ' program = VALUES(program),'
                 . ' year_level = VALUES(year_level),'
                 . ' grade_level = VALUES(grade_level)';

            $statement = $db->prepare($sql);
            $statement->execute($values);
        }

        return $count;
    }

    public function findBySrCode(string $srCode): ?array
    {
        $statement = $this->db()->prepare('SELECT * FROM voters WHERE UPPER(sr_code) = UPPER(:sr_code) LIMIT 1');
        $statement->execute(['sr_code' => trim($srCode)]);
        $voter = $statement->fetch();

        return $voter ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db()->prepare('SELECT * FROM voters WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $statement->execute(['email' => trim($email)]);
        $voter = $statement->fetch();

        return $voter ?: null;
    }

    public function delete(int $id): bool
    {
        $statement = $this->db()->prepare('DELETE FROM voters WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() === 1;
    }

    public function deleteAll(): int
    {
        return (int) $this->db()->exec('DELETE FROM voters');
    }

    private function clean(array $data): array
    {
        $email = strtolower($this->cleanText($data['email'] ?? '', 255));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $email = '';
        }

        return [
            'sr_code' => $this->cleanText($data['sr_code'] ?? '', 64),
            'full_name' => $this->cleanText($data['full_name'] ?? '', 255),
            'email' => $email,
            'college' => $this->cleanText($data['college'] ?? '', 255),
            'program' => $this->cleanText($data['program'] ?? $data['department'] ?? '', 255),
            'year_level' => $this->normalizeYearLevel($data['year_level'] ?? ''),
            'grade_level' => $this->normalizeGradeLevel($data['grade_level'] ?? ''),
        ];
    }

    private function cleanText(mixed $value, int $maxLength): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return substr($value, 0, $maxLength);
    }

    private function normalizeYearLevel(mixed $value): string
    {
        $clean = $this->cleanText($value, 64);
        $key = strtolower(preg_replace('/[^a-z0-9]+/', ' ', $clean) ?? '');
        $key = trim(preg_replace('/\s+/', ' ', $key) ?? '');

        return match ($key) {
            '1', '1st', '1st year', 'first', 'first year' => 'First',
            '2', '2nd', '2nd year', 'second', 'second year' => 'Second',
            '3', '3rd', '3rd year', 'third', 'third year' => 'Third',
            '4', '4th', '4th year', 'fourth', 'fourth year', 'forth', 'forth year' => 'Fourth',
            default => $clean,
        };
    }

    private function normalizeGradeLevel(mixed $value): string
    {
        $clean = $this->cleanText($value, 64);
        $key = strtolower(preg_replace('/[^a-z0-9]+/', ' ', $clean) ?? '');
        $key = trim(preg_replace('/\s+/', ' ', $key) ?? '');

        return match ($key) {
            '11', 'grade 11', 'eleven' => 'Eleven',
            '12', 'grade 12', 'twelve' => 'Twelve',
            default => $clean,
        };
    }

    private function filterQueryParts(?string $srCode, ?string $college): array
    {
        $where = [];
        $params = [];
        $srCode = trim((string) $srCode);
        $college = trim((string) $college);

        if ($srCode !== '') {
            $where[] = 'UPPER(sr_code) LIKE UPPER(:sr_code)';
            $params['sr_code'] = '%' . $srCode . '%';
        }

        if ($college !== '') {
            $where[] = 'TRIM(college) = :college';
            $params['college'] = $college;
        }

        return [$where, $params];
    }
}
