<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Htmx\Plugins\Home;

use SPE\Htmx\Core\Theme;

final class HomeView extends Theme
{
    public function list(): string
    {
        extract($this->ctx->ary);
        return <<<HTML
        <div class="card">
            <h2><i data-lucide="home" class="inline-icon"></i> $head</h2>
            <div>$main</div>
            <footer class="text-muted mt-2"><small>$foot</small></footer>
        </div>
        HTML;
    }
}
