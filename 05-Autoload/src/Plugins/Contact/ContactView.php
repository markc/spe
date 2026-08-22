<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Plugins\Contact;

use SPE\Autoload\Core\View;

final class ContactView extends View
{
    #[\Override]
    public function list(): string
    {
        return <<<HTML
<div class="card">
    <h2>{$this->e($this->data['title'])}</h2>
    <p>{$this->e($this->data['body'])}</p>
    <p><a class="btn" href="mailto:{$this->e($this->data['email'])}">Email us</a></p>
</div>
HTML;
    }
}
