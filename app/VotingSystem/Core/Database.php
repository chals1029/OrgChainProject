<?php

namespace App\VotingSystem\Core;

use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;

class Database
{
    /**
     * Bump this when you add a new auto-migration so it re-runs once.
     */
    private const SCHEMA_VERSION = '2026.07.24.1';

    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        try {
            // Prefer Laravel's default connection (MySQL or SQLite).
            self::$connection = DB::connection()->getPdo();
        } catch (\Throwable $exception) {
            // Fallback to voting-specific MySQL settings from config/voting.php
            self::$connection = self::mysqlFromVotingConfig();
        }

        self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        self::runSchemaMigrationsOnce(self::$connection);

        return self::$connection;
    }

    private static function mysqlFromVotingConfig(): PDO
    {
        $host = voting_config('database.host', '127.0.0.1');
        $port = voting_config('database.port', '3306');
        $database = voting_config('database.name', 'votingsystem');
        $charset = voting_config('database.charset', 'utf8mb4');
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        try {
            $pdo = new PDO($dsn, voting_config('database.username', 'root'), voting_config('database.password', ''), [
                PDO::ATTR_PERSISTENT => false,
            ]);
        } catch (PDOException $exception) {
            http_response_code(500);
            error_log('Database connection failed: '.$exception->getMessage());
            exit('Database connection failed. Please contact the system administrator.');
        }

        return $pdo;
    }

    private static function runSchemaMigrationsOnce(PDO $pdo): void
    {
        $marker = storage_path('app/voting/schema.lock');

        if (is_file($marker) && trim((string) @file_get_contents($marker)) === self::SCHEMA_VERSION) {
            return;
        }

        // If tables already exist (imported DB), only ensure additive columns/indexes.
        if (self::tableExists($pdo, 'admin_users')) {
            self::ensureSecurityConstraints($pdo);
            self::ensureVotersProgramColumn($pdo);
            self::ensureCandidateImageColumns($pdo);
            self::ensureElectionBallotContentColumns($pdo);
            self::ensureElectionAnnouncementColumns($pdo);
            self::ensureVoteChainColumns($pdo);
            self::ensurePerformanceIndexes($pdo);
        }

        if (! is_dir(dirname($marker))) {
            @mkdir(dirname($marker), 0775, true);
        }

        @file_put_contents($marker, self::SCHEMA_VERSION);
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table) ?: $table;

        if ($driver === 'sqlite') {
            $statement = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name = ".$pdo->quote($table));

            return (bool) $statement->fetchColumn();
        }

        // MySQL does not support bound params in SHOW TABLES LIKE.
        $statement = $pdo->query('SHOW TABLES LIKE '.$pdo->quote($table));

        return (bool) $statement->fetchColumn();
    }

    private static function ensureSecurityConstraints(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return;
        }

        self::ensureMysqlIndex($pdo, 'vote_receipts', 'uniq_vote_receipts_election_voter', 'ALTER TABLE vote_receipts ADD UNIQUE KEY uniq_vote_receipts_election_voter (election_id, voter_id)');
        self::ensureMysqlIndex($pdo, 'votes', 'uniq_votes_choice', 'ALTER TABLE votes ADD UNIQUE KEY uniq_votes_choice (election_id, voter_id, position_id, candidate_id)');
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS security_events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                user_agent VARCHAR(512),
                method VARCHAR(10),
                path VARCHAR(255) NOT NULL,
                event_type VARCHAR(100) NOT NULL,
                severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
                request_count INT NOT NULL DEFAULT 0,
                details TEXT,
                created_at DATETIME NOT NULL,
                INDEX idx_security_events_created (created_at),
                INDEX idx_security_events_ip_created (ip_address, created_at),
                INDEX idx_security_events_type_created (event_type, created_at)
            )"
        );
    }

    private static function ensurePerformanceIndexes(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return;
        }

        $indexes = [
            ['voters', 'idx_voters_has_voted', 'ALTER TABLE voters ADD INDEX idx_voters_has_voted (has_voted)'],
            ['voters', 'idx_voters_voted_at', 'ALTER TABLE voters ADD INDEX idx_voters_voted_at (voted_at)'],
            ['voters', 'idx_voters_college', 'ALTER TABLE voters ADD INDEX idx_voters_college (college)'],
            ['voters', 'idx_voters_program', 'ALTER TABLE voters ADD INDEX idx_voters_program (program)'],
            ['voters', 'idx_voters_grade_level', 'ALTER TABLE voters ADD INDEX idx_voters_grade_level (grade_level)'],
            ['audit_logs', 'idx_audit_logs_created_at', 'ALTER TABLE audit_logs ADD INDEX idx_audit_logs_created_at (created_at)'],
            ['candidates', 'idx_candidates_position', 'ALTER TABLE candidates ADD INDEX idx_candidates_position (position_id, sort_order)'],
            ['positions', 'idx_positions_election', 'ALTER TABLE positions ADD INDEX idx_positions_election (election_id, sort_order)'],
            ['votes', 'idx_votes_election_candidate', 'ALTER TABLE votes ADD INDEX idx_votes_election_candidate (election_id, candidate_id)'],
        ];

        foreach ($indexes as [$table, $index, $sql]) {
            self::ensureMysqlIndex($pdo, $table, $index, $sql);
        }
    }

    private static function ensureElectionBallotContentColumns(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return;
        }

        if (! self::hasMysqlColumn($pdo, 'elections', 'ballot_card_kicker')) {
            $pdo->exec('ALTER TABLE elections ADD COLUMN ballot_card_kicker VARCHAR(255) NULL');
        }

        if (! self::hasMysqlColumn($pdo, 'elections', 'ballot_card_heading')) {
            $pdo->exec('ALTER TABLE elections ADD COLUMN ballot_card_heading VARCHAR(512) NULL');
        }

        if (! self::hasMysqlColumn($pdo, 'elections', 'ballot_card_body')) {
            $pdo->exec('ALTER TABLE elections ADD COLUMN ballot_card_body TEXT NULL');
        }

        if (! self::hasMysqlColumn($pdo, 'elections', 'ballot_card_image_path')) {
            $pdo->exec('ALTER TABLE elections ADD COLUMN ballot_card_image_path VARCHAR(512) NULL');
        }
    }

    private static function ensureCandidateImageColumns(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return;
        }

        if (! self::hasMysqlColumn($pdo, 'candidates', 'image_blob')) {
            $pdo->exec('ALTER TABLE candidates ADD COLUMN image_blob MEDIUMBLOB NULL AFTER image_path');
        }

        if (! self::hasMysqlColumn($pdo, 'candidates', 'image_mime')) {
            $pdo->exec('ALTER TABLE candidates ADD COLUMN image_mime VARCHAR(100) NULL AFTER image_blob');
        }
    }

    private static function ensureElectionAnnouncementColumns(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return;
        }

        if (! self::hasMysqlColumn($pdo, 'elections', 'announcement')) {
            $pdo->exec('ALTER TABLE elections ADD COLUMN announcement TEXT NULL AFTER instructions');
        }

        if (! self::hasMysqlColumn($pdo, 'elections', 'announcement_expires_at')) {
            $pdo->exec('ALTER TABLE elections ADD COLUMN announcement_expires_at DATETIME NULL AFTER announcement');
        }
    }

    private static function ensureVotersProgramColumn(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return;
        }

        $hasDepartment = self::hasMysqlColumn($pdo, 'voters', 'department');
        $hasProgram = self::hasMysqlColumn($pdo, 'voters', 'program');

        if ($hasDepartment && ! $hasProgram) {
            $pdo->exec('ALTER TABLE voters CHANGE department program VARCHAR(255) NULL');
        }
    }

    private static function ensureVoteChainColumns(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return;
        }

        if (! self::tableExists($pdo, 'vote_receipts')) {
            return;
        }

        $columns = [
            'previous_hash' => 'ALTER TABLE vote_receipts ADD COLUMN previous_hash VARCHAR(64) NULL AFTER reference_code',
            'block_hash' => 'ALTER TABLE vote_receipts ADD COLUMN block_hash VARCHAR(64) NULL AFTER previous_hash',
            'ballot_root' => 'ALTER TABLE vote_receipts ADD COLUMN ballot_root VARCHAR(64) NULL AFTER block_hash',
            'voter_commitment' => 'ALTER TABLE vote_receipts ADD COLUMN voter_commitment VARCHAR(64) NULL AFTER ballot_root',
            'nodes_confirmed' => 'ALTER TABLE vote_receipts ADD COLUMN nodes_confirmed TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER voter_commitment',
            'node_confirmations' => 'ALTER TABLE vote_receipts ADD COLUMN node_confirmations JSON NULL AFTER nodes_confirmed',
        ];

        foreach ($columns as $column => $sql) {
            if (! self::hasMysqlColumn($pdo, 'vote_receipts', $column)) {
                try {
                    $pdo->exec($sql);
                } catch (\Throwable) {
                    // ignore if already present / unsupported
                }
            }
        }

        self::ensureMysqlIndex(
            $pdo,
            'vote_receipts',
            'idx_vote_receipts_block_hash',
            'ALTER TABLE vote_receipts ADD INDEX idx_vote_receipts_block_hash (block_hash)'
        );
    }

    private static function hasMysqlColumn(PDO $pdo, string $table, string $column): bool
    {
        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = :table
               AND column_name = :column'
        );
        $statement->execute([
            'table' => $table,
            'column' => $column,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    private static function ensureMysqlIndex(PDO $pdo, string $table, string $index, string $sql): void
    {
        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = :table
               AND index_name = :index'
        );
        $statement->execute([
            'table' => $table,
            'index' => $index,
        ]);

        if ((int) $statement->fetchColumn() === 0) {
            try {
                $pdo->exec($sql);
            } catch (\Throwable) {
                // ignore if already present / unsupported
            }
        }
    }
}
