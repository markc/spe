<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\Home;

use SPE\Session\Core\{Plugin, Util};

final class HomeModel extends Plugin {
    #[\Override] public function list(): array {
        $_SESSION['first_visit'] ??= time();
        $_SESSION['visit_count'] = ($_SESSION['visit_count'] ?? 0) + 1;
        return [
            'head' => 'Home Page',
            'main' => 'Welcome to the <b>Session</b> example demonstrating PHP session management.',
            'first_visit' => $_SESSION['first_visit'],
            'visit_count' => $_SESSION['visit_count'],
            'time_ago' => Util::timeAgo($_SESSION['first_visit']),
            'session_id' => session_id()
        ];
    }

    public function reset(): array {
        $this->ctx->flash('msg', 'Session has been reset!');
        $this->ctx->flash('type', 'success');
        session_destroy();
        session_start();
        $_SESSION['first_visit'] = time();
        $_SESSION['visit_count'] = 1;
        $_SESSION['o'] = 'Home';
        $_SESSION['t'] = $this->ctx->in['t'];
        return [
            'head' => 'Home Page',
            'main' => 'Session reset - all data cleared and reinitialized.',
            'first_visit' => $_SESSION['first_visit'],
            'visit_count' => 1,
            'time_ago' => 'just now',
            'session_id' => session_id()
        ];
    }
}
