<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Plugins\About;

use SPE\Autoload\Core\Plugin;

final class AboutModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['title' => 'About', 'body' => 'About has a Model but no View, so the base View renders it as a card. Theme is the only class that knows what a whole HTML document looks like.'];
    }
}
