<?php

namespace App\VotingSystem\Models;

use App\VotingSystem\Core\Model;

class Dashboard extends Model
{
    private const TRACKED_PROGRAMS = [
        'Bachelor of Science in Accountancy',
        'Bachelor of Science in Business Administration Major in Financial Management',
        'Bachelor of Science in Business Administration Major in Human Resource Management',
        'Bachelor of Science in Business Administration Major in Marketing Management',
        'Bachelor of Science in Hospitality Management',
        'Bachelor of Science in Management Accounting',
        'Bachelor of Science in Tourism Management',
        'Bachelor of Arts in Communication',
        'Bachelor of Science in Fisheries and Aquatic Sciences',
        'Bachelor of Science in Food Technology',
        'Bachelor of Science in Psychology',
        'Bachelor of Science in Criminology',
        'Bachelor of Science in Nursing',
        'Bachelor of Science in Nutrition and Diatetics',
        'Bachelor of Science in Information Technology',
        'Bachelor of Science in Information Technology Major in Business Analytics Track',
        'Bachelor of Science in Information Technology Major in Network Technology Track',
        'Bachelor of Elementary Education',
        'Bachelor of Physical Education',
        'Bachelor of Secondary Education Major in English',
        'Bachelor of Secondary Education Major in Filipino',
        'Bachelor of Secondary Education Major in Mathematics',
        'Bachelor of Secondary Education Major in Science',
        'Bachelor of Secondary Education Major in Social Studies',
    ];

    public function summary(int $electionId): array
    {
        $voterRow = $this->db()->query(
            'SELECT
                COUNT(*) AS total_voters,
                SUM(CASE WHEN has_voted = 1 THEN 1 ELSE 0 END) AS votes_cast
             FROM voters'
        )->fetch();

        $totalVoters = (int) ($voterRow['total_voters'] ?? 0);
        $votesCast = (int) ($voterRow['votes_cast'] ?? 0);

        $electionStatement = $this->db()->prepare(
            'SELECT
                (SELECT COUNT(*) FROM positions WHERE election_id = :eid_positions) AS positions,
                (SELECT COUNT(*) FROM candidates c
                    INNER JOIN positions p ON p.id = c.position_id
                    WHERE p.election_id = :eid_candidates) AS candidates'
        );
        $electionStatement->execute([
            'eid_positions' => $electionId,
            'eid_candidates' => $electionId,
        ]);
        $electionRow = $electionStatement->fetch();

        return [
            'total_voters' => $totalVoters,
            'votes_cast' => $votesCast,
            'turnout_rate' => $totalVoters > 0 ? round(($votesCast / $totalVoters) * 100, 2) : 0,
            'positions' => (int) ($electionRow['positions'] ?? 0),
            'candidates' => (int) ($electionRow['candidates'] ?? 0),
        ];
    }

