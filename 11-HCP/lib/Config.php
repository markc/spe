#!/usr/bin/env php
<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Lib;

/**
 * NS 3.0 Global Configuration
 *
 * Central path definitions for the NetServa 3.0 hosting structure.
 * Used by both lib/ classes and bin/ CLI scripts.
 *
 * Directory structure:
 *   /srv/domain/
 *   ├── .ssh/
 *   ├── var/{log,run}/
 *   ├── msg/user/Maildir/{cur,new,tmp}
 *   └── web/app/public/
 */
final class Config
{
    // Base paths
    public const string VPATH = '/srv';                          // Base path for all vhosts
    public const string VHOST = '';                              // Set at runtime via hostname()

    // Path templates (use sprintf with domain/user)
    public const string WPATH = '/srv/%s/web/app/public';        // Web docroot: sprintf(WPATH, $domain)
    public const string MPATH = '/srv/%s/msg';                   // Mail base: sprintf(MPATH, $domain)
    public const string UPATH = '/srv/%s/msg/%s';                // User maildir: sprintf(UPATH, $domain, $user)

    // System paths
    public const string NGINX_AVAILABLE = '/etc/nginx/sites-available';
    public const string NGINX_ENABLED = '/etc/nginx/sites-enabled';
    public const string PHP_FPM_POOLS = '/etc/php/%s/fpm/pool.d'; // sprintf with PHP version

    // Database paths
    public const string SYSADM_DB = '/srv/.local/sqlite/sysadm.db';
    public const string HCP_DB = '/srv/.local/sqlite/hcp.db';

    // PHP versions to check (in order of preference)
    public const array PHP_VERSIONS = ['8.5', '8.4', '8.3'];

    // UID/GID range for vhost users
    public const int UID_MIN = 1001;
    public const int UID_MAX = 1999;

    /**
     * Get the primary hostname (cached).
     */
    private static ?string $hostname = null;

    public static function hostname(): string
    {
        if (self::$hostname === null) {
            self::$hostname = trim(shell_exec('hostname -f 2>/dev/null') ?? '') ?: 'localhost';
        }
        return self::$hostname;
    }

    /**
     * Get vhost home directory.
     */
    public static function vhostPath(string $domain): string
    {
        return self::VPATH . '/' . $domain;
    }

    /**
     * Get web document root.
     */
    public static function webPath(string $domain): string
    {
        return sprintf(self::WPATH, $domain);
    }

    /**
     * Get mail base directory for domain.
     */
    public static function mailPath(string $domain): string
    {
        return sprintf(self::MPATH, $domain);
    }

    /**
     * Get user maildir path.
     */
    public static function userPath(string $domain, string $user): string
    {
        return sprintf(self::UPATH, $domain, $user);
    }

    /**
     * Get database path from environment or default.
     */
    public static function sysadmDb(): string
    {
        return $_ENV['SYSADM_DB'] ?? getenv('SYSADM_DB') ?: self::SYSADM_DB;
    }

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
            $path = sprintf(self::PHP_FPM_POOLS, $ver);
            if (is_dir($path)) {
                return $path;
            }
        }
        return null;
    }

    /**
     * Get PHP version from pool directory.
     */
    public static function phpVersion(): ?string
    {
        foreach (self::PHP_VERSIONS as $ver) {
            $path = sprintf(self::PHP_FPM_POOLS, $ver);
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

// Allow CLI scripts to include this file directly
if (php_sapi_name() === 'cli' && !class_exists('SPE\HCP\Lib\Config', false)) {
    // When included directly from bin/ scripts, make Config available
}
