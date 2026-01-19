<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Plugins\Contact;

use SPE\Autoload\Core\Plugin;

final class ContactModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['head' => 'Contact Page', 'main' => 'Get in touch using the <b>email form</b> below.'];
    }
}
