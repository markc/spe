<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Themes;

use SPE\Autoload\Core\Theme;

final class Simple extends Theme
{
    #[\Override]
    public function render(): string
    {
        $nav = $this->nav();
        $dd = $this->dropdown();
        $colors = $this->colors();
        $body = <<<HTML
        <div class="container">
            <header class="mt-4"><h1><a class="brand" href="/"><i data-lucide="chevron-left"></i> <span>Autoload PHP Example</span></a></h1></header>
            <nav class="card flex">
                $nav $dd $colors
                <span class="ml-auto"><button class="theme-toggle" id="theme-icon"><i data-lucide="moon"></i></button></span>
            </nav>
            <main class="mt-4 mb-4">{$this->out['main']}</main>
            <footer class="text-center"><small>© 2015-2026 Mark Constable (MIT License)</small></footer>
        </div>
        HTML;
        return $this->html('Simple', $body);
    }
}
