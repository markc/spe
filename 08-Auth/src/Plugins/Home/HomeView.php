<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Auth\Plugins\Home;

use SPE\Auth\Core\View;

final class HomeView extends View
{
    #[\Override]
    public function list(): string
    {
        return <<<HTML
<div class="card">
    <h2>{$this->e($this->data['title'])}</h2>
    <p>{$this->e($this->data['body'])}</p>
    <p>Open <a href="?o=Posts">Posts</a> while signed out: you can read but the New/Edit/Delete controls are gone. The <a href="?o=Users">Users</a> page is admin-only — signed out, it sends you to the login form.</p>
</div>
HTML;
    }
}
