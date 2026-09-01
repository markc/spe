<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Usage: php -S localhost:8080 index.php

namespace {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Reject any path segment that could escape the directory being served
    if (str_contains($uri, '..')) {
        http_response_code(404);
        return true;
    }

    // Static files at root (base.css, site.css, base.js)
    if ($uri !== '/' && is_file(__DIR__ . $uri)) return false;

    // Chapter pattern: /XX-Name/... -> /XX-Name/public/...
    if (preg_match('#^/(\d{2}-[^/]+)(/.*)?$#', $uri, $m) && is_dir($pub = __DIR__ . "/{$m[1]}/public")) {
        $rest = $m[2] ?? '/';
        if ($rest !== '/' && $rest !== '/index.php') {
            // A specific file was requested under the chapter: serve it if it exists
            // and is inside public/, otherwise it is a genuine 404 — not a page.
            if (is_file($f = $pub . $rest) && str_starts_with((string) realpath($f), realpath($pub) . '/')) {
                $ext = pathinfo($f, PATHINFO_EXTENSION);
                $types = ['css' => 'text/css', 'js' => 'text/javascript', 'webp' => 'image/webp',
                          'png' => 'image/png', 'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'svg' => 'image/svg+xml'];
                header('Content-Type: ' . ($types[$ext] ?? mime_content_type($f)));
                return readfile($f);
            }
            http_response_code(404);
            return true;
        }
        $_SERVER['SCRIPT_NAME'] = "/{$m[1]}/public/index.php";
        return require "$pub/index.php";
    }

    // Docs folder
    if (str_starts_with($uri, '/docs')) {
        if (is_file($f = __DIR__ . $uri)) return false;
        if (is_dir($f) && is_file("$f/index.html")) return require "$f/index.html";
        http_response_code(404);
        return true;
    }

    // The chapter index is only the site root; anything else is not a page.
    if ($uri !== '/') {
        http_response_code(404);
        return true;
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
                ['oklch(50% 0.12 220)', 'Ocean', 'default'],
                ['oklch(47% 0.2 25)', 'Crimson', 'crimson'],
                ['oklch(45% 0.05 60)', 'Stone', 'stone'],
                ['oklch(49% 0.12 150)', 'Forest', 'forest'],
                ['oklch(52% 0.16 45)', 'Sunset', 'sunset'],
                ['oklch(50% 0 0)', 'Mono', 'mono'],
            ],
            public array $chapters = [
                ['01', 'Simple', 'Single-file anonymous class demonstrating PHP 8.5 pipe operator with first-class callables'],
                ['02', 'Styled', 'Custom CSS framework with CSS variables, automatic dark mode detection, and toast notifications'],
                ['03', 'Plugins', 'Plugin architecture introducing the CRUDL pattern for Create, Read, Update, Delete, List operations'],
                ['04', 'Views', 'Model returns data, View returns HTML, Theme wraps it; escape at output'],
                ['05', 'Autoload', 'PSR-4 autoloading via Composer with proper namespacing and directory structure'],
                ['06', 'Session', 'PHP session management with sticky URL parameters and flash messages for user feedback'],
                ['07', 'PDO', 'SQLite database integration using PDO wrapper class and QueryType enum for fetch modes'],
                ['08', 'Auth', 'Identity: users, password hashing, login/logout, roles, access control'],
                ['09', 'Blog', 'Complete CMS featuring authentication, blog posts, static pages, and documentation'],
            ],
        ) {}
    }

    final class Theme {
        public function __construct(private Ctx $ctx) {}

        public function render(): string {
            $nav = $this->navLinks();
            $chapters = $this->chapterLinks();
            $schemes = $this->schemeButtons();
            $main = $this->main();

            return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$this->ctx->out['doc']}</title>
    <link rel="stylesheet" href="base.css">
    <link rel="stylesheet" href="site.css">
    <link rel="stylesheet" href="spe.css">
    <script src="https://unpkg.com/lucide@1.33.0/dist/umd/lucide.min.js"></script>
    <script>(function(){var s=JSON.parse(localStorage.getItem('base-state')||'{}'),t=s.theme,c=s.scheme,h=document.documentElement;h.className='preload '+(t||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light'))+(c&&c!=='default'?' scheme-'+c:'')+(s.width==='wide'?' wide':(s.width==='narrow'?' narrow':''));})()</script>
</head>
<body>
<button class="menu-toggle" data-sidebar="left"><i data-lucide="menu"></i></button>
<button class="menu-toggle" data-sidebar="right"><i data-lucide="menu"></i></button>
<nav class="topnav">
    <h1><a class="brand" href="./"><span>{$this->ctx->out['page']}</span></a></h1>
</nav>
<aside class="sidebar sidebar-left">
    <div class="carousel-header">
        <div class="carousel-nav">
            <button class="carousel-chevron" data-sidebar="left" data-dir="prev"><i data-lucide="chevron-left"></i></button>
            <div class="carousel-dots">
                <button class="carousel-dot active" data-sidebar="left" data-panel="0"></button>
                <button class="carousel-dot" data-sidebar="left" data-panel="1"></button>
            </div>
            <button class="carousel-chevron" data-sidebar="left" data-dir="next"><i data-lucide="chevron-right"></i></button>
        </div>
        <button class="pin-toggle" data-sidebar="left" title="Pin sidebar"><i data-lucide="pin"></i></button>
    </div>
    <div class="panel-viewport">
        <div class="panel-track">
            <div class="panel">
                <div class="panel-title">Navigation</div>
                <div class="panel-content"><nav>{$nav}</nav></div>
            </div>
            <div class="panel">
                <div class="panel-title">Chapters</div>
                <div class="panel-content"><nav>{$chapters}</nav></div>
            </div>
        </div>
    </div>
</aside>
<aside class="sidebar sidebar-right">
    <div class="carousel-header">
        <button class="pin-toggle" data-sidebar="right" title="Pin sidebar"><i data-lucide="pin"></i></button>
        <div class="carousel-nav">
            <button class="carousel-chevron" data-sidebar="right" data-dir="prev"><i data-lucide="chevron-left"></i></button>
            <div class="carousel-dots">
                <button class="carousel-dot active" data-sidebar="right" data-panel="0"></button>
                <button class="carousel-dot" data-sidebar="right" data-panel="1"></button>
            </div>
            <button class="carousel-chevron" data-sidebar="right" data-dir="next"><i data-lucide="chevron-right"></i></button>
        </div>
    </div>
    <div class="panel-viewport">
        <div class="panel-track">
            <div class="panel">
                <div class="panel-title">Appearance</div>
                <div class="panel-content">
                    <div class="appearance-section">
                        <div class="toggle-group">
                            <button class="toggle-btn" data-theme="light">Light</button>
                            <button class="toggle-btn" data-theme="dark">Dark</button>
                        </div>
                        <div class="toggle-group">
                            <button class="toggle-btn" data-carousel="slide">Slide</button>
                            <button class="toggle-btn" data-carousel="fade">Fade</button>
                        </div>
                        <div class="toggle-group">
                            <button class="toggle-btn" data-width="narrow">Narrow</button>
                            <button class="toggle-btn" data-width="normal">Normal</button>
                            <button class="toggle-btn" data-width="wide">Wide</button>
                        </div>
                        <div class="sidebar-width-controls">
                            <div class="sidebar-width-control">
                                <label for="sidebar-width-left-input">Left %</label>
                                <input id="sidebar-width-left-input" type="number" class="sidebar-width-spinner" data-side="left" min="10" max="100" value="15" step="5">
                            </div>
                            <div class="sidebar-width-control">
                                <label for="sidebar-width-right-input">Right %</label>
                                <input id="sidebar-width-right-input" type="number" class="sidebar-width-spinner" data-side="right" min="10" max="100" value="15" step="5">
                            </div>
                        </div>
                        <div class="scheme-list">{$schemes}</div>
                    </div>
                </div>
            </div>
            <div class="panel">
                <div class="panel-title">Settings</div>
                <div class="panel-content"><nav>
                    <a href="#" class="theme-toggle"><i id="theme-icon" data-lucide="moon"></i> Toggle theme</a>
                </nav></div>
            </div>
        </div>
    </div>
</aside>
{$main}
<script src="base.js"></script>
</body>
</html>
HTML;
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

        private function chapterLinks(): string {
            return $this->ctx->chapters
                |> (fn($c) => array_map(fn($ch) => sprintf(
                    '<a href="%s-%s/"><i data-lucide="file-code"></i> %s %s</a>',
                    $ch[0], $ch[1], $ch[0], $ch[1]
                ), $c))
                |> (fn($a) => implode('', $a));
        }

        private function schemeButtons(): string {
            return $this->ctx->colors
                |> (fn($c) => array_map(fn($p) => sprintf(
                    '<button class="scheme-item" data-scheme="%s"><span class="scheme-dot" style="background:%s"></span><span class="scheme-name">%s</span></button>',
                    $p[2], $p[0], $p[1]
                ), $c))
                |> (fn($a) => implode('', $a));
        }

        private function main(): string {
            $list = $this->chapterList();
            return <<<HTML
<main>
    <div class="card">
        <h2>Chapters</h2>
        <p>A progressive PHP 8.5 micro-framework tutorial in 9 chapters</p>
        $list
    </div>
</main>
HTML;
        }
    }
}

namespace {
    echo (new \SPE\Router\Theme(new \SPE\Router\Ctx))->render();
}
