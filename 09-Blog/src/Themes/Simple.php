<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Themes;

use SPE\Blog\Core\Theme;

final class Simple extends Theme
{
    #[\Override]
    public function render(): string
    {
        $nav = $this->nav();
        $dd = $this->dropdown();
        $auth = $this->authNav();
        $body = <<<HTML
        <div class="container">
            <header><h1><a class="brand" href="/">🐘 Blog PHP Example</a></h1></header>
            <nav class="card flex">
                $nav $dd
                <span class="ml-auto">$auth <button class="theme-toggle" id="theme-icon">🌙</button></span>
            </nav>
            <main>{$this->out['main']}</main>
            <footer class="text-center mt-3"><small>© 2015-2025 Mark Constable (MIT License)</small></footer>
        </div>
        HTML;
        return $this->html('Simple', $body);
    }
}
