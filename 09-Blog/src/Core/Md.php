<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

use Uri\Rfc3986\Uri;

/**
 * A small, safe Markdown renderer built as a pipe of pure steps. The input is
 * escaped first, so anything not turned into a tag here is shown as literal text,
 * and link/image targets are checked against an allowed set of URL schemes.
 */
final class Md
{
    private const array SCHEMES = ['http', 'https', 'mailto'];

    public static function render(string $markdown): string
    {
        return $markdown
            |> (static fn(string $s) => str_replace("\r\n", "\n", $s))
            |> htmlspecialchars(...)
            |> self::codeBlocks(...)
            |> self::blocks(...);
    }

    /** Fenced ```code``` first, so nothing inside is treated as Markdown. */
    private static function codeBlocks(string $s): string
    {
        return preg_replace_callback('/^```\n(.*?)\n```$/ms', static fn(array $m) => '<pre><code>' . $m[1] . '</code></pre>', $s);
    }

    private static function blocks(string $s): string
    {
        $html = [];
        foreach (preg_split('/\n{2,}/', trim($s)) as $block) {
            $block = trim($block);
            if ($block === '' || str_starts_with($block, '<pre>')) {
                $html[] = $block;
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
        $s = preg_replace('/`([^`]+)`/', '<code>$1</code>', $s);
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
