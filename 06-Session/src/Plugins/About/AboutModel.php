<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\About;

use SPE\Session\Core\Plugin;

final class AboutModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        $_SESSION['visit_count'] = ($_SESSION['visit_count'] ?? 0) + 1;
        $_SESSION['last_page'] = 'About';

        return [
            'head' => 'About Page',
            'main' => 'This chapter adds <b>PHP session management</b> for persistent state across requests.',
        ];
    }
}
