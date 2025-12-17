<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Themes;

use SPE\Session\Core\Theme;

final readonly class TopNav extends Theme {
    #[\Override] public function html(): string {
        ['doc' => $doc, 'head' => $head, 'main' => $main, 'foot' => $foot] = $this->ctx->out;
        $nav1 = $this->nav($this->ctx->nav1);
        $nav2 = $this->nav($this->ctx->nav2, 't');
        return <<<HTML
        <!DOCTYPE html><html lang="en"><head>
            <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
            <title>$doc [TopNav]</title><link rel="stylesheet" href="/spe.css">
        </head><body>
            <nav class="topnav"><a class="brand" href="../">« $head</a>
                <div class="topnav-links">$nav1 | $nav2</div>
                <button class="theme-toggle" id="theme-icon">🌙</button>
                <button class="menu-toggle">☰</button>
            </nav>
            <main class="container mt-3">$main</main>
            <footer class="container text-center mt-3"><small>$foot</small></footer>
        <script src="/spe.js"></script></body></html>
        HTML;
    }
}
