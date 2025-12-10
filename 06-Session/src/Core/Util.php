<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Core;

final class Util {
    public static function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

    public static function timeAgo(int $timestamp): string {
        $diff = time() - $timestamp;

        return match (true) {
            $diff < 10 => 'just now',
            default => self::formatTimeUnits($diff)
        };
    }

    private static function formatTimeUnits(int $diff): string {
        $units = [
            ['year', 31536000], ['month', 2678400], ['week', 604800],
            ['day', 86400], ['hour', 3600], ['min', 60], ['sec', 1]
        ];
        $result = [];
        foreach ($units as [$name, $secs]) {
            if ($diff >= $secs && count($result) < 2) {
                $amount = floor($diff / $secs);
                $result[] = $amount . ' ' . $name . ($amount > 1 ? 's' : '');
                $diff %= $secs;
            }
        }
        return implode(' ', $result) . ' ago';
    }
}
