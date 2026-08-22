<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Core;

final class Theme
{
    public function __construct(private readonly Ctx $ctx, private readonly array $out) {}

    public function render(): string
    {
        $nav = $this->ctx->nav
            |> (fn(array $items) => array_map(fn(array $n) => sprintf(
                '<a href="?o=%s"%s><i data-lucide="%s"></i> %s</a>', $n[2], $n[2] === $this->ctx->in['o'] ? ' class="active"' : '', $n[0], $n[1]
            ), $items))
            |> (static fn(array $links) => implode('', $links));

        $schemes = $this->ctx->schemes
            |> (static fn(array $items) => array_map(static fn(array $s) => sprintf(
                '<a href="#" data-scheme="%s"><i data-lucide="%s"></i> %s</a>', $s[2], $s[0], $s[1]
            ), $items))
            |> (static fn(array $links) => implode('', $links));

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$this->out['doc']} {$this->ctx->in['o']}</title>
    <link rel="stylesheet" href="../base.css">
    <link rel="stylesheet" href="../site.css">
    <script src="https://unpkg.com/lucide@1.33.0/dist/umd/lucide.min.js"></script>
    <script>(function(){var s=JSON.parse(localStorage.getItem('base-state')||'{}'),t=s.theme,c=s.scheme,h=document.documentElement;h.className='preload '+(t||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light'))+(c&&c!=='default'?' scheme-'+c:'');})()</script>
</head>
<body>
<nav class="topnav">
    <button class="menu-toggle" data-sidebar="left"><i data-lucide="menu"></i></button>
    <h1><a class="brand" href="../"><span>{$this->out['page']}</span></a></h1>
    <button class="menu-toggle" data-sidebar="right"><i data-lucide="menu"></i></button>
</nav>
<aside class="sidebar sidebar-left">
    <div class="sidebar-header"><span><i data-lucide="compass"></i> Navigation</span><button class="pin-toggle" data-sidebar="left" title="Pin sidebar"><i data-lucide="pin"></i></button></div>
    <nav>{$nav}</nav>
</aside>
<aside class="sidebar sidebar-right">
    <div class="sidebar-header"><span><i data-lucide="sliders-horizontal"></i> Settings</span><button class="pin-toggle" data-sidebar="right" title="Pin sidebar"><i data-lucide="pin"></i></button></div>
    <nav>{$schemes}<div class="sidebar-divider"></div><a href="#" class="theme-toggle"><i data-lucide="moon"></i> Toggle theme</a></nav>
</aside>
<main>{$this->out['main']}</main>
<div class="overlay"></div>
<script src="../base.js"></script>
{$this->flashScript()}
</body>
</html>
HTML;
    }

    private function flashScript(): string
    {
        $flash = $this->ctx->takeFlash();
        if (!$flash) {
            return '';
        }
        $calls = array_map(
            static fn(array $f) => sprintf('Base.toast(%s, %s);', json_encode($f[1], JSON_THROW_ON_ERROR), json_encode($f[0], JSON_THROW_ON_ERROR)),
            $flash,
        );
        $body = implode('', $calls);
        return "<script>addEventListener('DOMContentLoaded',()=>{{$body}})</script>";
    }
}
