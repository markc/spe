<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Plugins\Home;

use SPE\Autoload\Core\Plugin;

final readonly class HomeModel extends Plugin {
    #[\Override] public function list(): array {
        return ['head' => '🏠 Home', 'main' => 'Welcome to SPE::05 Autoload - PSR-4 autoloading with Composer.'];
    }
}
