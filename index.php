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

// Main index page with app shell (dual sidebars)
namespace SPE\Router {
    readonly class Ctx {
        public function __construct(
            public array $out = ['doc' => 'SPE', 'page' => '🐘 Simple PHP Engine'],
            public array $nav = [
                ['book-open', 'Docs', 'docs/'],
                ['github', 'GitHub', 'https://github.com/markc/spe'],
                ['video', 'Tutorials', 'https://www.youtube.com/playlist?list=PLM0Did14jsitwKl7RYaVrUWnG1GkRBO4B'],
            ],
            public array $colors = [
                ['circle', 'Stone', 'default'],
                ['waves', 'Ocean', 'ocean'],
                ['trees', 'Forest', 'forest'],
                ['sunset', 'Sunset', 'sunset'],
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
        ) {}
    }

    final class Theme {
        public function __construct(private Ctx $ctx) {}

        public function render(): string {
            $body = $this->topnav() . $this->sidebar('left') . $this->sidebar('right') . $this->main();
            return $this->html($body);
        }

        private function chapterList(): string {
            $rows = $this->ctx->chapters
                |> (fn($c) => array_map(fn($ch) => sprintf(
                    '<tr><td><a href="%s-%s/"><strong>%s %s</strong></a></td><td>%s</td></tr>',
                    $ch[0], $ch[1], $ch[0], $ch[1], $ch[2]
                ), $c))
                |> (fn($a) => implode("\n", $a));
            return "<table class=\"chapter-table\">$rows</table>";
        }

        private function navLinks(): string {
            return $this->ctx->nav
                |> (fn($n) => array_map(fn($p) => sprintf(
                    '<a href="%s" data-icon="%s"><i data-lucide="%s"></i> %s</a>',
                    $p[2], $p[0], $p[0], $p[1]
                ), $n))
                |> (fn($a) => implode('', $a));
        }

        private function colorLinks(): string {
            return $this->ctx->colors
                |> (fn($c) => array_map(fn($p) => sprintf(
                    '<a href="#" data-scheme="%s" data-icon="%s"><i data-lucide="%s"></i> %s</a>',
                    $p[2], $p[0], $p[0], $p[1]
                ), $c))
                |> (fn($a) => implode('', $a));
        }

        private function topnav(): string {
            return <<<HTML
<nav class="topnav">
    <button class="menu-toggle" data-sidebar="left"><i data-lucide="menu"></i></button>
    <h1><a class="brand" href="./"><span>{$this->ctx->out['page']}</span></a></h1>
    <button class="menu-toggle" data-sidebar="right"><i data-lucide="menu"></i></button>
</nav>
HTML;
        }

        private function sidebar(string $side): string {
            $nav = $side === 'left'
                ? $this->navLinks()
                : $this->colorLinks()
                  . '<div class="sidebar-divider"></div>'
                  . '<a href="#" onclick="Base.toggleTheme();return false" data-icon="moon"><i data-lucide="moon"></i> Toggle Theme</a>';
            $title = $side === 'left' ? 'Navigation' : 'Settings';
            $icon = $side === 'left' ? 'compass' : 'sliders-horizontal';
            return <<<HTML
<aside class="sidebar sidebar-{$side}">
    <div class="sidebar-header">
        <span><i data-lucide="{$icon}"></i> {$title}</span>
        <button class="pin-toggle" data-sidebar="{$side}" title="Pin sidebar"><i data-lucide="pin"></i></button>
    </div>
    <nav>{$nav}</nav>
</aside>
HTML;
        }

        private function main(): string {
            $list = $this->chapterList();
            return <<<HTML
<main>
    <div class="card">
        <h2>Chapters</h2>
        <p>A progressive PHP 8.5 micro-framework tutorial in 12 chapters</p>
        $list
    </div>
</main>
<div class="overlay"></div>
HTML;
        }

        private function html(string $body): string {
            $doc = $this->ctx->out['doc'];
            return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$doc}</title>
    <link rel="stylesheet" href="base.css">
    <link rel="stylesheet" href="site.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>(function(){var s=JSON.parse(localStorage.getItem('base-state')||'{}'),t=s.theme,c=s.scheme,h=document.documentElement;h.className='preload '+(t||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light'))+(c&&c!=='default'?' scheme-'+c:'');})()</script>
</head>
<body>
{$body}
<script src="base.js"></script>
</body>
</html>
HTML;
        }
    }
}

namespace {
    echo (new \SPE\Router\Theme(new \SPE\Router\Ctx))->render();
}
