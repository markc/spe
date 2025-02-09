<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250208
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Autoload\Core;

final class Util
{
    public static function elog(string $msg): void
    {
        if (defined('DBG') && DBG)
        {
            error_log($msg);
        }
    }
}
