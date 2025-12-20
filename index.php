<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)
// Usage: php -S localhost:8080 index.php

namespace {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Static files at root (spe.css, spe.js)
    if ($uri !== '/' && is_file(__DIR__ . $uri)) return false;

    // Chapter pattern: /XX-Name/... -> /XX-Name/public/...
    if (preg_match('#^/(\d{2}-[^/]+)(/.*)?$#', $uri, $m) && is_dir($pub = __DIR__ . "/{$m[1]}/public")) {
        if (is_file($f = $pub . ($m[2] ?? '/'))) return false;
        $_SERVER['SCRIPT_NAME'] = "/{$m[1]}/public/index.php";
        return require "$pub/index.php";
    }

    // Docs folder
    if (str_starts_with($uri, '/docs')) {
        if (is_file($f = __DIR__ . $uri)) return false;
        if (is_dir($f) && is_file("$f/index.html")) return require "$f/index.html";
    }
}

// Main index page with theme support
namespace SPE\Router {
    readonly class Ctx {
        public array $in;
        public function __construct(
            array $in = ['t' => 'Simple'],
            public array $out = ['doc' => 'SPE'],
            public array $nav = [
                ['🐙 GitHub', 'https://github.com/markc/spe'],
                ['🎬 Tutorials', 'https://www.youtube.com/playlist?list=PLM0Did14jsitwKl7RYaVrUWnG1GkRBO4B'],
                ['📚 Docs', 'docs/'],
            ],
            public array $chapters = [
                ['00', 'Tutorial', 'Video generation pipeline'],
                ['01', 'Simple', 'Single-file anonymous class, pipe operator'],
                ['02', 'Styled', 'Custom CSS, dark mode, toast notifications'],
                ['03', 'Plugins', 'Plugin architecture, CRUDL pattern'],
                ['04', 'Themes', 'Model/View separation, multiple layouts'],
                ['05', 'Autoload', 'PSR-4 autoloading via Composer'],
                ['06', 'Session', 'PHP session management'],
                ['07', 'PDO', 'SQLite database, QueryType enum'],
                ['08', 'Users', 'User management CRUDL'],
                ['09', 'Blog', 'Full CMS: Auth, Blog, Pages, Docs'],
                ['10', 'YouTube', 'OAuth, API integration'],
            ],
            public array $themes = [['🎨 Simple', 'Simple'], ['🎨 TopNav', 'TopNav'], ['🎨 SideBar', 'SideBar']]
        ) {
            $this->in = array_map(fn($k, $v) => ($_REQUEST[$k] ?? $v)
                |> trim(...)
                |> htmlspecialchars(...), array_keys($in), $in)
                |> (fn($v) => array_combine(array_keys($in), $v));
        }
    }

    final class Theme {
        public function __construct(private Ctx $ctx) {}

        private function chapterList(): string {
            return $this->ctx->chapters
                |> (fn($c) => array_map(fn($ch) => sprintf(
                    '<a href="%s-%s/">%s %s <span class="text-muted">— %s</span></a>',
                    $ch[0], $ch[1], $ch[0], $ch[1], $ch[2]
                ), $c))
                |> (fn($a) => implode("\n", $a));
        }

        private function nav(): string {
            return $this->ctx->nav
                |> (fn($n) => array_map(fn($p) => sprintf('<a href="%s">%s</a>', $p[1], $p[0]), $n))
                |> (fn($a) => implode(' ', $a));
        }

        private function dropdown(): string {
            $t = $this->ctx->in['t'];
            $links = $this->ctx->themes
                |> (fn($n) => array_map(fn($p) => sprintf(
                    '<a href="?t=%s"%s>%s</a>',
                    $p[1], $t === $p[1] ? ' class="active"' : '', $p[0]
                ), $n))
                |> (fn($a) => implode('', $a));
            return "<div class=\"dropdown\"><span class=\"dropdown-toggle\">🎨 Themes</span><div class=\"dropdown-menu\">$links</div></div>";
        }

        private function html(string $theme, string $body): string {
            return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>{$this->ctx->out['doc']} [$theme]</title>
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
            $nav = $this->nav();
            $dd = $this->dropdown();
            $list = $this->chapterList();
            $body = <<<HTML
<div class="container">
    <header><h1>🐘 Simple PHP Examples</h1></header>
    <nav class="card flex">
        $nav $dd
        <span class="ml-auto"><button class="theme-toggle" id="theme-icon">🌙</button></span>
    </nav>
    <main>
        <div class="card">
            <h2>Chapters</h2>
            <p>A progressive PHP 8.5 micro-framework tutorial in 11 chapters</p>
            <nav class="chapter-list">$list</nav>
        </div>
    </main>
    <footer class="text-center mt-3"><small>© 2015-2025 Mark Constable (MIT License)</small></footer>
</div>
HTML;
            return $this->html('Simple', $body);
        }

        public function TopNav(): string {
            $nav = $this->nav();
            $dd = $this->dropdown();
            $list = $this->chapterList();
            $body = <<<HTML
<nav class="topnav">
    <a class="brand" href="/">🐘 SPE</a>
    <div class="topnav-links">$nav $dd</div>
    <button class="theme-toggle" id="theme-icon">🌙</button>
    <button class="menu-toggle">☰</button>
</nav>
<main class="container mt-4">
    <div class="card">
        <h2>Chapters</h2>
        <p>A progressive PHP 8.5 micro-framework tutorial in 11 chapters</p>
        <nav class="chapter-list">$list</nav>
    </div>
</main>
<footer class="container text-center mt-3"><small>© 2015-2025 Mark Constable (MIT License)</small></footer>
HTML;
            return $this->html('TopNav', $body);
        }

        public function SideBar(): string {
            $t = $this->ctx->in['t'];
            $list = $this->chapterList();
            $n1 = $this->ctx->nav
                |> (fn($n) => array_map(fn($p) => sprintf('<a href="%s">%s</a>', $p[1], $p[0]), $n))
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
    <a class="brand" href="/">🐘 Simple PHP Examples</a>
    <button class="theme-toggle" id="theme-icon">🌙</button>
</nav>
<div class="sidebar-layout">
    <aside class="sidebar">
        <div class="sidebar-group">
            <div class="sidebar-group-title">Links</div>
            <nav>$n1</nav>
        </div>
        <div class="sidebar-group">
            <div class="sidebar-group-title">Themes</div>
            <nav>$n2</nav>
        </div>
    </aside>
    <div class="sidebar-main">
        <main class="mt-4">
            <div class="card">
                <h2>Chapters</h2>
                <p>A progressive PHP 8.5 micro-framework tutorial in 11 chapters</p>
                <nav class="chapter-list">$list</nav>
            </div>
        </main>
        <footer class="text-center mt-3"><small>© 2015-2025 Mark Constable (MIT License)</small></footer>
    </div>
</div>
HTML;
            return $this->html('SideBar', $body);
        }
    }
}

namespace {
    $ctx = new \SPE\Router\Ctx;
    echo (new \SPE\Router\Theme($ctx))->{$ctx->in['t']}();
}
