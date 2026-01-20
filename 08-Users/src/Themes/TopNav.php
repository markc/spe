<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Users\Themes;

use SPE\Users\Core\Theme;

final class TopNav extends Theme
{
    #[\Override]
    public function render(): string
    {
        $nav = $this->nav();
        $dd = $this->dropdown();
        $colors = $this->colors();
        $auth = $this->authNav();
        $body = <<<HTML
        <nav class="topnav">
            <h1><a class="brand" href="/"><i data-lucide="chevron-left"></i> <span>Users PHP Example</span></a></h1>
            <div class="topnav-links">$nav $dd $colors | $auth</div>
            <button class="theme-toggle" id="theme-icon"><i data-lucide="moon"></i></button>
            <button class="menu-toggle"><i data-lucide="menu"></i></button>
        </nav>
        <div class="container">
            <main class="mt-4 mb-4">{$this->out['main']}</main>
            <footer class="text-center"><small>© 2015-2026 Mark Constable (MIT License)</small></footer>
        </div>
        HTML;
        return $this->html('TopNav', $body);
    }
}
