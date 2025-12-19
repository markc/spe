<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\App;

final class Util
{
    // Text processing
    public static function enc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    public static function excerpt(string $s, int $len = 200): string
    {
        $s = strip_tags($s);
        $s = preg_replace('/\s+/', ' ', trim($s));
        return strlen($s) > $len ? substr($s, 0, $len) . '...' : $s;
    }

    public static function esc(array $in): array
    {
        foreach ($in as $k => $v)
            $in[$k] = isset($_REQUEST[$k]) && !is_array($_REQUEST[$k]) ? self::enc($_REQUEST[$k]) : $v;
        return $in;
    }

    public static function nlbr(string $s): string
    {
        return nl2br(self::enc($s));
    }

    // Session
    public static function ses(string $k, mixed $v = '', mixed $x = null): mixed
    {
        return $_SESSION[$k] = isset($_REQUEST[$k])
            ? (is_array($_REQUEST[$k]) ? $_REQUEST[$k] : trim($_REQUEST[$k]))
            : ($_SESSION[$k] ?? $x ?? $v);
    }

    // Auth
    public static function is_usr(): bool { return isset($_SESSION['usr']); }
    public static function is_adm(): bool { return isset($_SESSION['usr']) && (int)$_SESSION['usr']['acl'] === 0; }
    public static function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }

    // Security
    public static function token(int $len = 16): string { return bin2hex(random_bytes($len)); }

    public static function cookie(string $name, string $val, int $exp): void
    {
        setcookie($name, $val, ['expires' => time() + $exp, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    }

    // Flow control
    public static function redirect(string $url): never { header("Location: $url"); exit; }

    // Flash messages
    public static function log(string $msg, string $type = 'danger'): void
    {
        $_SESSION['log'] = ['msg' => $msg, 'type' => $type];
    }

    public static function flash(): array
    {
        $log = $_SESSION['log'] ?? [];
        unset($_SESSION['log']);
        return $log;
    }

    public static function toast(): string
    {
        $log = self::flash();
        if (!$log) return '';
        $ok = $log['type'] === 'success';
        $bg = $ok ? '#d4edda' : '#f8d7da';
        $fg = $ok ? '#155724' : '#721c24';
        return "<div style=\"margin-bottom:1rem;padding:1rem;border-radius:4px;background:$bg;color:$fg\">" . self::enc($log['msg']) . "</div>";
    }

    // Time formatting
    public static function timeAgo(int $ts): string
    {
        $d = time() - $ts;
        if ($d < 10) return 'just now';
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

    // Markdown parser - GFM subset (headings, bold, italic, strikethrough, links, images, code, blockquotes, lists, hr, tables)
    public static function md(string $s): string
    {
        $b = []; $L = "\x02"; $R = "\x03";

        // Protect code blocks
        $s = preg_replace_callback('/```(\w*)\r?\n(.*?)\r?\n```/s', function($m) use (&$b, $L, $R) {
            $lang = $m[1] ? " class=\"lang-{$m[1]}\"" : '';
            $b[] = "{$L}pre{$R}{$L}code{$lang}{$R}" . htmlspecialchars(rtrim($m[2])) . "{$L}/code{$R}{$L}/pre{$R}";
            return "\x00" . (count($b) - 1) . "\x00";
        }, $s);
        $s = preg_replace_callback('/`([^`\n]+)`/', function($m) use (&$b, $L, $R) {
            $b[] = "{$L}code{$R}" . htmlspecialchars($m[1]) . "{$L}/code{$R}";
            return "\x00" . (count($b) - 1) . "\x00";
        }, $s);

        // GFM Tables
        $s = preg_replace_callback('/^(\|.+\|)\r?\n(\|[-:\| ]+\|)\r?\n((?:\|.+\|\r?\n?)+)/m', function($m) use ($L, $R) {
            $hdr = array_map('trim', array_filter(explode('|', $m[1])));
            $align = array_map(fn($c) => match(true) {
                str_starts_with($c = trim($c), ':') && str_ends_with($c, ':') => 'center',
                str_ends_with($c, ':') => 'right', default => 'left'
            }, array_filter(explode('|', $m[2])));
            $th = implode('', array_map(fn($h, $i) => "{$L}th style=\"text-align:{$align[$i]}\"{$R}$h{$L}/th{$R}", $hdr, array_keys($hdr)));
            $rows = array_filter(explode("\n", trim($m[3])));
            $tb = implode('', array_map(function($r) use ($align, $L, $R) {
                $cells = array_map('trim', array_filter(explode('|', $r)));
                return "{$L}tr{$R}" . implode('', array_map(fn($c, $i) =>
                    "{$L}td style=\"text-align:" . ($align[$i] ?? 'left') . "\"{$R}$c{$L}/td{$R}", $cells, array_keys($cells))) . "{$L}/tr{$R}";
            }, $rows));
            return "{$L}table{$R}{$L}thead{$R}{$L}tr{$R}$th{$L}/tr{$R}{$L}/thead{$R}{$L}tbody{$R}$tb{$L}/tbody{$R}{$L}/table{$R}";
        }, $s);

        // Block elements
        $s = preg_replace_callback('/^(#{1,6})\s+(.+)$/m', fn($m) => "{$L}h" . strlen($m[1]) . "{$R}" . trim($m[2]) . "{$L}/h" . strlen($m[1]) . "{$R}", $s);
        $s = preg_replace('/^[-*_]{3,}\s*$/m', "{$L}hr{$R}", $s);
        $s = preg_replace('/^>\s*(.+)$/m', "{$L}blockquote{$R}$1{$L}/blockquote{$R}", $s);

        // Lists
        $s = preg_replace('/^[-*+]\s+(.+)$/m', "{$L}ul{$R}{$L}li{$R}$1{$L}/li{$R}{$L}/ul{$R}", $s);
        $s = preg_replace('/^\d+\.\s+(.+)$/m', "{$L}ol{$R}{$L}li{$R}$1{$L}/li{$R}{$L}/ol{$R}", $s);
        $s = preg_replace(["/{$L}\/ul{$R}\s*{$L}ul{$R}/", "/{$L}\/ol{$R}\s*{$L}ol{$R}/", "/{$L}\/blockquote{$R}\s*{$L}blockquote{$R}/"], ['', '', "\n"], $s);

        // Inline elements
        $s = preg_replace('/!\[([^\]]*)\]\(([^)\s]+)\)/', "{$L}img src=\"$2\" alt=\"$1\"{$R}", $s);
        $s = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', "{$L}a href=\"$2\"{$R}$1{$L}/a{$R}", $s);
        $s = preg_replace(['/(\*\*|__)(.+?)\1/', '/(\*|_)(.+?)\1/', '/~~(.+?)~~/'],
            ["{$L}strong{$R}$2{$L}/strong{$R}", "{$L}em{$R}$2{$L}/em{$R}", "{$L}del{$R}$1{$L}/del{$R}"], $s);

        // Finalize
        $s = htmlspecialchars($s, ENT_NOQUOTES);
        $s = preg_replace_callback('/\x00(\d+)\x00/', fn($m) => $b[(int)$m[1]], $s);
        $s = str_replace([$L, $R], ['<', '>'], $s);

        // Paragraphs
        return implode("\n", array_map(fn($p) => ($p = trim($p)) === '' ? '' :
            (preg_match('/^<(?:h[1-6]|ul|ol|blockquote|hr|pre|table)/', $p) ? $p : '<p>' . preg_replace('/\n/', '<br>', $p) . '</p>'),
            preg_split('/\n{2,}/', trim($s)))) |> trim(...);
    }
}
