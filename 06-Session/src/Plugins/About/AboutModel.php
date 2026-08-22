<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\About;

use SPE\Session\Core\Plugin;

final class AboutModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['title' => 'About', 'body' => 'Ctx now starts the session, keeps a CSRF token, and offers post() and flash(). post() returns the form data only for a POST whose token matches, so every write is one guarded line. Flash messages survive the redirect in the session and Theme turns them into toasts.'];
    }
}
