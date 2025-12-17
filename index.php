<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)
// Usage: php -S localhost:8000 index.php

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

// Main index page
echo new class {
    private array $chapters = [
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
    ];

    public function __toString(): string {
        $cards = $this->chapters |> (fn($c) => array_map(fn($ch) => <<<HTML
            <a href="{$ch[0]}-{$ch[1]}/" class="card-link">
                <span class="card-title">{$ch[0]}</span><h3>{$ch[1]}</h3><p>{$ch[2]}</p>
            </a>
            HTML, $c)) |> (fn($a) => implode("\n", $a));

        return <<<HTML
        <!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="color-scheme" content="light dark"><title>SPE - Simple PHP Examples</title><link rel="stylesheet" href="/spe.css"></head>
        <body><div class="container">
        <header class="hero">
            <div class="flex-center"><h1>🐘 Simple PHP Examples</h1><button class="theme-toggle" id="theme-icon">🌙</button></div>
            <p>A progressive PHP 8.5 micro-framework tutorial in 10 chapters</p>
            <div class="flex-center">
                <a href="https://github.com/markc/spe" class="btn btn-php">GitHub</a>
                <a href="https://www.youtube.com/playlist?list=PLM0Did14jsitwKl7RYaVrUWnG1GkRBO4B" class="btn btn-outline">Tutorials</a>
                <a href="docs/#09-Blog/README.md" class="btn btn-outline">Docs</a>
            </div>
        </header>
        <div class="grid grid-auto mt-2">$cards</div>
        <section><h2>PHP Features</h2>
        <div class="feature-grid">
            <div class="feature"><div class="feature-icon">|></div><h4>Pipe Operator</h4><p>PHP 8.5 data transformation chains</p></div>
            <div class="feature"><div class="feature-icon">🔌</div><h4>Plugin System</h4><p>Extensible architecture with CRUDL</p></div>
            <div class="feature"><div class="feature-icon">🎨</div><h4>Custom CSS</h4><p>270 lines, dark mode, responsive</p></div>
            <div class="feature"><div class="feature-icon">🗄️</div><h4>SQLite + PDO</h4><p>Type-safe queries with enums</p></div>
        </div></section>
        <footer class="text-center mt-3"><small>© 2015-2025 Mark Constable (MIT License)</small></footer>
        </div><script src="/spe.js"></script></body></html>
        HTML;
    }
};
