<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Autoload\Core;

use SPE\Autoload\Core\Util;

// Dynamic writable global context/state properties
class Ctx
{
    public function __construct(
        public string $email = 'markc@renta.net',
        public string $buf = '',    // Global string buffer
        public array $ary = [],     // Plugin CRUDL return array
        public array $in = [        // Input URI variables
            'l' => '',              // Log (alert)
            'm' => 'read',          // Method (action)
            'o' => 'Home',          // Object (plugin)
            't' => 'TopNav',        // Theme (current)
            'x' => '',              // XHR (request)
        ],
        public array $out = [       // Theme Method partials
            'doc'   => 'SPE::05 Autoload',
            'css'   => '',
            'log'   => '',
            'nav1'  => '',
            'nav2'  => '',
            'head'  => 'Autoload PHP Example',
            'main'  => 'Error: missing plugin!',
            'foot'  => 'Copyright (C) 2015-2025 Mark Constable (AGPL-3.0)',
            'js'    => '',
        ],
        public array $nav1 = ['Pages',      [
            ['Home',        '?o=Home',      'bi bi-house-door'],
            ['About',       '?o=About',     'bi bi-question-octagon'],
            ['Contact',     '?o=Contact',   'bi bi-person-rolodex']
        ], 'bi bi-list'],
        public array $nav2 = ['Themes',     [
            ['Simple',      '?t=Simple',    'bi bi-gear'],
            ['TopNav',      '?t=TopNav',    'bi bi-gear'],
            ['Sidebar',     '?t=SideBar',   'bi bi-gear'],
        ], 'bi bi-list'],
    )
    {
        Util::elog(__METHOD__);
    }
}
