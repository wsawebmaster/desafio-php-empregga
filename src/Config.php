<?php
declare(strict_types=1);

namespace App;

class Config
{
    public const DB_FILE = __DIR__ . '/../data/contacts.sqlite';

    public static function dbDsn(): string
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $name = getenv('DB_NAME') ?: 'contacts';
        return "mysql:host={$host};dbname={$name};charset=utf8mb4";
    }

    public static function dbUser(): ?string
    {
        return getenv('DB_USER') ?: 'root';
    }

    public static function dbPass(): ?string
    {
        return getenv('DB_PASS') ?: '';
    }
}
