<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\Home;

use SPE\Session\Core\Plugin;

final class HomeModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['title' => 'Home', 'body' => 'The pages are the same as chapter 05. What is new is invisible until you use the Contact form: the server now has a session, hands out a CSRF token, accepts the form only as a POST carrying that token, then redirects and shows a flash message.'];
    }
}
