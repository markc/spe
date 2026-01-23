<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Htmx\Themes;

use SPE\App\Util;
use SPE\Htmx\Core\Theme;

final class SideBar extends Theme
{
    #[\Override]
    public function render(): string
    {
        $path = '/' . trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        // Navigation links with htmx (supports both clean URLs and query strings)
        $nav = $this->ctx->nav
            |> (fn($n) => array_map(function($p) use ($path) {
                // Extract emoji (grapheme cluster) from start of title for data-icon attribute
                preg_match('/^(\X)\s*/u', $p[0], $m);
                $icon = (isset($m[1]) && preg_match('/^\p{So}|\p{S}/u', $m[1])) ? $m[1] : '📄';
                return sprintf(
                    '<a href="%s" hx-get="%s" hx-target="#main" hx-push-url="true"%s title="%s" data-icon="%s">%s</a>',
                    $p[1],
                    $p[1],
                    $this->isActiveNav($p[1], $path) ? ' class="active"' : '',
                    $p[0],
                    $icon,
                    $p[0],
                );
            }, $n))
            |> (static fn($a) => implode('', $a));

        $userMenu = $this->userDropdown();
        $mobileMenu = $this->mobileMenuItems();
        $body = <<<HTML
        <nav class="topnav">
            <button class="menu-toggle"><i data-lucide="menu"></i></button>
            <h1><a class="brand" href="/" hx-get="/" hx-target="#main" hx-push-url="true"><i data-lucide="chevron-left"></i> <span>htmx Blog</span></a></h1>
            <div class="topnav-user desktop-only">$userMenu</div>
            <span class="htmx-indicator"><i data-lucide="loader-2" class="spin"></i></span>
            <button class="theme-toggle desktop-only" id="theme-icon"><i data-lucide="moon"></i></button>
        </nav>
        <div class="sidebar-layout">
            <aside class="sidebar">
                <div class="sidebar-content">
                    <nav>$nav</nav>
                    <div class="mobile-only sidebar-mobile-menu">
                        $mobileMenu
                    </div>
                </div>
                <button class="sidebar-toggle" aria-label="Toggle sidebar"></button>
            </aside>
            <div class="sidebar-main">
                <main id="main" class="mt-4 mb-4">{$this->out['main']}</main>
                <footer class="text-center"><small>© 2015-2026 Mark Constable (MIT License)</small></footer>
            </div>
        </div>
        HTML;
        return $this->html('SideBar', $body);
    }

    private function mobileMenuItems(): string
    {
        $items = '<div class="sidebar-divider"></div>';

        // Theme toggle
        $items .= '<a href="#" onclick="Base.toggleTheme(); return false;" data-icon="🌙"><i data-lucide="moon"></i> Toggle Theme</a>';

        if (!Util::is_usr()) {
            $items .= '<a href="?o=Auth&m=login" data-icon="🔒"><i data-lucide="lock"></i> Login</a>';
            return $items;
        }

        // User menu items
        $items .= '<a href="?o=Auth&m=profile" data-icon="👤"><i data-lucide="user"></i> Profile</a>';
        $items .= '<a href="?o=Auth&m=changepw" data-icon="🔑"><i data-lucide="key"></i> Password</a>';

        // Admin links
        if (Util::is_adm()) {
            $items .= '<div class="sidebar-divider"></div>';
            $items .= '<a href="?o=Users" data-icon="👥"><i data-lucide="users"></i> Users</a>';
            $items .= '<a href="?o=Posts" data-icon="📝"><i data-lucide="file-text"></i> Posts</a>';
            $items .= '<a href="?o=Categories" data-icon="🏷️"><i data-lucide="tags"></i> Categories</a>';
        }

        // Logout
        $items .= '<div class="sidebar-divider"></div>';
        $items .= '<a href="?o=Auth&m=logout" data-icon="🚪"><i data-lucide="log-out"></i> Logout</a>';

        return $items;
    }

    private function isActiveNav(string $href, string $path): bool
    {
        // Clean URL match
        if (str_starts_with($href, '/')) {
            return $href === $path || $href === '/' && $path === '/';
        }
        // Query string match
        if (str_starts_with($href, '?o=')) {
            $o = substr($href, 3);
            $pos = strpos($o, '&');
            if ($pos !== false)
                $o = substr($o, 0, $pos);
            return str_starts_with($this->ctx->in['o'], $o);
        }
        return false;
    }
}
