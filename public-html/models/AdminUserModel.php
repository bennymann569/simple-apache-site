<?php

require_once __DIR__ . '/../database.php';

class AdminUserModel
{
    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM admin_users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(string $username, string $passwordHash, string $role = 'admin'): bool
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO admin_users (username, password_hash, role, created_at) VALUES (:username, :password_hash, :role, :created_at)'
        );

        return $stmt->execute([
            'username' => $username,
            'password_hash' => $passwordHash,
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function updateLastLogin(string $username): bool
    {
        $stmt = Database::connect()->prepare(
            'UPDATE admin_users SET last_login = :last_login WHERE username = :username'
        );

        return $stmt->execute([
            'last_login' => date('Y-m-d H:i:s'),
            'username' => $username,
        ]);
    }

    public static function all(): array
    {
        $stmt = Database::connect()->query('SELECT * FROM admin_users ORDER BY id ASC');
        return $stmt->fetchAll();
    }

    public static function delete(string $username): bool
    {
        $stmt = Database::connect()->prepare('DELETE FROM admin_users WHERE username = :username');
        return $stmt->execute(['username' => $username]);
    }
}
