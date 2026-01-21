<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Usage: php -S localhost:8080 index.php

namespace {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Static files at root (base.css, site.css, base.js)
    if ($uri !== '/' && is_file(__DIR__ . $uri)) return false;

    // Chapter pattern: /XX-Name/... -> /XX-Name/public/...
    if (preg_match('#^/(\d{2}-[^/]+)(/.*)?$#', $uri, $m) && is_dir($pub = __DIR__ . "/{$m[1]}/public")) {
        if (is_file($f = $pub . ($m[2] ?? '/'))) {
            // Serve static file with correct content type
            $ext = pathinfo($f, PATHINFO_EXTENSION);
            $types = ['css' => 'text/css', 'js' => 'text/javascript', 'webp' => 'image/webp',
                      'png' => 'image/png', 'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'svg' => 'image/svg+xml'];
            header('Content-Type: ' . ($types[$ext] ?? mime_content_type($f)));
            return readfile($f);
        }
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
                ['github', 'GitHub', 'https://github.com/markc/spe'],
                ['video', 'Tutorials', 'https://www.youtube.com/playlist?list=PLM0Did14jsitwKl7RYaVrUWnG1GkRBO4B'],
                ['book-open', 'Docs', 'docs/'],
            ],
            public array $chapters = [
                ['00', 'Tutorial', 'Automated video generation pipeline using Playwright browser capture and Piper text-to-speech'],
                ['01', 'Simple', 'Single-file anonymous class demonstrating PHP 8.5 pipe operator with first-class callables'],
                ['02', 'Styled', 'Custom CSS framework with CSS variables, automatic dark mode detection, and toast notifications'],
                ['03', 'Plugins', 'Plugin architecture introducing the CRUDL pattern for Create, Read, Update, Delete, List operations'],
                ['04', 'Themes', 'Model/View separation with three switchable layout themes: Simple, TopNav, and SideBar'],
                ['05', 'Autoload', 'PSR-4 autoloading via Composer with proper namespacing and directory structure'],
                ['06', 'Session', 'PHP session management with sticky URL parameters and flash messages for user feedback'],
                ['07', 'PDO', 'SQLite database integration using PDO wrapper class and QueryType enum for fetch modes'],
                ['08', 'Users', 'User management system with full CRUDL operations and profile handling'],
                ['09', 'Blog', 'Complete CMS featuring authentication, blog posts, static pages, and documentation'],
                ['10', 'Htmx', 'SPA-like blog with htmx for partial page updates, live search, and inline CRUD'],
                ['11', 'HCP', 'Lightweight hosting control panel for managing Nginx vhosts, DNS zones, and SSL certificates'],
            ],
            public array $layouts = [
                ['layout-template', 'Simple', 'Simple'],
                ['navigation', 'TopNav', 'TopNav'],
                ['panel-left', 'SideBar', 'SideBar'],
            ]
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
            $rows = $this->ctx->chapters
                |> (fn($c) => array_map(fn($ch) => sprintf(
                    '<tr><td><a href="%s-%s/"><strong>%s %s</strong></a></td><td>%s</td></tr>',
                    $ch[0], $ch[1], $ch[0], $ch[1], $ch[2]
                ), $c))
                |> (fn($a) => implode("\n", $a));
            return "<table class=\"chapter-table\">$rows</table>";
        }

        private function nav(): string {
            return $this->ctx->nav
                |> (fn($n) => array_map(fn($p) => sprintf(
                    '<a href="%s"><i data-lucide="%s"></i> %s</a>',
                    $p[2], $p[0], $p[1]
                ), $n))
                |> (fn($a) => implode(' ', $a));
        }

        private function dropdown(): string {
            $t = $this->ctx->in['t'];
            $links = $this->ctx->layouts
                |> (fn($n) => array_map(fn($p) => sprintf(
                    '<a href="?t=%s"%s><i data-lucide="%s"></i> %s</a>',
                    $p[2], $t === $p[2] ? ' class="active"' : '', $p[0], $p[1]
                ), $n))
                |> (fn($a) => implode('', $a));
            return "<div class=\"dropdown\"><span class=\"dropdown-toggle\"><i data-lucide=\"layout-grid\"></i> Layout</span><div class=\"dropdown-menu\">$links</div></div>";
        }

        private function colors(): string {
            return <<<HTML
            <div class="dropdown"><span class="dropdown-toggle"><i data-lucide="swatch-book"></i> Colors</span><div class="dropdown-menu">
            <a href="#" data-scheme="default"><i data-lucide="circle"></i> Stone</a>
            <a href="#" data-scheme="ocean"><i data-lucide="waves"></i> Ocean</a>
            <a href="#" data-scheme="forest"><i data-lucide="trees"></i> Forest</a>
            <a href="#" data-scheme="sunset"><i data-lucide="sunset"></i> Sunset</a>
            </div></div>
            HTML;
        }

