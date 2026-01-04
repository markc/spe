<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Core;

use SPE\App\Util;

abstract class Theme
{
    public function __construct(protected Ctx $ctx, protected array $out) {}

    abstract public function render(): string;

    protected function nav(): string
    {
        $current = $this->ctx->in['o'];
        return $this->ctx->nav
            |> (fn($n) => array_map(fn($p) => sprintf(
                '<a href="%s"%s>%s</a>',
                $p[1],
                $this->isActive($p[1], $current) ? ' class="active"' : '',
                $p[0]
            ), $n))
            |> (static fn($a) => implode(' ', $a));
    }

    private function isActive(string $href, string $current): bool
    {
        if (preg_match('/\?o=(\w+)/', $href, $m)) {
            return $m[1] === $current;
        }
        return false;
    }

    protected function flash(): string
    {
        $log = Util::log();
        if (!$log) return '';

        $html = '';
        foreach ($log as $type => $msg) {
            $msg = htmlspecialchars($msg);
            $html .= "<div class=\"alert alert-{$type}\">{$msg}</div>";
        }
        return $html;
    }

    protected function authNav(): string
    {
        if (Util::is_usr()) {
            $usr = $_SESSION['usr'];
            $name = htmlspecialchars($usr['fname'] ?: $usr['login']);
            $role = Util::is_adm() ? ' (admin)' : '';
            return "<span class=\"user-info\">👤 {$name}{$role}</span> <a href=\"?o=Auth&m=logout\">Logout</a>";
        }
        return '<a href="?o=Auth&m=login">🔒 Login</a>';
    }

    protected function html(string $body): string
    {
        $flash = $this->flash();
        $css = $this->out['css'] ?? '';
        $js = $this->out['js'] ?? '';
        $end = $this->out['end'] ?? '';
        $hostname = gethostname() ?: 'HCP';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$this->out['doc']} - {$hostname}</title>
    <link rel="stylesheet" href="/spe.css">
    <link rel="stylesheet" href="/hcp.css">
{$css}
</head>
<body>
{$flash}
{$body}
<script src="/spe.js"></script>
<script src="/tables.js"></script>
{$js}
{$end}
</body>
</html>
HTML;
    }
}
