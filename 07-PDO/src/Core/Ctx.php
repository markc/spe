<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250218
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\PDO\Core;

use SPE\PDO\Core\Util;

// Dynamic writable global context/state properties
class Ctx
{
    public function __construct(
        public string $email = 'markc@renta.net',
        public string $buf = '',    // Global string buffer
        public array $ary = [],     // Plugin CRUDL return array
        public array $nav = [],     // Plugin Nav Renderer
        public array $in = [        // Input URI variables
            //'i' => 0,               // Current ID/Item
            'l' => '',              // Log (alert)
            'm' => 'list',          // Method (action)
            'o' => 'Home',          // Object (plugin)
            //'p' => 1,               // Current Page
            't' => 'TopNav',        // Theme (current)
            'x' => '',              // XHR (request)
        ],
        public array $out = [       // Theme Method partials
            'doc'   => 'SPE::07 PDO',
            'css'   => '',
            'log'   => '',
            'nav1'  => '',
            'nav2'  => '',
            'head'  => 'PDO PHP Example',
            'main'  => 'Error: missing plugin!',
            'foot'  => 'Copyright (C) 2015-2025 Mark Constable (AGPL-3.0)',
            'js'    => '',
        ],
        public array $nav1 = [
            ['Pages',      [
                ['Home',        '?o=Home',      'bi bi-house-door',         'ajax-link'],
                ['About',       '?o=About',     'bi bi-question-octagon',   'ajax-link'],
                ['News',        '?o=News',      'bi bi-newspaper',          'ajax-link'],
                ['Contact',     '?o=Contact',   'bi bi-person-rolodex',     'ajax-link']
            ], 'bi bi-list']
        ],
        public array $nav2 = [
            [
                'Themes',       // Group name
                [               // Group items array
                    ['Simple',      '?t=Simple',    'bi bi-gear',   ''],
                    ['TopNav',      '?t=TopNav',    'bi bi-gear',   ''],
                    ['Sidebar',     '?t=SideBar',   'bi bi-gear',   ''],
                ],
                'bi bi-list'    // Group icon
            ],
            // Standalone items remain as they were
            ['Webmail',     'webmail/',         'bi bi-envelope',   'ajax-link'],
            ['Phpmyadmin',  'phpmyadmin/',      'bi bi-globe',      'ajax-link'],
        ],
    )
    {
        Util::elog(__METHOD__);
    }
}