        private function html(string $theme, string $body): string {
            return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$this->ctx->out['doc']} [$theme]</title>
    <link rel="stylesheet" href="/base.css">
    <link rel="stylesheet" href="/site.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>(function(){const t=localStorage.getItem("base-theme"),s=localStorage.getItem("base-scheme"),c=t||(matchMedia("(prefers-color-scheme:dark)").matches?"dark":"light");document.documentElement.className=c+(s&&s!=="default"?" scheme-"+s:"")})();</script>
</head>
<body>
$body
<script src="/base.js"></script>
</body>
</html>
HTML;
        }

        public function Simple(): string {
            $nav = $this->nav();
            $dd = $this->dropdown();
            $colors = $this->colors();
            $list = $this->chapterList();
            $body = <<<HTML
<div class="container">
    <header class="mt-4"><h1><a class="brand" href="/">🐘 <span>Simple PHP Engine</span></a></h1></header>
    <nav class="card flex mb-4">
        $nav $dd $colors
        <span class="ml-auto"><button class="theme-toggle" id="theme-icon"><i data-lucide="moon"></i></button></span>
    </nav>
    <main>
        <div class="card">
            <h2>Chapters</h2>
            <p>A progressive PHP 8.5 micro-framework tutorial in 12 chapters</p>
            $list
        </div>
    </main>
    <footer class="text-center mt-4"><small>© 2015-2026 Mark Constable (MIT License)</small></footer>
</div>
HTML;
            return $this->html('Simple', $body);
        }

        public function TopNav(): string {
            $nav = $this->nav();
            $dd = $this->dropdown();
            $colors = $this->colors();
            $list = $this->chapterList();
            $body = <<<HTML
<nav class="topnav">
    <h1><a class="brand" href="/">🐘 <span>Simple PHP Engine</span></a></h1>
    <div class="topnav-links">$nav $dd $colors</div>
    <button class="theme-toggle" id="theme-icon"><i data-lucide="moon"></i></button>
    <button class="menu-toggle"><i data-lucide="menu"></i></button>
</nav>
<div class="container">
    <main class="mt-4 mb-4">
        <div class="card">
            <h2>Chapters</h2>
            <p>A progressive PHP 8.5 micro-framework tutorial in 12 chapters</p>
            $list
        </div>
    </main>
    <footer class="text-center"><small>© 2015-2026 Mark Constable (MIT License)</small></footer>
</div>
HTML;
            return $this->html('TopNav', $body);
        }

        public function SideBar(): string {
            $t = $this->ctx->in['t'];
            $list = $this->chapterList();
            $n1 = $this->ctx->nav
                |> (fn($n) => array_map(fn($p) => sprintf(
                    '<a href="%s" title="%s" data-icon="%s"><i data-lucide="%s"></i> %s</a>',
                    $p[2], $p[1], $p[0], $p[0], $p[1]
                ), $n))
                |> (fn($a) => implode('', $a));
            $n2 = $this->ctx->layouts
                |> (fn($n) => array_map(fn($p) => sprintf(
                    '<a href="?t=%s"%s title="%s" data-icon="%s"><i data-lucide="%s"></i> %s</a>',
                    $p[2], $t === $p[2] ? ' class="active"' : '', $p[1], $p[0], $p[0], $p[1]
                ), $n))
                |> (fn($a) => implode('', $a));
            $body = <<<HTML
<nav class="topnav">
    <button class="menu-toggle"><i data-lucide="menu"></i></button>
    <h1><a class="brand" href="/">🐘 <span>Simple PHP Engine</span></a></h1>
    <button class="theme-toggle" id="theme-icon"><i data-lucide="moon"></i></button>
</nav>
<div class="sidebar-layout">
    <aside class="sidebar">
        <div class="sidebar-group">
            <div class="sidebar-group-title" data-icon="link"><i data-lucide="link"></i> Links</div>
            <nav>$n1</nav>
        </div>
        <div class="sidebar-group">
            <div class="sidebar-group-title" data-icon="layout-grid"><i data-lucide="layout-grid"></i> Layout</div>
            <nav>$n2</nav>
        </div>
        <div class="sidebar-group">
            <div class="sidebar-group-title" data-icon="swatch-book"><i data-lucide="swatch-book"></i> Colors</div>
            <nav>
                <a href="#" data-scheme="default" title="Stone" data-icon="circle"><i data-lucide="circle"></i> Stone</a>
                <a href="#" data-scheme="ocean" title="Ocean" data-icon="waves"><i data-lucide="waves"></i> Ocean</a>
                <a href="#" data-scheme="forest" title="Forest" data-icon="trees"><i data-lucide="trees"></i> Forest</a>
                <a href="#" data-scheme="sunset" title="Sunset" data-icon="sunset"><i data-lucide="sunset"></i> Sunset</a>
            </nav>
        </div>
        <button class="sidebar-toggle" aria-label="Toggle sidebar"></button>
    </aside>
    <div class="sidebar-main">
        <main class="mt-4 mb-4">
            <div class="card">
                <h2>Chapters</h2>
                <p>A progressive PHP 8.5 micro-framework tutorial in 12 chapters</p>
                $list
            </div>
        </main>
        <footer class="text-center"><small>© 2015-2026 Mark Constable (MIT License)</small></footer>
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
