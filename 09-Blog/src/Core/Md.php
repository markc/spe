<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

use Uri\Rfc3986\Uri;

/**
 * A small, safe Markdown renderer built as a pipe of pure steps. The input is
 * escaped first, so anything not turned into a tag here is shown as literal text.
 * Code — fenced blocks and inline spans — is pulled out into placeholders before
 * anything else runs, so it is never re-parsed as Markdown, then restored at the
 * end. Link and image targets are checked against an allowed set of URL schemes.
 */
final class Md
{
    private const array SCHEMES = ['http', 'https', 'mailto'];

    public static function render(string $markdown): string
    {
        $code = [];
        // \x1A is our placeholder marker; strip any from the input so it cannot collide.
        $escaped = htmlspecialchars(str_replace(["\r\n", "\x1A"], ["\n", ''], $markdown));
        $stashed = self::stash($escaped, $code);   // pull code out into placeholders
        return strtr(self::blocks($stashed), $code); // parse, then restore the code
    }

    /** Replace fenced blocks and inline code with opaque tokens, banking their HTML. */
    private static function stash(string $s, array &$code): string
    {
        $s = preg_replace_callback('/```\n(.*?)\n```/s', static function (array $m) use (&$code) {
            $token = "\x1A" . count($code) . "\x1A";
            $code[$token] = '<pre><code>' . $m[1] . '</code></pre>';
            return "\n\n$token\n\n";
        }, $s);
        return preg_replace_callback('/`([^`]+)`/', static function (array $m) use (&$code) {
            $token = "\x1A" . count($code) . "\x1A";
            $code[$token] = '<code>' . $m[1] . '</code>';
            return $token;
        }, $s);
    }

    private static function blocks(string $s): string
    {
        $html = [];
        foreach (preg_split('/\n{2,}/', trim($s)) as $block) {
            $block = trim($block);
            if ($block === '' || preg_match('/^\x1A\d+\x1A$/', $block)) {
                $html[] = $block;                       // blank, or a banked code block
            } elseif (preg_match('/^#{1,3} /', $block)) {
                $level = strlen($block) - strlen(ltrim($block, '#'));
                $html[] = "<h$level>" . self::inline(ltrim(substr($block, $level))) . "</h$level>";
            } elseif (preg_match('/^(-|\d+\.) /', $block)) {
                $html[] = self::list($block);
            } else {
                $html[] = '<p>' . self::inline(str_replace("\n", "<br>\n", $block)) . '</p>';
            }
        }
        return implode("\n", $html);
    }

    private static function list(string $block): string
    {
        $ordered = (bool) preg_match('/^\d+\. /', $block);
        $items = array_map(
            static fn(string $line) => '<li>' . self::inline(preg_replace('/^(-|\d+\.) /', '', trim($line))) . '</li>',
            explode("\n", $block),
        );
        $tag = $ordered ? 'ol' : 'ul';
        return "<$tag>" . implode('', $items) . "</$tag>";
    }

    private static function inline(string $s): string
    {
        $s = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $s);
        $s = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $s);
        $s = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', static fn(array $m) => self::image($m[1], $m[2]), $s);
        return preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', static fn(array $m) => self::link($m[1], $m[2]), $s);
    }

    private static function link(string $text, string $url): string
    {
        $safe = self::safeUrl($url);
        return $safe === null ? $text : '<a href="' . $safe . '">' . $text . '</a>';
    }

    private static function image(string $alt, string $url): string
    {
        $safe = self::safeUrl($url);
        return $safe === null ? $alt : '<img src="' . $safe . '" alt="' . $alt . '">';
    }

    /** Allow only relative URLs and a short list of schemes; reject javascript: and friends. */
    private static function safeUrl(string $url): ?string
    {
        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        try {
            $scheme = new Uri($url)->getScheme();
        } catch (\Throwable) {
            return null;
        }
        if ($scheme !== null && !in_array(strtolower($scheme), self::SCHEMES, true)) {
            return null;
        }
        return htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
