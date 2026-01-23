<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Plugins\Home;

use SPE\Autoload\Core\Plugin;

final class HomeModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return [
            'head' => 'PSR-4 Autoloading',
            'main' => '<p>This chapter introduces <b>Composer</b> and <b>PSR-4 autoloading</b> to organize classes into separate files with proper namespaces.</p>',
        ];
    }
}
