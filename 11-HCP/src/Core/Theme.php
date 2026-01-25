<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Core;

use SPE\App\Util;

final class Theme
{
    public function __construct(private Ctx $ctx, private array $out) {}

    public function render(): string
    {
        return $this->html($this->topnav() . $this->sidebar('left') . $this->sidebar('right') . "<main id=\"main\">{$this->out['main']}</main>");
    }

    private function navLinks(): string
    {
        $path = '/' . trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        return implode('', array_map(fn($p) => sprintf(
            '<a href="%s" hx-get="%s" hx-target="#main" hx-push-url="true"%s data-icon="%s"><i data-lucide="%s"></i> %s</a>',
            $p[2], $p[2], $this->isActive($p[2], $path) ? ' class="active"' : '', $p[0], $p[0], $p[1]
        ), $this->ctx->nav));
    }

    private function isActive(string $href, string $path): bool
    {
        if (str_starts_with($href, '/')) {
            return $href === $path || ($href === '/' && $path === '/');
        }
        if (str_starts_with($href, '?o=')) {
            return str_starts_with($this->ctx->in['o'], substr($href, 3));
        }
        return false;
    }

    private function colorLinks(): string
    {
        return implode('', array_map(fn($p) => sprintf(
            '<a href="#" data-scheme="%s" data-icon="%s"><i data-lucide="%s"></i> %s</a>', $p[2], $p[0], $p[0], $p[1]
        ), $this->ctx->colors));
    }

    private function authNav(): string
    {
        if (Util::is_usr()) {
            $usr = $_SESSION['usr'];
            $name = htmlspecialchars($usr['fname'] ?: $usr['login']);
            $role = Util::is_adm() ? ' <small>(admin)</small>' : '';
            return "<div class=\"sidebar-divider\"></div><a href=\"?o=Auth&m=profile\" hx-get=\"?o=Auth&m=profile\" hx-target=\"#main\" hx-push-url=\"true\" data-icon=\"user\"><i data-lucide=\"user\"></i> {$name}{$role}</a><a href=\"?o=Auth&m=changepw\" hx-get=\"?o=Auth&m=changepw\" hx-target=\"#main\" hx-push-url=\"true\" data-icon=\"key\"><i data-lucide=\"key\"></i> Password</a><a href=\"?o=Auth&m=logout\" data-icon=\"log-out\"><i data-lucide=\"log-out\"></i> Logout</a>";
        }
        return "<div class=\"sidebar-divider\"></div><a href=\"?o=Auth&m=login\" hx-get=\"?o=Auth&m=login\" hx-target=\"#main\" hx-push-url=\"true\" data-icon=\"lock\"><i data-lucide=\"lock\"></i> Login</a>";
    }

    private function topnav(): string
    {
        return <<<HTML
<nav class="topnav">
    <button class="menu-toggle" data-sidebar="left"><i data-lucide="menu"></i></button>
    <h1><a class="brand" href="../"><span>{$this->out['page']}</span></a></h1>
    <button class="menu-toggle" data-sidebar="right"><i data-lucide="menu"></i></button>
</nav>
HTML;
    }

    private function sidebar(string $side): string
    {
        [$nav, $title, $icon] = $side === 'left'
            ? [$this->navLinks() . $this->authNav(), 'Navigation', 'compass']
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
        $log = Util::log();
        if (!$log) return '';
        $html = '';
        foreach ($log as $type => $msg) {
            $msg = htmlspecialchars($msg);
            $html .= "<script>showToast('{$msg}', '{$type}');</script>";
        }
        return $html;
    }

    private function html(string $body): string
    {
        $flash = $this->flash();
        $css = $this->out['css'] ?? '';
        $js = $this->out['js'] ?? '';
        $end = $this->out['end'] ?? '';
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$this->out['doc']}</title>
    <link rel="stylesheet" href="../base.css">
    <link rel="stylesheet" href="../site.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>(function(){var s=JSON.parse(localStorage.getItem('base-state')||'{}'),t=s.theme,c=s.scheme,h=document.documentElement;h.className='preload '+(t||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light'))+(c&&c!=='default'?' scheme-'+c:'');})()</script>
    <style>
        .htmx-request #main { opacity: 0.5; transition: opacity 200ms; }
        .htmx-request .htmx-indicator { display: inline-block; }
        .htmx-indicator { display: none; }
        #main { transition: opacity 200ms ease-in-out; }
        .htmx-swapping { opacity: 0; }
        .htmx-settling { opacity: 1; }
    </style>
{$css}
</head>
<body hx-boost="true">
{$body}
<div class="overlay"></div>
<script src="../base.js"></script>
<script src="site.js"></script>
<script src="https://unpkg.com/htmx.org@2.0.4"></script>
<script>
document.body.addEventListener('showToast', function(e) {
    if (e.detail && e.detail.message) showToast(e.detail.message, e.detail.type || 'success');
});
document.body.addEventListener('htmx:afterSettle', function(e) {
    document.querySelectorAll('nav a, .sidebar a').forEach(a => {
        a.classList.remove('active');
        if (a.getAttribute('href') === window.location.pathname || a.getAttribute('href') === window.location.search) a.classList.add('active');
    });
    lucide.createIcons();
});
</script>
{$js}
{$flash}
{$end}
</body>
</html>
HTML;
    }
}
