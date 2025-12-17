<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Themes;

use SPE\PDO\Core\{Ctx, Theme};

final class SideBar extends Theme {

    public function html(): string {
        extract($this->ctx->out);
        ['o' => $o, 't' => $t] = $this->ctx->in;
        $nav1 = $this->ctx->nav1 |> (fn($a) => array_map(fn($n) => sprintf(
            '<a href="?o=%s&t=%s"%s>%s</a>', $n[1], $t, $n[1] === $o ? ' class="active"' : '', $n[0]), $a)) |> (fn($l) => implode('', $l));
        $nav2 = $this->ctx->nav2 |> (fn($a) => array_map(fn($n) => sprintf(
            '<a href="?o=%s&t=%s"%s>%s</a>', $o, $n[1], $n[1] === $t ? ' class="active"' : '', $n[0]), $a)) |> (fn($l) => implode('', $l));
        return <<<HTML
        <!DOCTYPE html><html lang="en"><head>
            <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
            <title>$doc [SideBar]</title><link rel="stylesheet" href="/spe.css">
        </head><body>
            <nav class="topnav"><button class="menu-toggle">☰</button><a class="brand" href="../">« $head</a>
                <button class="theme-toggle" id="theme-icon">🌙</button></nav>
            <div class="sidebar-layout">
                <aside class="sidebar">
                    <div class="sidebar-group"><div class="sidebar-group-title">Pages</div><nav>$nav1</nav></div>
                    <div class="sidebar-group"><div class="sidebar-group-title">Themes</div><nav>$nav2</nav></div>
                </aside>
                <div class="sidebar-main"><main>$main</main>
                    <footer class="text-center mt-3"><small>$foot</small></footer>
                </div>
            </div>
        <script src="/spe.js"></script></body></html>
        HTML;
    }
}
