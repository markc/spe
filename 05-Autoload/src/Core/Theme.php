<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Core;

abstract class Theme
{
    public function __construct(
        protected Ctx $ctx,
        protected array $out,
    ) {}

    abstract public function render(): string;

    protected function nav(): string
    {
        ['o' => $o, 't' => $t] = $this->ctx->in;
        return $this->ctx->nav
            |> (static fn($n) => array_map(static fn($p) => sprintf(
                '<a href="?o=%s&t=%s"%s><i data-lucide="%s"></i> %s</a>',
                $p[2],
                $t,
                $o === $p[2] ? ' class="active"' : '',
                $p[0],
                $p[1],
            ), $n))
            |> (static fn($a) => implode(' ', $a));
    }

    protected function dropdown(): string
    {
        ['o' => $o, 't' => $t] = $this->ctx->in;
        $links = $this->ctx->themes
            |> (static fn($n) => array_map(static fn($p) => sprintf(
                '<a href="?o=%s&t=%s"%s><i data-lucide="%s"></i> %s</a>',
                $o,
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

    protected function html(string $theme, string $body): string
    {
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
        </head>
        <body>
        $body
        <script src="/base.js"></script>
        </body>
        </html>
        HTML;
    }
}
