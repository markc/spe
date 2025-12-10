<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Core;

final class Util {
    public static function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
    public static function nlbr(string $text): string { return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')); }

    /**
     * Minimal Markdown parser (~50 lines) supporting GitHub-flavored subset:
     * Headings, bold, italic, strikethrough, links, images, code, blockquotes, lists, hr
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

        // 3. Block elements (using markers)
        $s = preg_replace_callback('/^(#{1,6})\s+(.+)$/m', fn($m) =>
            "{$L}h" . strlen($m[1]) . "{$R}" . trim($m[2]) . "{$L}/h" . strlen($m[1]) . "{$R}", $s);
        $s = preg_replace('/^[-*_]{3,}\s*$/m', "{$L}hr{$R}", $s);
        $s = preg_replace('/^>\s*(.+)$/m', "{$L}blockquote{$R}\$1{$L}/blockquote{$R}", $s);

        // 4. Lists
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
            if (preg_match('/^<(?:h[1-6]|ul|ol|blockquote|hr|pre)/', $block)) return $block;
            return '<p>' . preg_replace('/\n/', '<br>', $block) . '</p>';
        }, $blocks));

        return trim($s);
    }
}
