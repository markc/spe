<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Core;

final class Util {
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
        $units = [['year', 31536000], ['month', 2678400], ['week', 604800],
                  ['day', 86400], ['hour', 3600], ['min', 60], ['sec', 1]];
        $parts = [];
        foreach ($units as [$name, $secs]) {
            if ($d >= $secs && count($parts) < 2) {
                $amt = (int)($d / $secs);
                $parts[] = "$amt $name" . ($amt > 1 ? 's' : '');
                $d %= $secs;
            }
        }
        return implode(' ', $parts) . ' ago';
    }

    public static function sessionInfo(): array {
        return [
            'id' => session_id(),
            'name' => session_name(),
            'status' => match (session_status()) {
                PHP_SESSION_DISABLED => 'disabled',
                PHP_SESSION_NONE => 'none',
                PHP_SESSION_ACTIVE => 'active'
            },
            'save_path' => session_save_path(),
            'data' => $_SESSION
        ];
    }
}
