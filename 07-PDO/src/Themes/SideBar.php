<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Themes;

use SPE\PDO\Core\Theme;

final class SideBar extends Theme {
    #[\Override] public function render(): string {
        ['o' => $o, 't' => $t] = $this->ctx->in;
        $n1 = $this->ctx->nav
            |> (fn($n) => array_map(fn($p) => sprintf(
                '<a href="?o=%s"%s>%s</a>',
                $p[1], $o === $p[1] ? ' class="active"' : '', $p[0]
            ), $n))
            |> (fn($a) => implode('', $a));
        $n2 = $this->ctx->themes
            |> (fn($n) => array_map(fn($p) => sprintf(
                '<a href="?t=%s"%s>%s</a>',
                $p[1], $t === $p[1] ? ' class="active"' : '', $p[0]
            ), $n))
            |> (fn($a) => implode('', $a));
        $body = <<<HTML
<nav class="topnav">
    <button class="menu-toggle">☰</button>
    <h1><a class="brand" href="/">🐘 PDO PHP Example</a></h1>
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
}
