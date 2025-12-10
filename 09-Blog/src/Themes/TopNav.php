<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Themes;

use SPE\Blog\Core\{Ctx, Theme};

final class TopNav extends Theme {

    public function html(): string {
        extract($this->ctx->out);
        $pages = $this->pagesNav();
        $admin = $this->adminDropdown();
        $themes = $this->themesDropdown();
        $auth = $this->authNav();
        $toast = $this->toast();
        return <<<HTML
        <!DOCTYPE html><html lang="en"><head>
            <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
            <title>$doc [TopNav]</title><link rel="stylesheet" href="/spe.css">
        </head><body>
            $toast
            <nav class="topnav"><a class="brand" href="../">« $head</a>
                <div class="topnav-links">$pages $admin $themes $auth</div>
                <button class="theme-toggle" id="theme-icon">🌙</button>
                <button class="menu-toggle">☰</button>
            </nav>
            <main class="container mt-3">$main</main>
            <footer class="container text-center mt-3"><small>$foot</small></footer>
        <script src="/spe.js"></script></body></html>
        HTML;
    }
}
