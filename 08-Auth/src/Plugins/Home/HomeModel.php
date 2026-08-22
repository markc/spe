<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Auth\Plugins\Home;

use SPE\Auth\Core\Plugin;

final class HomeModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['title' => 'Home', 'body' => 'This chapter adds identity. Sign in as admin@example.com / admin to manage users and edit posts, or user@example.com / user to see a signed-in account without admin rights.'];
    }
}
