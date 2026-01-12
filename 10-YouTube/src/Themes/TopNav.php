<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Themes;

use SPE\YouTube\Core\Theme;

/**
 * TopNav theme for YouTube Manager - horizontal navigation bar
 */
final class TopNav extends Theme
{
    #[\Override]
    public function html(): string
    {
        extract($this->ctx->out);
        $pages = $this->pagesNav();
        $themes = $this->themesDropdown();
        $auth = $this->authNav();
        $toast = $this->toast();

        return <<<HTML
        <!DOCTYPE html><html lang="en"><head>
            <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
            <meta name="color-scheme" content="light dark">
            <title>$doc [TopNav]</title><link rel="stylesheet" href="/base.css">
            <link rel="stylesheet" href="/site.css">
            <script>(function(){const t=localStorage.getItem("base-theme");document.documentElement.className=t||(matchMedia("(prefers-color-scheme:dark)").matches?"dark":"light")})();</script>
            <style>:root { --primary: #ff0000; }</style>
        </head><body>
            $toast
            <nav class="topnav"><a class="brand" href="../">« $head</a>
                <div class="topnav-links">$pages $themes $auth</div>
                <button class="theme-toggle" id="theme-icon">🌙</button>
                <button class="menu-toggle">☰</button>
            </nav>
            <main class="container mt-3">$main</main>
            <footer class="container text-center mt-3"><small>$foot</small></footer>
        <script src="/base.js"></script></body></html>
        HTML;
    }
}
