<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = \config_value('database.host');
        $name = \config_value('database.name');
        $charset = \config_value('database.charset', 'utf8mb4');
        $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";

        self::$pdo = new PDO($dsn, (string) \config_value('database.user'), (string) \config_value('database.password'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }
}
