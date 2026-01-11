<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Lib;

/**
 * NS 3.0 Global Configuration
 *
 * Central path definitions for the NetServa 3.0 hosting structure.
 * Used by lib/ classes. CLI scripts use /etc/ns3/config.php.
 *
 * Directory structure:
 *   /srv/domain/
 *   ├── var/{log,run}/
 *   ├── msg/user/Maildir/{cur,new,tmp}
 *   └── web/app/public/
 */
final class Config
{
    // === Primary Host ===
    public const string ADMIN = 'sysadm';

    // === Base Paths ===
    public const string VPATH = '/srv';
    public const string WPATH = '/srv/%s/web/app/public';
    public const string MPATH = '/srv/%s/msg';
    public const string UPATH = '/srv/%s/msg/%s';

    // === Database ===
    public const string DTYPE = 'sqlite';
    public const string SYSADM_DB = '/srv/.local/sqlite/sysadm.db';
    public const string HCP_DB = '/srv/.local/sqlite/hcp.db';

    // === Nginx ===
    public const string NGINX_AVAILABLE = '/etc/nginx/sites-available';
    public const string NGINX_ENABLED = '/etc/nginx/sites-enabled';

    // === PHP ===
    public const string V_PHP = '8.4';
    public const array PHP_VERSIONS = ['8.5', '8.4', '8.3'];

    // === UID/GID Range ===
    public const int UID_MIN = 1001;
    public const int UID_MAX = 1999;
    public const string WUGID = 'www-data';

    // === Cached Values ===
    private static ?string $hostname = null;

    /**
     * Get primary hostname (VHOST).
     */
    public static function hostname(): string
    {
        if (self::$hostname === null) {
            self::$hostname = trim(shell_exec('hostname -f 2>/dev/null') ?? '') ?: 'localhost';
        }
        return self::$hostname;
    }

    /**
     * Get vhost home directory: /srv/domain
     */
    public static function vhostPath(string $domain): string
    {
        return self::VPATH . '/' . $domain;
    }

    /**
     * Get web document root: /srv/domain/web/app/public
     */
    public static function webPath(string $domain): string
    {
        return sprintf(self::WPATH, $domain);
    }

    /**
     * Get mail base directory: /srv/domain/msg
     */
    public static function mailPath(string $domain): string
    {
        return sprintf(self::MPATH, $domain);
    }

    /**
     * Get user maildir path: /srv/domain/msg/user
     */
    public static function userPath(string $domain, string $user): string
    {
        return sprintf(self::UPATH, $domain, $user);
    }

    /**
     * Get sysadm database path (from env or default).
     */
    public static function sysadmDb(): string
    {
        return $_ENV['SYSADM_DB'] ?? getenv('SYSADM_DB') ?: self::SYSADM_DB;
    }

    /**
     * Get HCP database path (from env or default).
     */
    public static function hcpDb(): string
    {
        return $_ENV['HCP_DB'] ?? getenv('HCP_DB') ?: self::HCP_DB;
    }

    /**
     * Find first available PHP-FPM pool directory.
     */
    public static function phpFpmPoolDir(): ?string
    {
        foreach (self::PHP_VERSIONS as $ver) {
            $path = "/etc/php/{$ver}/fpm/pool.d";
            if (is_dir($path)) {
                return $path;
            }
        }
        return null;
    }

    /**
     * Get active PHP version.
     */
    public static function phpVersion(): ?string
    {
        foreach (self::PHP_VERSIONS as $ver) {
            $path = "/etc/php/{$ver}/fpm/pool.d";
            if (is_dir($path)) {
                return $ver;
            }
        }
        return null;
    }

    /**
     * Find first available UID in range.
     */
    public static function nextUid(): ?int
    {
        for ($i = self::UID_MIN; $i <= self::UID_MAX; $i++) {
            if (!posix_getpwuid($i)) {
                return $i;
            }
        }
        return null;
    }
}
