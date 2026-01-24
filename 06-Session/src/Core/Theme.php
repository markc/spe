<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Core;

final class Theme
{
    public function __construct(private Ctx $ctx, private array $out) {}

    public function render(): string
    {
        return $this->html($this->topnav() . $this->sidebar('left') . $this->sidebar('right') . "<main>{$this->out['main']}</main>");
    }

    private function navLinks(): string
    {
        return implode('', array_map(fn($p) => sprintf(
            '<a href="?o=%s"%s data-icon="%s"><i data-lucide="%s"></i> %s</a>',
            $p[2], $this->ctx->in['o'] === $p[2] ? ' class="active"' : '', $p[0], $p[0], $p[1]
        ), $this->ctx->nav));
    }

    private function colorLinks(): string
    {
        return implode('', array_map(fn($p) => sprintf(
            '<a href="#" data-scheme="%s" data-icon="%s"><i data-lucide="%s"></i> %s</a>', $p[2], $p[0], $p[0], $p[1]
        ), $this->ctx->colors));
    }

    private function topnav(): string
    {
        return <<<HTML
<nav class="topnav">
    <button class="menu-toggle" data-sidebar="left"><i data-lucide="menu"></i></button>
    <h1><a class="brand" href="/"><span>{$this->out['page']}</span></a></h1>
    <button class="menu-toggle" data-sidebar="right"><i data-lucide="menu"></i></button>
</nav>
HTML;
    }

    private function sidebar(string $side): string
    {
        [$nav, $title, $icon] = $side === 'left'
            ? [$this->navLinks(), 'Navigation', 'compass']
            : [$this->colorLinks() . '<div class="sidebar-divider"></div><a href="#" onclick="Base.toggleTheme();return false" data-icon="moon"><i data-lucide="moon"></i> Toggle Theme</a>', 'Settings', 'sliders-horizontal'];
        return <<<HTML
<aside class="sidebar sidebar-{$side}">
    <div class="sidebar-header"><span><i data-lucide="{$icon}"></i> {$title}</span><button class="pin-toggle" data-sidebar="{$side}" title="Pin sidebar"><i data-lucide="pin"></i></button></div>
    <nav>{$nav}</nav>
</aside>
HTML;
    }

    private function flash(): string
    {
        $msg = $this->ctx->flash('msg');
        $type = $this->ctx->flash('type') ?? 'success';
        return $msg ? "<script>showToast('{$msg}', '{$type}');</script>" : '';
    }

    private function html(string $body): string
    {
        $flash = $this->flash();
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$this->out['doc']}</title>
    <link rel="stylesheet" href="/base.css">
    <link rel="stylesheet" href="/site.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>(function(){var s=JSON.parse(localStorage.getItem('base-state')||'{}'),t=s.theme,c=s.scheme,h=document.documentElement;h.className='preload '+(t||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light'))+(c&&c!=='default'?' scheme-'+c:'');})()</script>
</head>
<body>
{$body}
<div class="overlay"></div>
<script src="/base.js"></script>
<script>if(location.search)history.replaceState(null,'',location.pathname);</script>
{$flash}
</body>
</html>
HTML;
    }
}
