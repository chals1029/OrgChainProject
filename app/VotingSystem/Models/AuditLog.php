<?php

namespace App\VotingSystem\Models;

use App\VotingSystem\Core\Auth;
use App\VotingSystem\Core\Model;

class AuditLog extends Model
{
    public function record(string $action, string $details = ''): void
    {
        $user = Auth::user();
        $statement = $this->db()->prepare(
            'INSERT INTO audit_logs (user_id, actor_name, action, details, created_at)
             VALUES (:user_id, :actor_name, :action, :details, :created_at)'
        );
        $statement->execute([
            'user_id' => $user['id'] ?? null,
            'actor_name' => $user['name'] ?? 'Public voter',
            'action' => $action,
            'details' => $details,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
