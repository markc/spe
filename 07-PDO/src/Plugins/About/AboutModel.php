<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Plugins\About;

use SPE\PDO\Core\Plugin;

final class AboutModel extends Plugin {
    #[\Override] public function list(): array {
        return ['head' => '📖 About', 'main' => "PHP 8.5 framework with PDO database. Contact: {$this->ctx->email}"];
    }
}
