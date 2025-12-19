<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Themes;

use SPE\Session\Core\Theme;

final class TopNav extends Theme {
    #[\Override] public function render(): string {
        $nav = $this->nav();
        $dd = $this->dropdown();
        $body = <<<HTML
<nav class="topnav">
    <a class="brand" href="/">« Session PHP Example</a>
    <div class="topnav-links">$nav $dd</div>
    <button class="theme-toggle" id="theme-icon">🌙</button>
    <button class="menu-toggle">☰</button>
</nav>
<main class="container mt-4">{$this->out['main']}</main>
<footer class="container text-center mt-3"><small>© 2015-2025 Mark Constable (MIT License)</small></footer>
HTML;
        return $this->html('TopNav', $body);
    }
}
