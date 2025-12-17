<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Plugins\Home;

use SPE\PDO\Core\Plugin;

final class HomeModel extends Plugin {
    #[\Override] public function list(): array {
        return ['head' => '🏠 Home', 'main' => 'Welcome to SPE::07 PDO - PHP Data Objects database example.'];
    }
}
