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
    <script src="https://unpkg.com/lucide@1.33.0/dist/umd/lucide.min.js"></script>
    <script>(function(){var s=JSON.parse(localStorage.getItem('base-state')||'{}'),t=s.theme,c=s.scheme,h=document.documentElement;h.className='preload '+(t||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light'))+(c&&c!=='default'?' scheme-'+c:'');})()</script>
</head>
<body>
<nav class="topnav">
    <button class="menu-toggle" data-sidebar="left"><i data-lucide="menu"></i></button>
    <h1><a class="brand" href="$home"><span>📚 SPE Documentation</span></a></h1>
    <button class="menu-toggle" data-sidebar="right"><i data-lucide="menu"></i></button>
</nav>
<aside class="sidebar sidebar-left">
    <div class="sidebar-header"><span><i data-lucide="book-open"></i> Chapters</span><button class="pin-toggle" data-sidebar="left" title="Pin sidebar"><i data-lucide="pin"></i></button></div>
    <nav>
$links    </nav>
</aside>
<aside class="sidebar sidebar-right">
    <div class="sidebar-header"><span><i data-lucide="sliders-horizontal"></i> Settings</span><button class="pin-toggle" data-sidebar="right" title="Pin sidebar"><i data-lucide="pin"></i></button></div>
    <nav>
        <a href="#" data-scheme="default" data-icon="circle"><i data-lucide="circle"></i> Stone</a>
        <a href="#" data-scheme="ocean" data-icon="waves"><i data-lucide="waves"></i> Ocean</a>
        <a href="#" data-scheme="forest" data-icon="trees"><i data-lucide="trees"></i> Forest</a>
        <a href="#" data-scheme="sunset" data-icon="sunset"><i data-lucide="sunset"></i> Sunset</a>
        <div class="sidebar-divider"></div>
        <a href="#" onclick="Base.toggleTheme();return false" data-icon="moon"><i data-lucide="moon"></i> Toggle Theme</a>
    </nav>
</aside>
<main id="content" class="prose" data-md="$md" data-root="$r">Loading…</main>
<div class="overlay"></div>
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
