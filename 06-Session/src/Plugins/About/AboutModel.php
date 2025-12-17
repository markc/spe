<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\About;

use SPE\Session\Core\Plugin;

final readonly class AboutModel extends Plugin {
    #[\Override] public function list(): array {
        return ['head' => '📖 About', 'main' => "PHP 8.5 framework with session management. Contact: {$this->ctx->email}"];
    }
}
