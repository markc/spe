<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Users\Themes;

use SPE\Users\Core\{Ctx, Theme};

final class Simple extends Theme {

    public function html(): string {
        extract($this->ctx->out);
        $nav1 = $this->nav($this->ctx->nav1);
        $nav2 = $this->nav($this->ctx->nav2, 't');
        return <<<HTML
        <!DOCTYPE html><html lang="en"><head>
            <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
            <title>$doc [Simple]</title><link rel="stylesheet" href="/spe.css">
        </head><body><div class="container">
            <header><h1><a href="../">« $head</a></h1></header>
            <nav class="flex">$nav1 | $nav2<span style="margin-left:auto"><button class="theme-toggle" id="theme-icon">🌙</button></span></nav>
            <main>$main</main>
            <footer class="text-center mt-3"><small>$foot</small></footer>
        </div><script src="/spe.js"></script></body></html>
        HTML;
    }
}
