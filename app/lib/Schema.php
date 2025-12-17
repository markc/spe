<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\App;

use PDO;

final class Schema
{
    private const string DIR = __DIR__ . '/../schema';

    public static function init(string $db, ?string $type = null): void
    {
        $type ??= Env::get('DB_TYPE', 'sqlite');
        $file = self::DIR . "/$db.$type.sql";

        if (!file_exists($file)) {
            throw new \RuntimeException("Schema not found: $file");
        }

        $pdo = new PDO(self::dsn($db, $type), Env::get('DB_USER'), Env::get('DB_PASS'));
        $pdo->exec(file_get_contents($file));
    }

    public static function exists(string $db, ?string $type = null): bool
    {
        $type ??= Env::get('DB_TYPE', 'sqlite');

        return match ($type) {
            'sqlite' => file_exists(self::sqlitePath($db)),
            default => self::mysqlExists($db),
        };
    }

    public static function path(string $db): string
    {
        return Env::get('DB_TYPE', 'sqlite') === 'sqlite'
            ? self::sqlitePath($db)
            : Env::get("DB_{$db}_NAME", $db);
    }

    private static function sqlitePath(string $db): string
    {
        $dir = Env::get('SQLITE_DIR', __DIR__ . '/../sqlite');
        return "$dir/$db.db";
    }

    private static function dsn(string $db, string $type): string
    {
        return match ($type) {
            'sqlite' => 'sqlite:' . self::sqlitePath($db),
            default => sprintf(
                'mysql:host=%s;port=%s;dbname=%s',
                Env::get('DB_HOST', 'localhost'),
                Env::get('DB_PORT', '3306'),
                Env::get("DB_{$db}_NAME", $db)
            ),
        };
    }

    private static function mysqlExists(string $db): bool
    {
        try {
            new PDO(self::dsn($db, 'mariadb'), Env::get('DB_USER'), Env::get('DB_PASS'));
            return true;
        } catch (\PDOException) {
            return false;
        }
    }
}
