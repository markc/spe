<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Plugins\Home;

use SPE\PDO\Core\View;

final class HomeView extends View
{
    #[\Override]
    public function list(): string
    {
        return <<<HTML
<div class="card">
    <h2>{$this->e($this->data['title'])}</h2>
    <p>{$this->e($this->data['body'])}</p>
    <p>Go to <a href="?o=Posts">Posts</a> to list, read, add, edit and delete rows.</p>
</div>
HTML;
    }
}
