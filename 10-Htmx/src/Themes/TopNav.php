<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Htmx\Themes;

use SPE\Htmx\Core\Theme;

final class TopNav extends Theme
{
    #[\Override]
    public function render(): string
    {
        $nav = $this->nav();
        $userMenu = $this->userDropdown();
        $body = <<<HTML
        <nav class="topnav">
            <h1><a class="brand" href="/" hx-get="/" hx-target="#main" hx-push-url="true"><i data-lucide="chevron-left"></i> <span>htmx Blog</span></a></h1>
            <div class="topnav-links">$nav</div>
            <span class="htmx-indicator"><i data-lucide="loader-2" class="spin"></i></span>
            <div class="topnav-user">$userMenu</div>
            <button class="theme-toggle" id="theme-icon"><i data-lucide="moon"></i></button>
            <button class="menu-toggle"><i data-lucide="menu"></i></button>
        </nav>
        <div class="container">
            <main id="main" class="mt-4 mb-4">{$this->out['main']}</main>
            <footer class="text-center"><small>© 2015-2026 Mark Constable (MIT License)</small></footer>
        </div>
        HTML;
        return $this->html('TopNav', $body);
    }
}
