<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\Contact;

use SPE\Session\Core\Plugin;

final readonly class ContactModel extends Plugin {
    #[\Override] public function list(): array {
        return ['head' => '✉️ Contact', 'main' => 'form', 'email' => $this->ctx->email];
    }
}
