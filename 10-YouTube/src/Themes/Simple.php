<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Themes;

use SPE\YouTube\Core\Theme;

/**
 * Simple theme for YouTube Manager - minimal layout
 */
final class Simple extends Theme
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
            <title>$doc [Simple]</title><link rel="stylesheet" href="/base.css">
            <link rel="stylesheet" href="/site.css">
            <script>(function(){const t=localStorage.getItem("base-theme");document.documentElement.className=t||(matchMedia("(prefers-color-scheme:dark)").matches?"dark":"light")})();</script>
            <style>:root { --primary: #ff0000; }</style>
        </head><body>
            $toast
            <header class="container">
                <h1><a href="../">«</a> $head</h1>
                <nav>$pages $themes $auth</nav>
            </header>
            <main class="container">$main</main>
            <footer class="container text-center mt-3"><small>$foot</small></footer>
        <script src="/base.js"></script></body></html>
        HTML;
    }
}
