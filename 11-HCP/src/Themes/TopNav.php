<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Themes;

use SPE\HCP\Core\Theme;

final class TopNav extends Theme
{
    public function render(): string
    {
        $nav = $this->nav();
        $auth = $this->authNav();
        $main = $this->out['main'];
        $hostname = gethostname() ?: 'HCP';

        $body = <<<HTML
<header class="topnav">
    <div class="brand">
        <a href="?o=System">🖥️ {$hostname}</a>
    </div>
    <nav class="nav-links">
        {$nav}
    </nav>
    <div class="nav-auth">
        {$auth}
    </div>
</header>
<main class="container">
    {$main}
</main>
<footer class="footer">
    <p>HCP &copy; 2025 | <a href="https://github.com/markc/spe">SPE Framework</a></p>
</footer>
HTML;

        return $this->html($body);
    }
}
