<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Core;

abstract class Theme {
    public function __construct(protected Ctx $ctx, protected array $out) {}

    abstract public function render(): string;

    protected function nav(): string {
        $o = $this->ctx->in['o'];
        return $this->ctx->nav
            |> (fn($n) => array_map(fn($p) => sprintf(
                '<a href="?o=%s"%s>%s</a>',
                $p[1], $o === $p[1] ? ' class="active"' : '', $p[0]
            ), $n))
            |> (fn($a) => implode(' ', $a));
    }

    protected function dropdown(): string {
        $t = $this->ctx->in['t'];
        $links = $this->ctx->themes
            |> (fn($n) => array_map(fn($p) => sprintf(
                '<a href="?t=%s"%s>%s</a>',
                $p[1], $t === $p[1] ? ' class="active"' : '', $p[0]
            ), $n))
            |> (fn($a) => implode('', $a));
        return "<div class=\"dropdown\"><span class=\"dropdown-toggle\">🎨 Themes</span><div class=\"dropdown-menu\">$links</div></div>";
    }

    protected function flash(): string {
        $msg = $this->ctx->flash('msg');
        $type = $this->ctx->flash('type') ?? 'success';
        return $msg ? "<script>showToast('$msg', '$type');</script>" : '';
    }

    protected function html(string $theme, string $body): string {
        $flash = $this->flash();
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
$flash
</body>
</html>
HTML;
    }
}
