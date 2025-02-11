<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250212
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Auth\Core;

// Dynamic writable global context/state properties
class Ctx
{
    public function __construct(
        public string $buf = '',    // Global string buffer
        public array $ary = [],     // Plugin CRUDL return array
        public array $nav = [],     // PluginNav array
        public array $in = [        // Input URI variables
            'i' => 1,               // Item/ID
            'l' => '',              // Log (alert)
            'm' => 'list',          // Method (action)
            'o' => 'Home',          // Object (plugin)
            'p' => '1',             // Page (current)
            't' => 'Simple',        // Theme (current)
            'x' => '',              // XHR (request)
        ],
        public array $out = [       // Theme Method partials
            'doc'   => 'SPE::09 Auth',
            'css'   => '',
            'log'   => '',
            'nav1'  => '',
            'nav2'  => '',
            'head'  => 'Auth PHP Example',
            'main'  => 'Error: missing plugin!',
            'foot'  => 'Copyright (C) 2015-2025 Mark Constable (AGPL-3.0)',
            'js'    => '',
        ],
    )
    {
        Util::elog(__METHOD__);
    }
}
