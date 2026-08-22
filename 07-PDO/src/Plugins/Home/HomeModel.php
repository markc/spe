<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Plugins\Home;

use SPE\PDO\Core\Plugin;

final class HomeModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['title' => 'Home', 'body' => 'This chapter adds a database. A SQLite file is created from schema.sql the first time you load the page, and the Posts plugin reads and writes it entirely through prepared statements.'];
    }
}
