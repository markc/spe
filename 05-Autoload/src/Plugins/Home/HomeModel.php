<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Plugins\Home;

use SPE\Autoload\Core\Plugin;

final class HomeModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return [
            'head' => 'Home Page',
            'main' => 'Welcome to the <b>Autoload</b> example with PSR-4 autoloading via Composer.',
        ];
    }
}
