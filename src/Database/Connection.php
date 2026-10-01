<?php

declare(strict_types=1);

namespace Kolya\Test\Database;

use PDO;

final class Connection
{
    public static function create(array $config): PDO
    {
        $pdo = new PDO(
            $config['dsn'],
            $config['user'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );

        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA synchronous = NORMAL');
            $pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $pdo->exec("SET time_zone = '+00:00'");
        }

        return $pdo;
    }

    public static function migrate(PDO $pdo, string $root): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $file = $root . '/database/schema.' . ($driver === 'sqlite' ? 'sqlite' : 'mysql') . '.sql';
        $sql = file_get_contents($file);

        if ($sql === false) {
            throw new \RuntimeException('Не вдалося прочитати схему бази даних.');
        }

        $pdo->exec($sql);
    }
}

