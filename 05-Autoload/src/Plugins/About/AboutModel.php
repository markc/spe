<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Plugins\About;

use SPE\Autoload\Core\Plugin;

final class AboutModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return [
            'head' => 'About Page',
            'main' => 'This chapter adds <b>PSR-4 autoloading</b> via Composer to organize classes into separate files.',
        ];
    }
}
