<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\About;

use SPE\Blog\Core\Plugin;

final class AboutModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['title' => 'About', 'body' => 'Blog and Docs are the same code with a different Type; Content holds the shared CRUDL. A Post exposes html and excerpt as property hooks — computed on read, never stored stale. Markdown is rendered by a pipe of pure steps that escapes first and only allows safe link schemes. Prev/next and first/last use array_first() and array_last().'];
    }
}
