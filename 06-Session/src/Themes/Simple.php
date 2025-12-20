<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Themes;

use SPE\Session\Core\Theme;

final class Simple extends Theme {
    #[\Override] public function render(): string {
        $nav = $this->nav();
        $dd = $this->dropdown();
        $body = <<<HTML
<div class="container">
    <header><h1><a class="brand" href="/">🐘 Session PHP Example</a></h1></header>
    <nav class="card flex">
        $nav $dd
        <span class="ml-auto"><button class="theme-toggle" id="theme-icon">🌙</button></span>
    </nav>
    <main>{$this->out['main']}</main>
    <footer class="text-center mt-3"><small>© 2015-2025 Mark Constable (MIT License)</small></footer>
</div>
HTML;
        return $this->html('Simple', $body);
    }
}
