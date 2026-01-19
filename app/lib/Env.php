<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\App;

final class Env
{
    private static array $v = [];

    public static function load(string $chapter = ''): void
    {
        // Resolve relative paths (e.g., "07-PDO/public/.." → "07-PDO")
        $chapter = $chapter ? (realpath($chapter) ?: $chapter) : '';
        $root = $chapter ? dirname($chapter) : dirname(__DIR__, 2);

        // Store chapter name for database prefixing (e.g., "07-PDO" from path)
        if ($chapter) {
            self::$v['_CHAPTER'] = basename($chapter);
        }

        // Cascade: global -> global.local -> chapter -> chapter.local
        [$root . '/.env', $root . '/.env.local']
            |> (static fn($f) => $chapter ? [...$f, "$chapter/.env", "$chapter/.env.local"] : $f)
            |> (static fn($files) => array_map(self::parse(...), array_filter($files, 'file_exists')));
    }

    public static function get(string $k, string $d = ''): string
    {
        return self::$v[$k] ?? getenv($k) ?: $_ENV[$k] ?? $d;
    }

    public static function bool(string $k, bool $d = false): bool
    {
        $v = self::get($k);
        return $v === '' ? $d : in_array(strtolower($v), ['true', '1', 'yes', 'on'], true);
    }

    public static function int(string $k, int $d = 0): int
    {
        return (int) (self::get($k) ?: $d);
    }

    private static function parse(string $path): void
    {
        file_get_contents($path)
            |> (static fn($s) => explode("\n", $s))
            |> (static fn($lines) => array_filter(
                $lines,
                static fn($l) => ($l = trim($l)) && $l[0] !== '#' && str_contains($l, '='),
            ))
            |> (static fn($lines) => array_map(static function ($l) {
                [$k, $v] = explode('=', $l, 2) + [1 => ''];
                self::$v[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
            }, $lines));
    }
}
