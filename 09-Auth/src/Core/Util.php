<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250212
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Auth\Core;

use InvalidArgumentException as IAE;

final class Util
{
    public static function is_post(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public static function is_usr(): bool
    {
        return isset($_SESSION['usr']);
    }

    public static function random_token(int $length): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    public static function put_cookie(string $name, string $value, int $expiry): void
    {
        setcookie($name, $value, time() + $expiry, '/');
    }

    public static function elog(string $msg): void
    {
        if (defined('DBG') && DBG)
        {
            error_log($msg);
        }
    }

    public static function log(string $msg = '', string $lvl = 'danger'): array
    {
        self::elog(__METHOD__);

        if ($msg)
        {
            $_SESSION['log'][$lvl] = empty($_SESSION['log'][$lvl])
                ? $msg
                : $_SESSION['log'][$lvl] . '<br>' . $msg;
        }
        elseif (isset($_SESSION['log']) && $_SESSION['log'])
        {
            $log = $_SESSION['log'];
            $_SESSION['log'] = [];
            return $log;
        }
        return [];
    }

    /**
     * Manages session values with request integration and default fallbacks.
     * 
     * This method provides three-tier session management:
     * 1. If the key exists in $_REQUEST, updates $_SESSION with that value
     * 2. If the key doesn't exist in $_REQUEST or $_SESSION, sets a default value
     * 3. Always returns the current $_SESSION value for the key
     * 
     * @param string $k The session key to manage
     * @param mixed $v Default value if key doesn't exist in $_REQUEST or $_SESSION
     * @param mixed $x Optional override value that takes precedence over $v when setting defaults
     * @return mixed The current value in $_SESSION for the given key
     * 
     * @example
     * // Set/get a session value, checking $_REQUEST first
     * $value = Util::ses('user_id', 0);
     * 
     * // Set/get with an override default
     * $theme = Util::ses('theme', 'light', 'dark');
     */
    public static function ses(string $k, mixed $v = '', mixed $x = null): mixed
    {
        self::elog(__METHOD__ . "({$k}, " . var_export($v, true) . ", " . var_export($x, true) . ")");

        if (isset($_REQUEST[$k]))
        {
            $_SESSION[$k] = is_array($_REQUEST[$k]) ? $_REQUEST[$k] : trim($_REQUEST[$k]);
        }
        elseif (!isset($_SESSION[$k]))
        {
            $_SESSION[$k] = $x ?? $v;
        }
        return $_SESSION[$k];
    }

    public static function esc(array $in): array
    {
        self::elog(__METHOD__);

        return array_map(
            fn($value) => isset($_REQUEST[$value]) && !is_array($_REQUEST[$value])
                ? self::enc($_REQUEST[$value])
                : $value,
            $in
        );
    }

    public static function enc(string $v): string
    {
        self::elog(__METHOD__ . "({$v})");

        return htmlentities(trim($v), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function nlbr(string $text): string
    {
        self::elog(__METHOD__ . "({$text})");

        return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    }

    public static function genpw(int $length = 16): string
    {
        self::elog(__METHOD__);

        return bin2hex(random_bytes($length));
    }

    public static function chkpw(string $pw1, string $pw2 = ''): bool
    {
        self::elog(__METHOD__);

        try
        {
            match (true)
            {
                strlen($pw1) < 12 => throw new IAE('Password must be at least 12 characters'),
                !preg_match('/[0-9]+/', $pw1) => throw new IAE('Password must contain at least one number'),
                !preg_match('/[A-Z]+/', $pw1) => throw new IAE('Password must contain at least one capital letter'),
                !preg_match('/[a-z]+/', $pw1) => throw new IAE('Password must contain at least one lowercase letter'),
                $pw2 !== '' && $pw1 !== $pw2 => throw new IAE('Passwords do not match')
            };
            return true;
        }
        catch (IAE $e)
        {
            self::log($e->getMessage());
            return false;
        }
    }

    public static function redirect(
        string $url,
        string $method = 'location',
        int $ttl = 5,
        string $msg = ''
    ): never
    {
        self::elog(__METHOD__ . ' url=' . $url);

        match ($method)
        {
            'refresh' => (function () use ($url, $ttl, $msg)
            {
                header("refresh:$ttl;url=$url");
                echo <<<HTML
                    <!DOCTYPE html><title>Redirect...</title>
                    <h2 style="text-align:center">Redirecting in {$ttl} seconds...</h2>
                    <pre style="width:50em;margin:0 auto">{$msg}</pre>
                HTML;
            })(),
            default => header("Location:$url")
        };

        exit;
    }

    public static function now(string $date1, ?string $date2 = null): string
    {
        self::elog(__METHOD__);

        try
        {
            $t1 = is_numeric($date1) ? (int)$date1 : strtotime($date1);
            $t2 = $date2 ? (is_numeric($date2) ? (int)$date2 : strtotime($date2)) : time();

            if ($t1 === false || ($date2 && $t2 === false))
            {
                throw new \InvalidArgumentException('Invalid date format');
            }

            $diff = abs($t1 - $t2);
            if ($diff < 10) return 'just now';

            $intervals = [
                ['year', 31536000],
                ['month', 2678400],
                ['week', 604800],
                ['day', 86400],
                ['hour', 3600],
                ['min', 60],
                ['sec', 1]
            ];

            return match (true)
            {
                $diff < 10 => 'just now',
                default => implode(' ', array_slice(array_filter(
                    array_map(
                        fn($i) => (($c = floor($diff / $i[1])) > 0)
                            ? (($diff = $diff % $i[1]) !== false
                                ? "$c {$i[0]}" . ($c > 1 ? 's' : '')
                                : null)
                            : null,
                        $intervals
                    )
                ), 0, 2)) . ' ago'
            };
        }
        catch (\Throwable)
        {
            error_log("Date calculation error");
            return 'unknown time ago';
        }
    }
}
