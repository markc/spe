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
                '<a href="?t=%s"%s>%s</a>',
                $p[1],
                $t === $p[1] ? ' class="active"' : '',
                $p[0],
            ), $n))
            |> (static fn($a) => implode('', $a));
        return "<div class=\"dropdown\"><span class=\"dropdown-toggle\">🎨 Themes</span><div class=\"dropdown-menu\">$links</div></div>";
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
            return "<a href=\"?o=Auth&m=profile\">👤 $name$role</a> <a href=\"?o=Auth&m=logout\">Logout</a>";
        }
        return '<a href="?o=Auth&m=login">🔒 Login</a>';
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
            <script>(function(){const t=localStorage.getItem("base-theme");document.documentElement.className=t||(matchMedia("(prefers-color-scheme:dark)").matches?"dark":"light")})();</script>
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
