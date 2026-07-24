<?php

namespace App\VotingSystem\Models;

use App\VotingSystem\Core\Model;

class SecurityEvent extends Model
{
    public function record(array $data): void
    {
        $statement = $this->db()->prepare(
            'INSERT INTO security_events
                (ip_address, user_agent, method, path, event_type, severity, request_count, details, created_at)
             VALUES
                (:ip_address, :user_agent, :method, :path, :event_type, :severity, :request_count, :details, :created_at)'
        );

        $statement->execute([
            'ip_address' => substr((string) ($data['ip_address'] ?? 'unknown'), 0, 45),
            'user_agent' => substr((string) ($data['user_agent'] ?? ''), 0, 512),
            'method' => substr((string) ($data['method'] ?? ''), 0, 10),
            'path' => substr((string) ($data['path'] ?? ''), 0, 255),
            'event_type' => substr((string) ($data['event_type'] ?? 'suspicious_request'), 0, 100),
            'severity' => $this->severity((string) ($data['severity'] ?? 'medium')),
            'request_count' => max(0, (int) ($data['request_count'] ?? 0)),
            'details' => substr((string) ($data['details'] ?? ''), 0, 1000),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function summary(int $hours = 24): array
    {
        $since = date('Y-m-d H:i:s', time() - ($hours * 3600));

        $total = $this->scalar(
            'SELECT COUNT(*) FROM security_events WHERE created_at >= :since',
            ['since' => $since]
        );
        $blocked = $this->scalar(
            "SELECT COUNT(*) FROM security_events WHERE created_at >= :since AND event_type LIKE '%blocked%'",
            ['since' => $since]
        );
        $highRisk = $this->scalar(
            "SELECT COUNT(*) FROM security_events WHERE created_at >= :since AND severity IN ('high', 'critical')",
            ['since' => $since]
        );
        $uniqueIps = $this->scalar(
            'SELECT COUNT(DISTINCT ip_address) FROM security_events WHERE created_at >= :since',
            ['since' => $since]
        );

        return [
            'total' => $total,
            'blocked' => $blocked,
            'high_risk' => $highRisk,
            'unique_ips' => $uniqueIps,
        ];
    }

    public function recent(int $limit = 100): array
    {
        $statement = $this->db()->prepare(
            'SELECT * FROM security_events ORDER BY created_at DESC, id DESC LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function topSources(int $hours = 24, int $limit = 10): array
    {
        $statement = $this->db()->prepare(
            'SELECT ip_address, COUNT(*) AS event_count, MAX(created_at) AS last_seen
             FROM security_events
             WHERE created_at >= :since
             GROUP BY ip_address
             ORDER BY event_count DESC, last_seen DESC
             LIMIT :limit'
        );
        $statement->bindValue('since', date('Y-m-d H:i:s', time() - ($hours * 3600)));
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function scalar(string $sql, array $params): int
    {
        $statement = $this->db()->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    private function severity(string $severity): string
    {
        $severity = strtolower($severity);

        return in_array($severity, ['low', 'medium', 'high', 'critical'], true) ? $severity : 'medium';
    }
}
