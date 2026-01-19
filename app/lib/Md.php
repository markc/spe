<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\App;

final class Md
{
    // GFM subset: headings, bold, italic, strikethrough, links, images, code, blockquotes, lists, hr, tables
    public static function parse(string $s): string
    {
        $b = [];
        $L = "\x02";
        $R = "\x03";

        // Protect code blocks
        $s = preg_replace_callback(
            '/```(\w*)\r?\n(.*?)\r?\n```/s',
            static function ($m) use (&$b, $L, $R) {
                $lang = $m[1] ? " class=\"lang-{$m[1]}\"" : '';
                $b[] = "{$L}pre{$R}{$L}code{$lang}{$R}" . htmlspecialchars(rtrim($m[2])) . "{$L}/code{$R}{$L}/pre{$R}";
                return "\x00" . (count($b) - 1) . "\x00";
            },
            $s,
        );
        $s = preg_replace_callback(
            '/`([^`\n]+)`/',
            static function ($m) use (&$b, $L, $R) {
                $b[] = "{$L}code{$R}" . htmlspecialchars($m[1]) . "{$L}/code{$R}";
                return "\x00" . (count($b) - 1) . "\x00";
            },
            $s,
        );

        // GFM Tables
        $s = preg_replace_callback(
            '/^(\|.+\|)\r?\n(\|[-:\| ]+\|)\r?\n((?:\|.+\|\r?\n?)+)/m',
            static function ($m) use ($L, $R) {
                $hdr = array_map('trim', array_filter(explode('|', $m[1])));
                $align = array_map(static fn($c) => match (true) {
                    str_starts_with($c = trim($c), ':') && str_ends_with($c, ':') => 'center',
                    str_ends_with($c, ':') => 'right',
                    default => 'left',
                }, array_filter(explode('|', $m[2])));
                $th = implode('', array_map(
                    static fn($h, $i) => "{$L}th style=\"text-align:{$align[$i]}\"{$R}$h{$L}/th{$R}",
                    $hdr,
                    array_keys($hdr),
                ));
                $rows = array_filter(explode("\n", trim($m[3])));
                $tb = implode('', array_map(static function ($r) use ($align, $L, $R) {
                    $cells = array_map('trim', array_filter(explode('|', $r)));
                    return (
                        "{$L}tr{$R}"
                        . implode('', array_map(
                            static fn($c, $i) => (
                                "{$L}td style=\"text-align:"
                                . ($align[$i] ?? 'left')
                                . "\"{$R}$c{$L}/td{$R}"
                            ),
                            $cells,
                            array_keys($cells),
                        ))
                        . "{$L}/tr{$R}"
                    );
                }, $rows));
                return "{$L}table{$R}{$L}thead{$R}{$L}tr{$R}$th{$L}/tr{$R}{$L}/thead{$R}{$L}tbody{$R}$tb{$L}/tbody{$R}{$L}/table{$R}";
            },
            $s,
        );

        // Block elements
        $s = preg_replace_callback(
            '/^(#{1,6})\s+(.+)$/m',
            static fn($m) => "{$L}h" . strlen($m[1]) . "{$R}" . trim($m[2]) . "{$L}/h" . strlen($m[1]) . "{$R}",
            $s,
        );
        $s = preg_replace('/^[-*_]{3,}\s*$/m', "{$L}hr{$R}", $s);
        $s = preg_replace('/^>\s*(.+)$/m', "{$L}blockquote{$R}$1{$L}/blockquote{$R}", $s);

        // Lists
        $s = preg_replace('/^[-*+]\s+(.+)$/m', "{$L}ul{$R}{$L}li{$R}$1{$L}/li{$R}{$L}/ul{$R}", $s);
        $s = preg_replace('/^\d+\.\s+(.+)$/m', "{$L}ol{$R}{$L}li{$R}$1{$L}/li{$R}{$L}/ol{$R}", $s);
        $s = preg_replace(
            [
                "/{$L}\/ul{$R}\s*{$L}ul{$R}/",
                "/{$L}\/ol{$R}\s*{$L}ol{$R}/",
                "/{$L}\/blockquote{$R}\s*{$L}blockquote{$R}/",
            ],
            ['', '', "\n"],
            $s,
        );

        // Inline elements
        $s = preg_replace('/!\[([^\]]*)\]\(([^)\s]+)\)/', "{$L}img src=\"$2\" alt=\"$1\"{$R}", $s);
        $s = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', "{$L}a href=\"$2\"{$R}$1{$L}/a{$R}", $s);
        $s = preg_replace(
            ['/(\*\*|__)(.+?)\1/', '/(\*|_)(.+?)\1/', '/~~(.+?)~~/'],
            ["{$L}strong{$R}$2{$L}/strong{$R}", "{$L}em{$R}$2{$L}/em{$R}", "{$L}del{$R}$1{$L}/del{$R}"],
            $s,
        );

        // Finalize
        $s = htmlspecialchars($s, ENT_NOQUOTES);
        $s = preg_replace_callback('/\x00(\d+)\x00/', static fn($m) => $b[(int) $m[1]], $s);
        $s = str_replace([$L, $R], ['<', '>'], $s);

        // Paragraphs
        return implode("\n", array_map(
            static fn($p) => (
                ($p = trim($p)) === ''
                    ? ''
                    : (
                        preg_match('/^<(?:h[1-6]|ul|ol|blockquote|hr|pre|table)/', $p)
                            ? $p
                            : '<p>'
                            . preg_replace('/\n/', '<br>', $p)
                            . '</p>'
                    )
            ),
            preg_split('/\n{2,}/', trim($s)),
        ))
            |> trim(...);
    }
}
