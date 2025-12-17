<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Core;

class Theme {
    public function __construct(protected Ctx $ctx) {}

    // Default CRUDL methods for views to override
    public function create(): string { return ''; }
    public function read(): string { return ''; }
    public function update(): string { return ''; }
    public function delete(): string { return ''; }
    public function list(): string { return ''; }
    public function html(): string { return ''; }

    /** Build flat nav links from items array */
    protected function nav(array $items, string $param = 'o'): string {
        return $items |> (fn($a) => array_map(fn($n) => sprintf(
            '<a href="%s"%s>%s</a>',
            str_starts_with($n[1], '?') ? $n[1] : "?{$param}={$n[1]}",
            $this->isActive($n[1], $param) ? ' class="active"' : '',
            $n[0]
        ), $a)) |> (fn($l) => implode(' ', $l));
    }

    /** Build dropdown menu from items array */
    protected function dropdown(string $label, array $items, string $param = 'o'): string {
        if (empty($items)) return '';
        $links = $items |> (fn($a) => array_map(fn($n) => sprintf(
            '<a href="%s">%s</a>',
            str_starts_with($n[1], '?') ? $n[1] : "?{$param}={$n[1]}",
            $n[0]
        ), $a)) |> (fn($l) => implode('', $l));
        return <<<HTML
        <div class="dropdown">
            <a class="dropdown-toggle">$label</a>
            <div class="dropdown-menu">$links</div>
        </div>
        HTML;
    }

    private function isActive(string $href, string $param): bool {
        if (str_starts_with($href, '?p=')) {
            $slug = substr($href, 3);
            return ($_GET['p'] ?? '') === $slug;
        }
        return $this->ctx->in[$param] === $href;
    }

    /** Build pages nav (flat links for core pages) */
    protected function pagesNav(): string {
        return $this->nav($this->ctx->navPages);
    }

    /** YouTube doesn't have admin dropdown - return empty */
    protected function adminDropdown(): string {
        return '';
    }

    /** Build themes dropdown - preserves current URL params */
    protected function themesDropdown(): string {
        $items = array_map(fn($n) => [$n[0], $this->themeLink($n[1])], $this->ctx->nav2);
        return $this->dropdown('🎨 Theme', $items);
    }

    /** Build theme link preserving current URL params */
    private function themeLink(string $theme): string {
        return $_GET
            |> (fn($p) => [...$p, 't' => $theme])
            |> http_build_query(...)
            |> (fn($q) => "?$q");
    }

    /** YouTube OAuth auth nav */
    protected function authNav(): string {
        if (!empty($_SESSION['authenticated'])) {
            $channel = $_SESSION['channel'] ?? null;
            $name = htmlspecialchars($channel['title'] ?? 'User');
            $thumb = htmlspecialchars($channel['thumbnail'] ?? '');
            $img = $thumb ? "<img src=\"$thumb\" alt=\"$name\" style=\"width:28px;height:28px;border-radius:50%;vertical-align:middle;margin-right:0.5rem\">" : '';
            return <<<HTML
            <span title="$name">$img$name</span>
            <a href="?o=Auth&m=delete" title="Logout">🚪</a>
            HTML;
        }
        return '<a href="?o=Auth">🔒 Login</a>';
    }

    protected function toast(): string {
        $log = Util::get_log();
        if (empty($log)) return '';
        $type = $log['type'] === 'success' ? 'toast-success' : 'toast-danger';
        $msg = htmlspecialchars($log['msg']);
        return <<<HTML
        <div class="toast $type" onclick="this.remove()">$msg</div>
        <script>setTimeout(() => document.querySelector('.toast')?.remove(), 4000)</script>
        HTML;
    }
}
