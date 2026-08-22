<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Plugins\Contact;

use SPE\Autoload\Core\Plugin;

final class ContactModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['title' => 'Contact', 'body' => 'This form opens your email client. A form the server actually receives arrives in chapter 06.', 'email' => $this->ctx->email];
    }
}
