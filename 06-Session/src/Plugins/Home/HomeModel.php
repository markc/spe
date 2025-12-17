<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\Home;

use SPE\Session\Core\{Plugin, Util};

final readonly class HomeModel extends Plugin {
    #[\Override] public function list(): array {
        $_SESSION['first_visit'] ??= time();
        $_SESSION['visit_count'] = ($_SESSION['visit_count'] ?? 0) + 1;
        return [
            'head' => '🏠 Home', 'main' => 'Welcome to SPE::06 Session - demonstrating PHP session management.',
            'first_visit' => $_SESSION['first_visit'], 'visit_count' => $_SESSION['visit_count'],
            'time_ago' => Util::timeAgo($_SESSION['first_visit'])
        ];
    }

    public function reset(): array {
        session_destroy();
        session_start();
        $_SESSION['first_visit'] = time();
        $_SESSION['visit_count'] = 1;
        return ['head' => '🏠 Home', 'main' => 'Session has been reset!',
            'first_visit' => $_SESSION['first_visit'], 'visit_count' => 1, 'time_ago' => 'just now'];
    }
}
