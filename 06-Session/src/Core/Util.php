<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Core;

final class Util
{
    public static function timeAgo(int $ts): string
    {
        $d = time() - $ts;
        if ($d < 10)
            return 'just now';
        $units = [['hour', 3600], ['min', 60], ['sec', 1]];
        foreach ($units as [$name, $secs]) {
            if ($d < $secs) {
                continue;
            }

            $amt = (int) ($d / $secs);
            return "$amt $name" . ($amt > 1 ? 's' : '') . ' ago';
        }
        return 'just now';
    }
}
