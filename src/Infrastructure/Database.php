<?php
declare(strict_types=1);
namespace App\Infrastructure;

use App\Config;
use PDO;

class Database
{
    public static function pdo(): PDO
    {
        $dsn = Config::dbDsn();
        $user = Config::dbUser();
        $pass = Config::dbPass();
        $pdo = new PDO($dsn, $user ?? null, $pass ?? null);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if (str_starts_with($dsn, 'sqlite:')) {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }
        return $pdo;
    }

    public static function migrate(): void
    {
        $pdo = self::pdo();
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $pdo->exec('CREATE TABLE IF NOT EXISTS contacts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                address TEXT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )');
            $pdo->exec('CREATE TABLE IF NOT EXISTS phones (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                contact_id INTEGER NOT NULL,
                number TEXT NOT NULL,
                label TEXT NULL,
                FOREIGN KEY(contact_id) REFERENCES contacts(id) ON DELETE CASCADE
            )');
        } else {
            $pdo->exec('CREATE TABLE IF NOT EXISTS contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                address TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )');
            $pdo->exec('CREATE TABLE IF NOT EXISTS phones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contact_id INT NOT NULL,
                number VARCHAR(64) NOT NULL,
                label VARCHAR(64) NULL,
                CONSTRAINT fk_contact FOREIGN KEY(contact_id) REFERENCES contacts(id) ON DELETE CASCADE
            )');
        }
    }
}
