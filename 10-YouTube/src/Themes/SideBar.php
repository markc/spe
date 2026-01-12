<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Themes;

use SPE\YouTube\Core\Theme;

/**
 * SideBar theme for YouTube Manager - vertical sidebar navigation
 */
final class SideBar extends Theme
{
    #[\Override]
    public function html(): string
    {
        extract($this->ctx->out);
        $sidebarNav = $this->buildSidebarNav();
        $auth = $this->authNav();
        $toast = $this->toast();

        return <<<HTML
        <!DOCTYPE html><html lang="en"><head>
            <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
            <meta name="color-scheme" content="light dark">
            <title>$doc [SideBar]</title><link rel="stylesheet" href="/base.css">
            <link rel="stylesheet" href="/site.css">
            <script>(function(){const t=localStorage.getItem("base-theme");document.documentElement.className=t||(matchMedia("(prefers-color-scheme:dark)").matches?"dark":"light")})();</script>
            <style>:root { --primary: #ff0000; }</style>
        </head><body>
            $toast
            <nav class="topnav"><button class="menu-toggle">☰</button><a class="brand" href="../">« $head</a>
                <span style="margin-left:auto">$auth</span>
                <button class="theme-toggle" id="theme-icon">🌙</button></nav>
            <div class="sidebar-layout">
                <aside class="sidebar">
                    $sidebarNav
                </aside>
                <div class="sidebar-main"><main>$main</main>
                    <footer class="text-center mt-3"><small>$foot</small></footer>
                </div>
            </div>
        <script src="/base.js"></script></body></html>
        HTML;
    }

    private function buildSidebarNav(): string
    {
        $html = '';

        // Pages section
        $pages = $this->ctx->navPages;
        if (!empty($pages)) {
            $current = $this->ctx->in['o'] ?? '';
            $links = $pages
                |> (static fn($items) => array_map(static fn($n) => sprintf(
                    '<a href="?o=%s"%s>%s</a>',
                    $n[1],
                    $n[1] === $current ? ' class="active"' : '',
                    $n[0],
                ), $items))
                |> (static fn($l) => implode('', $l));
            $html .=
                '<div class="sidebar-group"><div class="sidebar-group-title">Navigation</div><nav>'
                . $links
                . '</nav></div>';
        }

        // Themes section - preserves current URL params
        $themes = $this->ctx->nav2;
        $currentTheme = $this->ctx->in['t'] ?? '';
        if (!empty($themes)) {
            $links = $themes
                |> (static fn($items) => array_map(static fn($n) => sprintf(
                    '<a href="%s"%s>%s</a>',
                    $_GET
                        |> (static fn($p) => [...$p, 't' => $n[1]])
                        |> http_build_query(...)
                        |> (static fn($q) => "?$q"),
                    $n[1] === $currentTheme ? ' class="active"' : '',
                    $n[0],
                ), $items))
                |> (static fn($l) => implode('', $l));
            $html .=
                '<div class="sidebar-group"><div class="sidebar-group-title">Themes</div><nav>'
                . $links
                . '</nav></div>';
        }

        return $html;
    }
}
