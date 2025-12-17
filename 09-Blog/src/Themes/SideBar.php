<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Themes;

use SPE\App\Util;
use SPE\Blog\Core\{Ctx, Theme};

final class SideBar extends Theme {

    public function html(): string {
        extract($this->ctx->out);
        $sidebarNav = $this->buildSidebarNav();
        $auth = $this->authNav();
        $toast = $this->toast();
        return <<<HTML
        <!DOCTYPE html><html lang="en"><head>
            <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
            <title>$doc [SideBar]</title><link rel="stylesheet" href="/spe.css">
        </head><body>
            $toast
            <nav class="topnav"><button class="menu-toggle">☰</button><a class="brand" href="../">« $head</a>
                <span style="margin-left:auto">$auth</span>
                <button class="theme-toggle" id="theme-icon">🌙</button></nav>
            <div class="sidebar-layout">
                <aside class="sidebar">
                    $sidebarNav
                </aside>
                <div class="sidebar-main"><main>$main</main>
                    <footer class="text-center mt-3"><small>$foot</small></footer>
                </div>
            </div>
        <script src="/spe.js"></script></body></html>
        HTML;
    }

    private function buildSidebarNav(): string {
        $html = '';
        ['o' => $o, 't' => $t] = $this->ctx->in;

        // Pages section (from database)
        $pages = $this->ctx->navPages;
        if (!empty($pages)) {
            $links = array_map(fn($n) => sprintf(
                '<a href="%s"%s>%s</a>',
                str_starts_with($n[1], '?') ? $n[1] : "?o={$n[1]}",
                $n[1] === $o ? ' class="active"' : '',
                $n[0]
            ), $pages);
            $html .= '<div class="sidebar-group"><div class="sidebar-group-title">Pages</div><nav>' . implode('', $links) . '</nav></div>';
        }

        // Admin section (for logged-in users)
        if (Util::is_usr()) {
            $adminItems = [
                ['📝 Posts', 'Posts'],
                ['📄 Pages', 'Pages'],
                ['🏷️ Categories', 'Categories'],
            ];
            if (Util::is_adm()) {
                $adminItems[] = ['👥 Users', 'Users'];
            }
            $adminItems[] = ['📚 Docs', 'Docs'];
            $links = array_map(fn($n) => sprintf('<a href="?o=%s"%s>%s</a>', $n[1], $n[1] === $o ? ' class="active"' : '', $n[0]), $adminItems);
            $html .= '<div class="sidebar-group"><div class="sidebar-group-title">Admin</div><nav>' . implode('', $links) . '</nav></div>';
        }

        // Grouped pages by category (from database)
        $groups = $this->ctx->navGroups;
        foreach ($groups as $slug => $group) {
            if ($slug === 'main' || !is_array($group) || empty($group['items'])) continue;

            $groupName = htmlspecialchars($group['name']);
            $links = array_map(fn($n) => sprintf(
                '<a href="%s"%s>%s</a>',
                str_starts_with($n[1], '?') ? $n[1] : "?o={$n[1]}",
                $n[1] === $o ? ' class="active"' : '',
                $n[0]
            ), $group['items']);
            $html .= "<div class=\"sidebar-group\"><div class=\"sidebar-group-title\">$groupName</div><nav>" . implode('', $links) . '</nav></div>';
        }

        // Themes section (always visible) - preserves current URL params
        $themes = $this->ctx->nav2;
        if (!empty($themes)) {
            $links = $themes
                |> (fn($items) => array_map(fn($n) => sprintf(
                    '<a href="%s"%s>%s</a>',
                    $_GET |> (fn($p) => [...$p, 't' => $n[1]]) |> http_build_query(...) |> (fn($q) => "?$q"),
                    $n[1] === $t ? ' class="active"' : '',
                    $n[0]
                ), $items))
                |> (fn($l) => implode('', $l));
            $html .= '<div class="sidebar-group"><div class="sidebar-group-title">Themes</div><nav>' . $links . '</nav></div>';
        }

        return $html;
    }
}
