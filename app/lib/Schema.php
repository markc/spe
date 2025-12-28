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

        $pass = Env::get('DB_PASS', '');
        $pass = file_exists($pass) ? trim(file_get_contents($pass)) : $pass;

        $pdo = new PDO(self::dsn($db, $type), Env::get('DB_USER'), $pass);
        $pdo->exec(file_get_contents($file));
    }

    public static function exists(string $db, ?string $type = null): bool
    {
        $type ??= Env::get('DB_TYPE', 'sqlite');

        return match ($type) {
            'sqlite' => file_exists(self::sqlitePath($db)),
            'mariadb' => self::mariadbExists($db),
            default => false,
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
        $dir = Env::get('SQLITE_DIR');
        if ($dir) {
            // Resolve relative paths from project root (where composer.json lives)
            if (!str_starts_with($dir, '/')) {
                $dir = dirname(__DIR__, 2) . '/' . $dir;
            }
        } else {
            $dir = __DIR__ . '/../sqlite';
        }

        // Add chapter prefix for isolation (e.g., "07-PDO-blog.db")
        $chapter = Env::get('_CHAPTER');
        $name = $chapter ? "{$chapter}-{$db}" : $db;

        return "$dir/$name.db";
    }

    private static function dsn(string $db, string $type): string
    {
        return match ($type) {
            'sqlite' => 'sqlite:' . self::sqlitePath($db),
            'mariadb' => 'mysql:' . (($sock = Env::get('DB_SOCK'))
                ? "unix_socket=$sock"
                : 'host=' . Env::get('DB_HOST', 'localhost') . ';port=' . Env::get('DB_PORT', '3306'))
                . ';dbname=' . Env::get("DB_{$db}_NAME", $db),
            default => throw new \RuntimeException("Unsupported DB type: $type"),
        };
    }

    private static function mariadbExists(string $db): bool
    {
        try {
            $pass = Env::get('DB_PASS', '');
            $pass = file_exists($pass) ? trim(file_get_contents($pass)) : $pass;
            new PDO(self::dsn($db, 'mariadb'), Env::get('DB_USER'), $pass);
            return true;
        } catch (\PDOException) {
            return false;
        }
    }
}
