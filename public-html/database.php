<?php

/**
 * Database connection helper for future SQL migration.
 *
 * This file is a scaffold for replacing CSV storage with a PDO-backed database.
 */
class Database
{
    private static $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $db   = getenv('DB_NAME') ?: 'cleaning_service';
        $user = getenv('DB_USER') ?: 'dbuser';
        $pass = getenv('DB_PASS') ?: 'dbpass';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        self::$pdo = new PDO($dsn, $user, $pass, $options);
        return self::$pdo;
    }

    public static function migrate(): void
    {
        $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
        if ($sql === false) {
            throw new RuntimeException('Unable to load database schema.');
        }

        self::connect()->exec($sql);
    }
}
