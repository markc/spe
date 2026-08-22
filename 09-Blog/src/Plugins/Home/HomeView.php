<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Home;

use SPE\Blog\Core\View;

final class HomeView extends View
{
    #[\Override]
    public function list(): string
    {
        return <<<HTML
<div class="card">
    <h2>{$this->e($this->data['title'])}</h2>
    <p>{$this->e($this->data['body'])}</p>
    <p>Read the <a href="?o=Blog">Blog</a>, browse the <a href="?o=Docs">Docs</a> (this tutorial, served by the app it documents), and sign in as admin@example.com / admin to write. Every earlier chapter is a step towards this one.</p>
</div>
HTML;
    }
}
