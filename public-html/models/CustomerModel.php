<?php

require_once __DIR__ . '/../database.php';

class CustomerModel
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM customers WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $customer = $stmt->fetch();
        return $customer ?: null;
    }

    public static function create(string $email, string $passwordHash, string $name, string $phone = null): bool
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO customers (email, password_hash, name, phone, created_at) VALUES (:email, :password_hash, :name, :phone, :created_at)'
        );

        return $stmt->execute([
            'email' => $email,
            'password_hash' => $passwordHash,
            'name' => $name,
            'phone' => $phone,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function updateLastLogin(string $email): bool
    {
        $stmt = Database::connect()->prepare('UPDATE customers SET last_login = :last_login WHERE email = :email');
        return $stmt->execute([
            'last_login' => date('Y-m-d H:i:s'),
            'email' => $email,
        ]);
    }

    public static function allByEmail(string $email): array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM requests WHERE email = :email ORDER BY created_at DESC');
        $stmt->execute(['email' => $email]);
        return $stmt->fetchAll();
    }
}