    public function turnoutByCollege(): array
    {
        $statement = $this->db()->query(
            'SELECT TRIM(college) AS college,
                    COUNT(*) AS total_voters,
                    SUM(CASE WHEN has_voted = 1 THEN 1 ELSE 0 END) AS votes_cast
             FROM voters
             WHERE TRIM(COALESCE(college, \'\')) <> \'\'
             GROUP BY TRIM(college)
             ORDER BY TRIM(college)'
        );

        $rows = array_map(function (array $row): array {
            $row['total_voters'] = (int) $row['total_voters'];
            $row['votes_cast'] = (int) $row['votes_cast'];
            $row['turnout_rate'] = $row['total_voters'] > 0
                ? round(($row['votes_cast'] / $row['total_voters']) * 100, 2)
                : 0;

            return $row;
        }, $statement->fetchAll());

        usort($rows, static function (array $left, array $right): int {
            return \college_sort_rank($left['college'] ?? '') <=> \college_sort_rank($right['college'] ?? '')
                ?: strcmp(\college_abbreviation($left['college'] ?? ''), \college_abbreviation($right['college'] ?? ''));
        });

        return $rows;
    }

    public function turnoutByProgram(): array
    {
        $gradeBuckets = [
            'Eleven' => ['11', 'grade 11', 'eleven'],
            'Twelve' => ['12', 'grade 12', 'twelve'],
        ];

        $rows = array_fill_keys(self::TRACKED_PROGRAMS, [
            'program' => '',
            'total_voters' => 0,
            'votes_cast' => 0,
            'turnout_rate' => 0,
            'grade_counts' => [
                'Eleven' => 0,
                'Twelve' => 0,
            ],
        ]);

        foreach (array_keys($rows) as $program) {
            $rows[$program]['program'] = $program;
        }

        $statement = $this->db()->query(
            'SELECT TRIM(program) AS program,
                    TRIM(COALESCE(grade_level, \'\')) AS grade_level,
                    COUNT(*) AS total_voters,
                    SUM(CASE WHEN has_voted = 1 THEN 1 ELSE 0 END) AS votes_cast
             FROM voters
             WHERE TRIM(COALESCE(program, \'\')) <> \'\'
             GROUP BY TRIM(program), TRIM(COALESCE(grade_level, \'\'))'
        );

        foreach ($statement->fetchAll() as $row) {
            $program = (string) ($row['program'] ?? '');

            if (!isset($rows[$program])) {
                continue;
            }

            $totalVoters = (int) ($row['total_voters'] ?? 0);
            $votesCast = (int) ($row['votes_cast'] ?? 0);
            $rows[$program]['total_voters'] += $totalVoters;
            $rows[$program]['votes_cast'] += $votesCast;

            $normalizedLevel = strtolower(trim((string) ($row['grade_level'] ?? '')));
            foreach ($gradeBuckets as $label => $aliases) {
                if (in_array($normalizedLevel, $aliases, true)) {
                    $rows[$program]['grade_counts'][$label] += $votesCast;
                    break;
                }
            }
        }

        return array_values(array_map(static function (array $row): array {
            $row['turnout_rate'] = $row['total_voters'] > 0
                ? round(($row['votes_cast'] / $row['total_voters']) * 100, 2)
                : 0;

            return $row;
        }, $rows));
    }

    public function programOptionsByCollege(): array
    {
        $statement = $this->db()->query(
            'SELECT TRIM(college) AS college,
                    TRIM(program) AS program
             FROM voters
             WHERE TRIM(COALESCE(program, \'\')) <> \'\'
             GROUP BY TRIM(college), TRIM(program)
             ORDER BY TRIM(college), TRIM(program)'
        );

        return array_values(array_filter($statement->fetchAll(), static function (array $row): bool {
            return trim((string) ($row['program'] ?? '')) !== '';
        }));
    }

    public function turnoutByGradeLevel(): array
    {
        $summary = [
            'Eleven' => ['total_voters' => 0, 'votes_cast' => 0],
            'Twelve' => ['total_voters' => 0, 'votes_cast' => 0],
        ];

        $statement = $this->db()->query(
            'SELECT TRIM(COALESCE(grade_level, \'\')) AS grade_level,
                    COUNT(*) AS total_voters,
                    SUM(CASE WHEN has_voted = 1 THEN 1 ELSE 0 END) AS votes_cast
             FROM voters
             GROUP BY TRIM(COALESCE(grade_level, \'\'))'
        );

        foreach ($statement->fetchAll() as $row) {
            $level = strtolower(trim((string) ($row['grade_level'] ?? '')));
            $target = null;

            if (in_array($level, ['11', 'grade 11', 'eleven'], true)) {
                $target = 'Eleven';
            } elseif (in_array($level, ['12', 'grade 12', 'twelve'], true)) {
                $target = 'Twelve';
            }

            if ($target === null) {
                continue;
            }

            $summary[$target]['total_voters'] += (int) ($row['total_voters'] ?? 0);
            $summary[$target]['votes_cast'] += (int) ($row['votes_cast'] ?? 0);
        }

        return $summary;
    }

    public function results(int $electionId, ?string $college = null, ?string $positionTitle = null, ?string $yearLevel = null, ?string $program = null): array
    {
        $positions = (new Position())->forElection($electionId);
        $college = trim((string) $college);
        $program = trim((string) $program);
        $yearLevel = trim((string) $yearLevel);

        if ($positionTitle) {
            $positions = array_values(array_filter($positions, function ($p) use ($positionTitle) {
                return $p['title'] === $positionTitle;
            }));
        }

        $sql = 'SELECT c.id, COUNT(v.id) AS vote_count FROM candidates c ';
        $params = [];

        $sql .= 'LEFT JOIN (
            SELECT v.* FROM votes v
            INNER JOIN voters vt ON vt.id = v.voter_id
            WHERE v.election_id = :filtered_election_id';

        $params['filtered_election_id'] = $electionId;
        $sql .= $this->voterFilterSql($college, $program, $yearLevel, 'vt', $params, 'vote_filter');
        $sql .= '
        ) v ON v.candidate_id = c.id ';

        $sql .= 'GROUP BY c.id';

        $statement = $this->db()->prepare($sql);
        $statement->execute($params);
        $counts = [];

        foreach ($statement->fetchAll() as $row) {
            $counts[(int) $row['id']] = (int) $row['vote_count'];
        }

        $submittedBallots = $this->submittedBallotCount($electionId, $college, $program, $yearLevel);
        $votedByPosition = $this->votedPositionCounts($electionId, $college, $program, $yearLevel);

        foreach ($positions as &$position) {
            $maxCandidateVotes = 0;

            foreach ($position['candidates'] as &$candidate) {
                $candidate['vote_count'] = $counts[(int) $candidate['id']] ?? 0;
                $maxCandidateVotes = max($maxCandidateVotes, (int) $candidate['vote_count']);
            }

            foreach ($position['candidates'] as &$candidate) {
                $candidate['vote_percent'] = $submittedBallots > 0
                    ? round(((int) $candidate['vote_count'] / $submittedBallots) * 100, 2)
                    : 0;
                $candidate['is_abstain'] = false;
            }

            unset($candidate);

            if (!empty($position['candidates'])) {
                $positionId = (int) ($position['id'] ?? 0);
                $abstainCount = max(0, $submittedBallots - (int) ($votedByPosition[$positionId] ?? 0));
                $abstainPercent = $submittedBallots > 0 ? round(($abstainCount / $submittedBallots) * 100, 2) : 0;
                $position['submitted_ballots'] = $submittedBallots;
                $position['abstain_count'] = $abstainCount;
                $position['abstain_percent'] = $abstainPercent;
                $position['abstain_leads'] = $submittedBallots > 0 && $abstainCount > $maxCandidateVotes;
                $position['candidates'][] = [
                    'id' => 'abstain-' . $positionId,
                    'name' => 'Abstain',
                    'party' => 'No candidate selected',
                    'vote_count' => $abstainCount,
                    'vote_percent' => $abstainPercent,
                    'is_abstain' => true,
                ];
            }
        }
        unset($position);

        return $positions;
    }

    private function submittedBallotCount(int $electionId, string $college, string $program, string $yearLevel): int
    {
        $params = ['receipt_election_id' => $electionId];
        $sql = 'SELECT COUNT(*)
                FROM vote_receipts vr
                INNER JOIN voters vt ON vt.id = vr.voter_id
                WHERE vr.election_id = :receipt_election_id';
        $sql .= $this->voterFilterSql($college, $program, $yearLevel, 'vt', $params, 'receipt_filter');

        $statement = $this->db()->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    private function votedPositionCounts(int $electionId, string $college, string $program, string $yearLevel): array
    {
        $params = ['position_vote_election_id' => $electionId];
        $sql = 'SELECT v.position_id, COUNT(DISTINCT v.voter_id) AS voter_count
                FROM votes v
                INNER JOIN voters vt ON vt.id = v.voter_id
                WHERE v.election_id = :position_vote_election_id';
        $sql .= $this->voterFilterSql($college, $program, $yearLevel, 'vt', $params, 'position_vote_filter');
        $sql .= ' GROUP BY v.position_id';

        $statement = $this->db()->prepare($sql);
        $statement->execute($params);
        $counts = [];

        foreach ($statement->fetchAll() as $row) {
            $counts[(int) $row['position_id']] = (int) $row['voter_count'];
        }

        return $counts;
    }

    private function voterFilterSql(string $college, string $program, string $yearLevel, string $alias, array &$params, string $prefix): string
    {
        $sql = '';

        if ($college !== '') {
            $key = $prefix . '_college';
            $sql .= ' AND TRIM(' . $alias . '.college) = :' . $key;
            $params[$key] = $college;
        }

        if ($program !== '') {
            $key = $prefix . '_program';
            $sql .= ' AND TRIM(COALESCE(' . $alias . '.program, \'\')) = :' . $key;
            $params[$key] = $program;
        }

        if ($yearLevel !== '') {
            $aliases = $this->yearLevelAliases($yearLevel);
            $placeholders = [];

            foreach ($aliases as $index => $yearAlias) {
                $key = $prefix . '_year_level_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $yearAlias;
            }

            $sql .= ' AND LOWER(TRIM(COALESCE(' . $alias . '.year_level, \'\'))) IN (' . implode(', ', $placeholders) . ')';
        }

        return $sql;
    }

    private function yearLevelAliases(string $yearLevel): array
    {
        $normalized = strtolower(trim($yearLevel));
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? '';
        $normalized = trim(preg_replace('/\s+/', ' ', $normalized) ?? '');

        $aliases = [
            'first' => ['1', '1st', '1st year', 'first', 'first year'],
            'second' => ['2', '2nd', '2nd year', 'second', 'second year'],
            'third' => ['3', '3rd', '3rd year', 'third', 'third year'],
            'fourth' => ['4', '4th', '4th year', 'fourth', 'fourth year', 'forth', 'forth year'],
        ];

        return $aliases[$normalized] ?? [$normalized];
    }

    public function recentVotes(int $limit = 8): array
    {
        $statement = $this->db()->prepare(
            'SELECT sr_code, full_name, college, program, voted_at
             FROM voters
             WHERE has_voted = 1
             ORDER BY voted_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function recentActivity(int $limit = 6): array
    {
        $statement = $this->db()->prepare(
            'SELECT actor_name, action, details, created_at
             FROM audit_logs
             ORDER BY created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
