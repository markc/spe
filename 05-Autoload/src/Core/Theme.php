<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Core;

abstract class Theme {
    public function __construct(protected Ctx $ctx, protected array $out) {}

    abstract public function render(): string;

    protected function nav(): string {
        ['o' => $o, 't' => $t] = $this->ctx->in;
        return $this->ctx->nav
            |> (static fn($n) => array_map(static fn($p) => sprintf(
                '<a href="?o=%s&t=%s"%s>%s</a>',
                $p[1], $t, $o === $p[1] ? ' class="active"' : '', $p[0]
            ), $n))
            |> (static fn($a) => implode(' ', $a));
    }

    protected function dropdown(): string {
        ['o' => $o, 't' => $t] = $this->ctx->in;
        $links = $this->ctx->themes
            |> (static fn($n) => array_map(static fn($p) => sprintf(
                '<a href="?o=%s&t=%s"%s>%s</a>',
                $o, $p[1], $t === $p[1] ? ' class="active"' : '', $p[0]
            ), $n))
            |> (static fn($a) => implode('', $a));
        return "<div class=\"dropdown\"><span class=\"dropdown-toggle\">🎨 Themes</span><div class=\"dropdown-menu\">$links</div></div>";
    }

    protected function html(string $theme, string $body): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$this->out['doc']} [$theme]</title>
    <link rel="stylesheet" href="/spe.css">
</head>
<body>
$body
<script src="/spe.js"></script>
</body>
</html>
HTML;
    }
}
