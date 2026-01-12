<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Home;

use SPE\Blog\Core\Theme;

final class HomeView extends Theme
{
    public function list(): string
    {
        extract($this->ctx->ary);
        return <<<HTML
        <div class="card">
            <h2>🏠 $head</h2>
            <div>$main</div>
            <footer class="text-muted mt-2"><small>$foot</small></footer>
        </div>
        HTML;
    }
}
