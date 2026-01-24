<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Core;

final class Util
{
    public static function timeAgo(int $ts): string
    {
        $d = time() - $ts;
        if ($d < 10) return 'just now';
        foreach ([['hour', 3600], ['min', 60], ['sec', 1]] as [$name, $secs]) {
            if ($d >= $secs) {
                $amt = (int) ($d / $secs);
                return "{$amt} {$name}" . ($amt > 1 ? 's' : '') . ' ago';
            }
        }
        return 'just now';
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return "{$bytes} B";
        foreach (['KB', 'MB', 'GB'] as $unit) {
            $bytes /= 1024;
            if ($bytes < 1024) return round($bytes, 1) . " {$unit}";
        }
        return round($bytes, 1) . ' TB';
    }
}
