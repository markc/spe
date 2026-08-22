<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Home;

use SPE\Blog\Core\Plugin;

final class HomeModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['title' => 'Simple PHP Engine', 'body' => 'The finished application: a small content engine. Posts and docs share one table and one set of code, bodies are written in Markdown, entries carry tags, lists paginate, and the whole thing runs on plain PHP 8.5 with no framework.'];
    }
}
