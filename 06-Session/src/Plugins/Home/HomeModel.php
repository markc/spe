<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\Home;

use SPE\Session\Core\{Plugin, Util};

final class HomeModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        $_SESSION['first_visit'] ??= time();
        $_SESSION['visit_count'] = ($_SESSION['visit_count'] ?? 0) + 1;
        $_SESSION['last_page'] = 'Home';

        return [
            'head' => 'PHP Session Management',
            'main' => '<p>This chapter introduces <b>PHP sessions</b> for persistent state across requests.</p>',
            'first_visit' => $_SESSION['first_visit'],
            'visit_count' => $_SESSION['visit_count'],
            'time_ago' => Util::timeAgo($_SESSION['first_visit']),
            'session_id' => session_id(),
            'session_name' => session_name(),
            'session_size' => Util::formatBytes(strlen(serialize($_SESSION))),
            'session_keys' => array_keys($_SESSION),
        ];
    }

    public function reset(): array
    {
        $this->ctx->flash('msg', 'Session destroyed and regenerated!');
        $this->ctx->flash('type', 'success');
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $_SESSION['first_visit'] = time();
        $_SESSION['visit_count'] = 1;
        $_SESSION['o'] = 'Home';

        return $this->list();
    }

    public function regenerate(): array
    {
        $old_id = session_id();
        session_regenerate_id(true);
        $this->ctx->flash('msg', "Session ID regenerated! Old: {$old_id}");
        $this->ctx->flash('type', 'success');

        return $this->list();
    }
}
