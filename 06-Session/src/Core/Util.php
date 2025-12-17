<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Core;

final readonly class Util {
    public static function esc(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    public static function timeAgo(int $ts): string {
        $d = time() - $ts;
        return match (true) {
            $d < 10 => 'just now',
            default => self::fmt($d)
        };
    }

    private static function fmt(int $d): string {
        $u = [['year', 31536000], ['month', 2678400], ['week', 604800],
              ['day', 86400], ['hour', 3600], ['min', 60], ['sec', 1]];
        $r = [];
        foreach ($u as [$n, $s]) {
            if ($d >= $s && count($r) < 2) {
                $a = (int)($d / $s);
                $r[] = "$a $n" . ($a > 1 ? 's' : '');
                $d %= $s;
            }
        }
        return implode(' ', $r) . ' ago';
    }
}
