<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250209
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Core;

// Dynamic writable global context/state properties
class Ctx
{
    public function __construct(
        public string $buf = '',    // Global string buffer
        public array $ary = [],     // Plugin CRUDL return array
        public array $in = [        // Input URI variables
            'l' => '',              // Log (alert)
            'm' => 'read',          // Method (action)
            'o' => 'Home',          // Object (plugin)
            't' => 'Simple',        // Theme (current)
            'x' => '',              // XHR (request)
        ],
        public array $out = [       // Theme Method partials
            'doc'   => 'SPE::05',
            'css'   => '',
            'log'   => '',
            'nav1'  => '',
            'nav2'  => '',
            'head'  => 'Autoload PHP Example',
            'main'  => 'Error: missing plugin!',
            'foot'  => 'Copyright (C) 2015-2025 Mark Constable (AGPL-3.0)',
            'js'    => '',
        ],
    )
    {
        Util::elog(__METHOD__);
    }
}
