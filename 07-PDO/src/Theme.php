<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO;

final class Theme {
    public function __construct(private App $c) {}

    private function nav(array $items, string $p = 'o'): string {
        ['o' => $o, 't' => $t] = $this->c->in;
        return $items |> (fn($a) => array_map(fn($n) => sprintf('<a href="?o=%s&t=%s"%s>%s</a>',
            $p === 'o' ? $n[1] : $o, $p === 't' ? $n[1] : $t, $this->c->in[$p] === $n[1] ? ' class="active"' : '', $n[0]), $a))
            |> (fn($l) => implode(' ', $l));
    }

    private function dd(): string {
        $o = $this->c->in['o'];
        $links = $this->c->n2 |> (fn($a) => array_map(fn($n) => sprintf('<a href="?o=%s&t=%s">%s</a>', $o, $n[1], $n[0]), $a))
            |> (fn($l) => implode('', $l));
        return "<div class=dropdown><span class=dropdown-toggle>🎨 Themes</span><div class=dropdown-menu>$links</div></div>";
    }

    private function html(string $theme, string $body): string {
        $doc = $this->c->out['doc'];
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>$doc [$theme]</title>
    <link rel="stylesheet" href="/spe.css">
</head>
<body>
$body
    <script src="/spe.js"></script>
</body>
</html>
HTML;
    }

    public function Simple(): string {
        extract($this->c->out); $n1 = $this->nav($this->c->n1); $dd = $this->dd();
        $body = <<<HTML
    <div class="container">
        <header><h1><a href="../">« $head</a></h1></header>
        <nav class="flex nav-card">
            $n1 $dd
            <span class="ml-auto"><button class="theme-toggle" id="theme-icon">🌙</button></span>
        </nav>
        <main>$main</main>
        <footer class="text-center mt-3"><small>$foot</small></footer>
    </div>
HTML;
        return $this->html('Simple', $body);
    }

    public function TopNav(): string {
        extract($this->c->out); $n1 = $this->nav($this->c->n1); $dd = $this->dd();
        $body = <<<HTML
    <nav class="topnav">
        <a class="brand" href="../">« $head</a>
        <div class="topnav-links">$n1 $dd</div>
        <button class="theme-toggle" id="theme-icon">🌙</button>
        <button class="menu-toggle">☰</button>
    </nav>
    <main class="container mt-4">$main</main>
    <footer class="container text-center mt-3"><small>$foot</small></footer>
HTML;
        return $this->html('TopNav', $body);
    }

    public function SideBar(): string {
        extract($this->c->out); ['o' => $o, 't' => $t] = $this->c->in;
        $n1 = $this->c->n1 |> (fn($a) => array_map(fn($n) => sprintf('<a href="?o=%s&t=%s"%s>%s</a>',
            $n[1], $t, $n[1] === $o ? ' class="active"' : '', $n[0]), $a)) |> (fn($l) => implode('', $l));
        $n2 = $this->c->n2 |> (fn($a) => array_map(fn($n) => sprintf('<a href="?o=%s&t=%s"%s>%s</a>',
            $o, $n[1], $n[1] === $t ? ' class="active"' : '', $n[0]), $a)) |> (fn($l) => implode('', $l));
        $body = <<<HTML
    <nav class="topnav">
        <button class="menu-toggle">☰</button>
        <a class="brand" href="../">« $head</a>
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
            <main class="mt-4">$main</main>
            <footer class="text-center mt-3"><small>$foot</small></footer>
        </div>
    </div>
HTML;
        return $this->html('SideBar', $body);
    }
}
