#!/usr/bin/env php
<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Generates one static docs page per section (docs/<dir>/index.html) from
// chapters.json, so the GitHub Pages sidebar links straight to clean URLs
// (/spe/01-Simple/) with no extra README click and no #hash. Re-run after
// adding or renaming a chapter: `composer build-docs`.

$root = dirname(__DIR__);
$manifest = json_decode(file_get_contents("$root/chapters.json"), true, flags: JSON_THROW_ON_ERROR);

$icons = [
    '00-Tutorial' => 'video', '01-Simple' => 'file-code', '02-Styled' => 'palette',
    '03-Plugins' => 'puzzle', '04-Views' => 'layout-template', '05-Autoload' => 'package',
    '06-Session' => 'cookie', '07-PDO' => 'database', '08-Auth' => 'users', '09-Blog' => 'newspaper',
];

// The sidebar, in order: Overview, Conventions, 00-Tutorial, then the chapters.
$nav = [
    ['dir' => '', 'label' => 'SPE Overview', 'icon' => 'home'],
    ['dir' => 'conventions', 'label' => 'Conventions', 'icon' => 'ruler'],
    ['dir' => '00-Tutorial', 'label' => '00 Tutorial', 'icon' => 'video'],
];
foreach ($manifest['chapters'] as $c) {
    $nav[] = ['dir' => $c['dir'], 'label' => "{$c['id']} {$c['name']}", 'icon' => $icons[$c['dir']] ?? 'file-text'];
}

// The pages to generate: dir (relative to docs/), the markdown to render, and the title.
$pages = [
    ['dir' => '', 'md' => 'README.md', 'title' => 'SPE Documentation'],
    ['dir' => 'conventions', 'md' => '../CONVENTIONS.md', 'title' => 'SPE Conventions'],
    ['dir' => '00-Tutorial', 'md' => 'README.md', 'title' => 'SPE Tutorial tooling'],
];
foreach ($manifest['chapters'] as $c) {
    $pages[] = ['dir' => $c['dir'], 'md' => 'README.md', 'title' => "SPE::{$c['id']} {$c['name']}"];
}

$href = static function (string $rootPrefix, string $target): string {
    if ($target === '') {
        return $rootPrefix === '' ? './' : $rootPrefix;
    }
    return "$rootPrefix$target/";
};

$count = 0;
foreach ($pages as $page) {
    $depth = $page['dir'] === '' ? 0 : 1;
    $r = str_repeat('../', $depth);                 // relative path back to docs root

    $links = '';
    foreach ($nav as $item) {
        $active = $item['dir'] === $page['dir'] ? ' class="active"' : '';
        $links .= sprintf(
            "        <a href=\"%s\"%s data-icon=\"%s\"><i data-lucide=\"%s\"></i> %s</a>\n",
            htmlspecialchars($href($r, $item['dir']), ENT_QUOTES),
            $active,
            $item['icon'],
            $item['icon'],
            htmlspecialchars($item['label'], ENT_QUOTES),
        );
    }

    $t = htmlspecialchars($page['title'], ENT_QUOTES);
    $md = htmlspecialchars($page['md'], ENT_QUOTES);
    $home = $r === '' ? './' : $r;
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>$t</title>
    <link rel="icon" href="{$r}favicon.ico">
    <link rel="stylesheet" href="{$r}base.css">
    <link rel="stylesheet" href="{$r}site.css">
    <link rel="stylesheet" href="{$r}spe.css">
    <script src="https://unpkg.com/lucide@1.33.0/dist/umd/lucide.min.js"></script>
    <script>(function(){var s=JSON.parse(localStorage.getItem('base-state')||'{}'),t=s.theme,c=s.scheme,h=document.documentElement;h.className='preload '+(t||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light'))+(c&&c!=='default'?' scheme-'+c:'')+(s.width==='wide'?' wide':(s.width==='narrow'?' narrow':''));})()</script>
</head>
<body>
<button class="menu-toggle" data-sidebar="left"><i data-lucide="menu"></i></button>
<button class="menu-toggle" data-sidebar="right"><i data-lucide="menu"></i></button>
<nav class="topnav">
    <h1><a class="brand" href="$home"><span>📚 SPE Documentation</span></a></h1>
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
                <div class="panel-title">Chapters</div>
                <div class="panel-content"><nav>
$links                </nav></div>
            </div>
            <div class="panel">
                <div class="panel-title">About</div>
                <div class="panel-content"><nav>
                    <a href="{$r}../"><i data-lucide="layout-grid"></i> Run the chapters</a>
                    <div class="sidebar-divider"></div>
                    <a href="https://github.com/markc/spe"><i data-lucide="code"></i> SPE on GitHub</a>
                    <a href="https://www.youtube.com/playlist?list=PLM0Did14jsitwKl7RYaVrUWnG1GkRBO4B"><i data-lucide="video"></i> Video tutorials</a>
                    <a href="https://dcs.spa"><i data-lucide="panels-left-right"></i> DCS — this interface</a>
                </nav></div>
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
                        <div class="scheme-list">
                            <button class="scheme-item" data-scheme="default"><span class="scheme-dot" style="background:oklch(50% 0.12 220)"></span><span class="scheme-name">Ocean</span></button>
                            <button class="scheme-item" data-scheme="crimson"><span class="scheme-dot" style="background:oklch(47% 0.2 25)"></span><span class="scheme-name">Crimson</span></button>
                            <button class="scheme-item" data-scheme="stone"><span class="scheme-dot" style="background:oklch(45% 0.05 60)"></span><span class="scheme-name">Stone</span></button>
                            <button class="scheme-item" data-scheme="forest"><span class="scheme-dot" style="background:oklch(49% 0.12 150)"></span><span class="scheme-name">Forest</span></button>
                            <button class="scheme-item" data-scheme="sunset"><span class="scheme-dot" style="background:oklch(52% 0.16 45)"></span><span class="scheme-name">Sunset</span></button>
                            <button class="scheme-item" data-scheme="mono"><span class="scheme-dot" style="background:oklch(50% 0 0)"></span><span class="scheme-name">Mono</span></button>
                        </div>
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
<main id="content" class="prose" data-md="$md" data-root="$r">Loading…</main>
<script src="{$r}base.js"></script>
<script src="{$r}md.js"></script>
</body>
</html>

HTML;

    $out = $page['dir'] === '' ? "$root/docs/index.html" : "$root/docs/{$page['dir']}/index.html";
    if (!is_dir(dirname($out))) {
        mkdir(dirname($out), 0o755, true);
    }
    file_put_contents($out, $html);
    echo "wrote docs/" . ($page['dir'] === '' ? '' : "{$page['dir']}/") . "index.html\n";
    $count++;
}

echo "$count docs pages generated\n";
