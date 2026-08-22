<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Plugins\Home;

use SPE\Autoload\Core\Plugin;

final class HomeModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['title' => 'Home', 'body' => 'The page looks exactly like chapters 02, 03 and 04. The classes are the same too; each now lives in its own file and Composer finds it by its namespace, so nothing is ever required by hand again.'];
    }
}
