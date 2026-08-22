<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Plugins\About;

use SPE\PDO\Core\Plugin;

final class AboutModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['title' => 'About', 'body' => 'Db is a small PDO subclass: create(), read(), update() and delete() build the SQL, bind every value with its type, and never interpolate a request value. read() takes a QueryType enum to choose whether it returns all rows, one row or one column. The write methods are marked #[\\NoDiscard] so their result cannot be silently ignored.'];
    }
}
