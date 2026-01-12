<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Themes;

use SPE\Blog\Core\Theme;

final class SideBar extends Theme
{
    #[\Override]
    public function render(): string
    {
        $path = '/' . trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $t = $this->ctx->in['t'];

        // Navigation links (supports both clean URLs and query strings)
        $n1 = $this->ctx->nav
            |> (fn($n) => array_map(fn($p) => sprintf(
                '<a href="%s"%s>%s</a>',
                $p[1],
                $this->isActiveNav($p[1], $path) ? ' class="active"' : '',
                $p[0],
            ), $n))
            |> (static fn($a) => implode('', $a));

        // Theme links
        $n2 = $this->ctx->themes
            |> (static fn($n) => array_map(static fn($p) => sprintf(
                '<a href="?t=%s"%s>%s</a>',
                $p[1],
                $t === $p[1] ? ' class="active"' : '',
                $p[0],
            ), $n))
            |> (static fn($a) => implode('', $a));

        $auth = $this->authNav();
        $body = <<<HTML
        <nav class="topnav">
            <button class="menu-toggle">☰</button>
            <h1><a class="brand" href="/">🐘 Blog PHP Example</a></h1>
            <span class="topnav-links">$auth</span>
            <button class="theme-toggle" id="theme-icon">🌙</button>
        </nav>
        <div class="sidebar-layout">
            <aside class="sidebar">
                <div class="sidebar-group">
                    <div class="sidebar-group-title">Pages</div>
                    <nav>$n1</nav>
                </div>
                <div class="sidebar-group">
                    <div class="sidebar-group-title">Themes</div>
                    <nav>$n2</nav>
                </div>
            </aside>
            <div class="sidebar-main">
                <main class="mt-2">{$this->out['main']}</main>
                <footer class="text-center mt-3"><small>© 2015-2025 Mark Constable (MIT License)</small></footer>
            </div>
        </div>
        HTML;
        return $this->html('SideBar', $body);
    }

    private function isActiveNav(string $href, string $path): bool
    {
        // Clean URL match
        if (str_starts_with($href, '/')) {
            return $href === $path || $href === '/' && $path === '/';
        }
        // Query string match
        if (str_starts_with($href, '?o=')) {
            $o = substr($href, 3);
            $pos = strpos($o, '&');
            if ($pos !== false)
                $o = substr($o, 0, $pos);
            return str_starts_with($this->ctx->in['o'], $o);
        }
        return false;
    }
}
