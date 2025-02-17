<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Session\Core;

//use InvalidArgumentException as IAE;

final class Util
{
    public static function elog(string $msg): void
    {
        if (defined('DBG') && DBG)
        {
            error_log($msg);
        }
    }

    public static function esc(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

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
}
