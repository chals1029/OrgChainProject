<?php

namespace App\VotingSystem\Models;

use App\VotingSystem\Core\Model;

class AdminUser extends Model
{
    public function find(int $id): ?array
    {
        $statement = $this->db()->prepare('SELECT * FROM admin_users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db()->prepare('SELECT * FROM admin_users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => strtolower(trim($email))]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function canvassingAccounts(): array
    {
        $statement = $this->db()->query(
            "SELECT id, name, email, role, is_active, created_at
             FROM admin_users
             WHERE role IN ('canvassing', 'view_only')
             ORDER BY is_active DESC, role ASC, name ASC"
        );

        return $statement->fetchAll();
    }

    public function createStaffAccount(array $fields): int
    {
        $statement = $this->db()->prepare(
            'INSERT INTO admin_users (name, email, password_hash, role, is_active)
             VALUES (:name, :email, :password_hash, :role, :is_active)'
        );
        $statement->execute([
            'name' => trim((string) $fields['name']),
            'email' => strtolower(trim((string) $fields['email'])),
            'password_hash' => password_hash((string) $fields['password'], PASSWORD_DEFAULT),
            'role' => $fields['role'],
            'is_active' => (int) ($fields['is_active'] ?? 1),
        ]);

        return (int) $this->db()->lastInsertId();
    }

    public function updateStaffAccount(int $id, array $fields): void
    {
        $sql = 'UPDATE admin_users SET name = :name, email = :email, role = :role, is_active = :is_active';
        $params = [
            'id' => $id,
            'name' => trim((string) $fields['name']),
            'email' => strtolower(trim((string) $fields['email'])),
            'role' => $fields['role'],
            'is_active' => (int) ($fields['is_active'] ?? 0),
        ];

        if (!empty($fields['password'])) {
            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = password_hash((string) $fields['password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = :id AND role IN ('canvassing', 'view_only')";
        $statement = $this->db()->prepare($sql);
        $statement->execute($params);
    }

    public function deleteStaffAccount(int $id): bool
    {
        $statement = $this->db()->prepare(
            "DELETE FROM admin_users
             WHERE id = :id AND role IN ('canvassing', 'view_only')"
        );
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }
}
