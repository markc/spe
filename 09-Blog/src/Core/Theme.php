<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

use SPE\App\Util;

abstract class Theme
{
    public function __construct(
        protected Ctx $ctx,
        protected array $out,
    ) {}

    abstract public function render(): string;

    protected function nav(): string
    {
        $path = '/' . trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        return $this->ctx->nav
            |> (fn($n) => array_map(fn($p) => sprintf(
                '<a href="%s"%s>%s</a>',
                $p[1],
                $this->isActive($p[1], $path) ? ' class="active"' : '',
                $p[0],
            ), $n))
            |> (static fn($a) => implode(' ', $a));
    }

    private function isActive(string $href, string $path): bool
    {
        // Clean URL match
        if (str_starts_with($href, '/')) {
            return $href === $path || $href === '/' && $path === '/';
        }
        // Query string match
        if (str_starts_with($href, '?o=')) {
            $o = substr($href, 3);
            return str_starts_with($this->ctx->in['o'], $o);
        }
        return false;
    }

    protected function dropdown(): string
    {
        $t = $this->ctx->in['t'];
        $links = $this->ctx->themes
            |> (static fn($n) => array_map(static fn($p) => sprintf(
                '<a href="?t=%s"%s><i data-lucide="%s"></i> %s</a>',
                $p[2],
                $t === $p[2] ? ' class="active"' : '',
                $p[0],
                $p[1],
            ), $n))
            |> (static fn($a) => implode('', $a));
        return "<div class=\"dropdown\"><span class=\"dropdown-toggle\"><i data-lucide=\"layout-grid\"></i> Layout</span><div class=\"dropdown-menu\">$links</div></div>";
    }

    protected function colors(): string
    {
        return <<<HTML
        <div class="dropdown"><span class="dropdown-toggle"><i data-lucide="swatch-book"></i> Colors</span><div class="dropdown-menu">
        <a href="#" data-scheme="default"><i data-lucide="circle"></i> Stone</a>
        <a href="#" data-scheme="ocean"><i data-lucide="waves"></i> Ocean</a>
        <a href="#" data-scheme="forest"><i data-lucide="trees"></i> Forest</a>
        <a href="#" data-scheme="sunset"><i data-lucide="sunset"></i> Sunset</a>
        </div></div>
        HTML;
    }

    protected function flash(): string
    {
        $log = Util::log();
        if (!$log)
            return '';

        $html = '';
        foreach ($log as $type => $msg) {
            $msg = htmlspecialchars($msg);
            $html .= "<script>showToast('$msg', '$type');</script>";
        }
        return $html;
    }

    protected function authNav(): string
    {
        if (Util::is_usr()) {
            $usr = $_SESSION['usr'];
            $name = htmlspecialchars($usr['fname'] ?: $usr['login']);
            $role = Util::is_adm() ? ' (admin)' : '';
            return "<a href=\"?o=Auth&m=profile\"><i data-lucide=\"user\"></i> $name$role</a> <a href=\"?o=Auth&m=logout\">Logout</a>";
        }
        return '<a href="?o=Auth&m=login"><i data-lucide="lock"></i> Login</a>';
    }

    protected function html(string $theme, string $body): string
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
            <title>{$this->out['doc']} [$theme]</title>
            <link rel="stylesheet" href="/base.css">
            <link rel="stylesheet" href="/site.css">
            <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
            <script>(function(){const t=localStorage.getItem("base-theme"),s=localStorage.getItem("base-scheme"),c=t||(matchMedia("(prefers-color-scheme:dark)").matches?"dark":"light");document.documentElement.className=c+(s&&s!=="default"?" scheme-"+s:"")})();</script>
        $css
        </head>
        <body>
        $body
        <script src="/base.js"></script>
        $js
        $flash
        $end
        </body>
        </html>
        HTML;
    }
}
