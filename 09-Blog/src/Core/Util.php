<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

final class Util {
    public static function enc(string $str): string {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

    public static function esc(array $in): array {
        foreach ($in as $k => $v)
            $in[$k] = isset($_REQUEST[$k]) && !is_array($_REQUEST[$k])
                ? self::enc($_REQUEST[$k]) : $v;
        return $in;
    }

    public static function ses(string $k, mixed $v = '', mixed $x = null): mixed {
        if (isset($_REQUEST[$k]))
            $_SESSION[$k] = is_array($_REQUEST[$k]) ? $_REQUEST[$k] : trim($_REQUEST[$k]);
        elseif (!isset($_SESSION[$k]))
            $_SESSION[$k] = $x ?? $v;
        return $_SESSION[$k];
    }

    public static function nlbr(string $text): string {
        return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    }

    // Auth helpers
    public static function is_usr(): bool {
        return isset($_SESSION['usr']);
    }

    public static function is_adm(): bool {
        return isset($_SESSION['usr']) && (int)$_SESSION['usr']['acl'] === 0;
    }

    public static function is_post(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public static function random_token(int $length = 16): string {
        return bin2hex(random_bytes($length));
    }

    public static function set_cookie(string $name, string $value, int $expiry): void {
        setcookie($name, $value, [
            'expires' => time() + $expiry,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    public static function redirect(string $url): never {
        header("Location: $url");
        exit;
    }

    public static function log(string $msg, string $type = 'danger'): void {
        $_SESSION['log'] = ['msg' => $msg, 'type' => $type];
    }

    public static function get_log(): array {
        $log = $_SESSION['log'] ?? [];
        unset($_SESSION['log']);
        return $log;
    }

    /**
     * Minimal Markdown parser (~70 lines) supporting GitHub-flavored subset:
     * Headings, bold, italic, strikethrough, links, images, code, blockquotes, lists, hr, tables
     * Based on Slimdown by Johnny Broadway (MIT License)
     */
    public static function md(string $s): string {
        $b = []; $L = "\x02"; $R = "\x03"; // Markers for safe tags

        // 1. Protect fenced code blocks (``` with optional language)
        $s = preg_replace_callback('/```(\w*)\r?\n(.*?)\r?\n```/s', function($m) use (&$b, $L, $R) {
            $lang = $m[1] ? " class=\"lang-{$m[1]}\"" : '';
            $b[] = "{$L}pre{$R}{$L}code{$lang}{$R}" . htmlspecialchars(rtrim($m[2])) . "{$L}/code{$R}{$L}/pre{$R}";
            return "\x00" . (count($b) - 1) . "\x00";
        }, $s);

        // 2. Protect inline code
        $s = preg_replace_callback('/`([^`\n]+)`/', function($m) use (&$b, $L, $R) {
            $b[] = "{$L}code{$R}" . htmlspecialchars($m[1]) . "{$L}/code{$R}";
            return "\x00" . (count($b) - 1) . "\x00";
        }, $s);

        // 3. GFM Tables - must be before other block elements
        $s = preg_replace_callback('/^(\|.+\|)\r?\n(\|[-:\| ]+\|)\r?\n((?:\|.+\|\r?\n?)+)/m', function($m) use ($L, $R) {
            // Parse header
            $headers = array_map('trim', array_filter(explode('|', $m[1])));
            // Parse alignment from separator row
            $aligns = array_map(function($c) {
                $c = trim($c);
                if (str_starts_with($c, ':') && str_ends_with($c, ':')) return 'center';
                if (str_ends_with($c, ':')) return 'right';
                return 'left';
            }, array_filter(explode('|', $m[2])));
            // Build header row
            $thead = "{$L}tr{$R}" . implode('', array_map(fn($h, $i) =>
                "{$L}th style=\"text-align:{$aligns[$i]}\"{$R}" . trim($h) . "{$L}/th{$R}",
                $headers, array_keys($headers))) . "{$L}/tr{$R}";
            // Build body rows
            $rows = array_filter(explode("\n", trim($m[3])));
            $tbody = implode('', array_map(function($row) use ($aligns, $L, $R) {
                $cells = array_map('trim', array_filter(explode('|', $row)));
                return "{$L}tr{$R}" . implode('', array_map(fn($c, $i) =>
                    "{$L}td style=\"text-align:" . ($aligns[$i] ?? 'left') . "\"{$R}" . trim($c) . "{$L}/td{$R}",
                    $cells, array_keys($cells))) . "{$L}/tr{$R}";
            }, $rows));
            return "{$L}table{$R}{$L}thead{$R}$thead{$L}/thead{$R}{$L}tbody{$R}$tbody{$L}/tbody{$R}{$L}/table{$R}";
        }, $s);

        // 4. Block elements (using markers)
        $s = preg_replace_callback('/^(#{1,6})\s+(.+)$/m', fn($m) =>
            "{$L}h" . strlen($m[1]) . "{$R}" . trim($m[2]) . "{$L}/h" . strlen($m[1]) . "{$R}", $s);
        $s = preg_replace('/^[-*_]{3,}\s*$/m', "{$L}hr{$R}", $s);
        $s = preg_replace('/^>\s*(.+)$/m', "{$L}blockquote{$R}\$1{$L}/blockquote{$R}", $s);

        // 5. Lists
        $s = preg_replace('/^[-*+]\s+(.+)$/m', "{$L}ul{$R}{$L}li{$R}\$1{$L}/li{$R}{$L}/ul{$R}", $s);
        $s = preg_replace('/^\d+\.\s+(.+)$/m', "{$L}ol{$R}{$L}li{$R}\$1{$L}/li{$R}{$L}/ol{$R}", $s);
        $s = preg_replace("/{$L}\/ul{$R}\s*{$L}ul{$R}/", '', $s);
        $s = preg_replace("/{$L}\/ol{$R}\s*{$L}ol{$R}/", '', $s);
        $s = preg_replace("/{$L}\/blockquote{$R}\s*{$L}blockquote{$R}/", "\n", $s);

        // 5. Inline elements
        $s = preg_replace('/!\[([^\]]*)\]\(([^)\s]+)\)/', "{$L}img src=\"\$2\" alt=\"\$1\"{$R}", $s);
        $s = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', "{$L}a href=\"\$2\"{$R}\$1{$L}/a{$R}", $s);
        $s = preg_replace('/(\*\*|__)(.+?)\1/', "{$L}strong{$R}\$2{$L}/strong{$R}", $s);
        $s = preg_replace('/(\*|_)(.+?)\1/', "{$L}em{$R}\$2{$L}/em{$R}", $s);
        $s = preg_replace('/~~(.+?)~~/', "{$L}del{$R}\$1{$L}/del{$R}", $s);

        // 6. Escape HTML, restore protected blocks and convert markers
        $s = htmlspecialchars($s, ENT_NOQUOTES);
        $s = preg_replace_callback('/\x00(\d+)\x00/', fn($m) => $b[(int)$m[1]], $s);
        $s = str_replace([$L, $R], ['<', '>'], $s);

        // 7. Paragraphs - split on blank lines, wrap non-block content in <p>
        $s = preg_replace('/\n{2,}/', "\n\n", trim($s));
        $blocks = preg_split('/\n\n/', $s);
        $s = implode("\n", array_map(function($block) {
            $block = trim($block);
            if ($block === '') return '';
            if (preg_match('/^<(?:h[1-6]|ul|ol|blockquote|hr|pre|table)/', $block)) return $block;
            return '<p>' . preg_replace('/\n/', '<br>', $block) . '</p>';
        }, $blocks));

        return trim($s);
    }
}
