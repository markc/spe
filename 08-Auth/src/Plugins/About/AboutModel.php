<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Auth\Plugins\About;

use SPE\Auth\Core\Plugin;

final class AboutModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['title' => 'About', 'body' => 'Role is an ordered enum and can() is the whole authorization system. Each plugin declares the minimum role per method with guard(); Init checks it before the plugin runs. Login creates a session (with session_regenerate_id), logout destroys it, and an optional remember-me cookie stores a random selector and a hashed validator that is rotated every time it is used.'];
    }
}
