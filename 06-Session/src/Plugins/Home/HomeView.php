<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\Home;

use SPE\Session\Core\View;

final class HomeView extends View
{
    #[\Override]
    public function list(): string
    {
        return <<<HTML
<div class="card">
    <h2>{$this->e($this->data['title'])}</h2>
    <p>{$this->e($this->data['body'])}</p>
    <p>Open <a href="?o=Contact">Contact</a>, send the form, and watch the toast appear after the redirect. Reload the page afterwards: the message does not repeat, because a flash is shown once and then cleared.</p>
</div>
HTML;
    }
}
