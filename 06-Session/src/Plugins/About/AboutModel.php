<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\About;

use SPE\Session\Core\Plugin;

final class AboutModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return [
            'head' => 'About Page',
            'main' => 'This chapter adds <b>PHP session management</b> with sticky URL parameters, flash messages, and visit tracking.',
        ];
    }
}
