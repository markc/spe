<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Lib;

/**
 * NS 3.0 Global Configuration
 *
 * Loads configuration from .env file at project root.
 * Falls back to sensible defaults if not set.
 */
final class Config
{
    private static bool $envLoaded = false;

    // === Defaults ===
    private const array DEFAULTS = [
        'SYSADM_DB' => '/srv/.local/hcp/sysadm.db',
        'HCP_DB' => '/srv/.local/hcp/hcp.db',
        'SSH_HOSTS_DIR' => '~/.ssh/hosts',
        'SSH_KEYS_DIR' => '~/.ssh/keys',
        'TARGET_HOST' => '',
        'VPATH' => '/srv',
        'ADMIN' => 'sysadm',
    ];

    // === Cached Values ===
    private static ?string $hostname = null;
    private static ?string $projectRoot = null;

    /**
     * Load .env file from project root.
     */
    public static function loadEnv(): void
    {
        if (self::$envLoaded)
            return;
        self::$envLoaded = true;

        $file = self::projectRoot() . '/.env';
        if (!file_exists($file))
            return;

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#'))
                continue;
            if (!str_contains($line, '='))
                continue;

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim(trim($value), '"\'');

            // Expand ~ to HOME
            if (str_starts_with($value, '~/')) {
                $value = getenv('HOME') . substr($value, 1);
            }

            // Don't override existing environment
            if (!getenv($key)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }

    /**
     * Get project root directory.
     */
    public static function projectRoot(): string
    {
        if (self::$projectRoot === null) {
            self::$projectRoot = dirname(__DIR__);
        }
        return self::$projectRoot;
    }

    /**
     * Get config value with fallback.
     */
    public static function get(string $key): string
    {
        self::loadEnv();
        return $_ENV[$key] ?? getenv($key) ?: self::DEFAULTS[$key] ?? '';
    }

    /**
     * Get primary hostname.
     */
    public static function hostname(): string
    {
        if (self::$hostname === null) {
            self::$hostname = trim(shell_exec('hostname -f 2>/dev/null') ?? '') ?: 'localhost';
        }
        return self::$hostname;
    }

    /**
     * Get target SSH host.
     * Priority: ENV > active vnode from DB > default
     */
    public static function targetHost(): string
    {
        self::loadEnv();

        // 1. Environment variable
        if ($host = getenv('TARGET_HOST')) {
            return $host;
        }

        // 2. Active vnode from database (if available)
        try {
            $db = new \PDO('sqlite:' . self::sysadmDb(), null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $stmt = $db->query('SELECT hostname FROM vnodes WHERE is_active = 1 LIMIT 1');
            if ($row = $stmt->fetch()) {
                return $row['hostname'];
            }
        } catch (\Exception) {
            // DB not available, use default
        }

        // 3. Default
        return self::DEFAULTS['TARGET_HOST'] ?: '127.0.0.1';
    }

    /**
     * Get active vnode name.
     */
    public static function activeVnode(): ?string
    {
        self::loadEnv();

        try {
            $db = new \PDO('sqlite:' . self::sysadmDb(), null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $stmt = $db->query('SELECT name FROM vnodes WHERE is_active = 1 LIMIT 1');
            if ($row = $stmt->fetch()) {
                return $row['name'];
            }
        } catch (\Exception) {
            // DB not available
        }

        return null;
    }

    // === Path Helpers ===

    public static function vhostPath(string $domain): string
    {
        return self::get('VPATH') . '/' . $domain;
    }

    public static function webPath(string $domain): string
    {
        return self::get('VPATH') . "/{$domain}/web/app/public";
    }

    public static function mailPath(string $domain): string
    {
        return self::get('VPATH') . "/{$domain}/msg";
    }

    public static function userPath(string $domain, string $user): string
    {
        return self::get('VPATH') . "/{$domain}/msg/{$user}";
    }

    // === Database Paths ===

    public static function sysadmDb(): string
    {
        self::loadEnv();
        $path = $_ENV['SYSADM_DB'] ?? getenv('SYSADM_DB') ?: self::DEFAULTS['SYSADM_DB'];

        // Expand ~
        if (str_starts_with($path, '~/')) {
            $path = getenv('HOME') . substr($path, 1);
        }

        return $path;
    }

    public static function hcpDb(): string
    {
        self::loadEnv();
        $path = $_ENV['HCP_DB'] ?? getenv('HCP_DB') ?: self::DEFAULTS['HCP_DB'];

        // Expand ~
        if (str_starts_with($path, '~/')) {
            $path = getenv('HOME') . substr($path, 1);
        }

        return $path;
    }

    // === SSH Paths ===

    public static function sshHostsDir(): string
    {
        self::loadEnv();
        $path = $_ENV['SSH_HOSTS_DIR'] ?? getenv('SSH_HOSTS_DIR') ?: self::DEFAULTS['SSH_HOSTS_DIR'];

        if (str_starts_with($path, '~/')) {
            $path = getenv('HOME') . substr($path, 1);
        }

        return $path;
    }

    public static function sshKeysDir(): string
    {
        self::loadEnv();
        $path = $_ENV['SSH_KEYS_DIR'] ?? getenv('SSH_KEYS_DIR') ?: self::DEFAULTS['SSH_KEYS_DIR'];

        if (str_starts_with($path, '~/')) {
            $path = getenv('HOME') . substr($path, 1);
        }

        return $path;
    }
}
