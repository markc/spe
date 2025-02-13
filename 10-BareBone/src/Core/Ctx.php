<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250214
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\BareBone\Core;

use SPE\BareBone\Core\Util;

// Dynamic writable global context/state properties
class Ctx
{
    public function __construct(
        public string $email = '',  // Auto login and outgoing mail
        public string $admpw = '',  // Trigger auto login for devel
        public string $host = '',   // Outgoing forgot_passwd email
        public string $buf = '',    // Global string buffer
        public array $ary = [],     // Plugin CRUDL return array
        public array $nav = [],     // PluginNav array
        public array $in = [        // Input URI variables
            'a' => '',              // API credentials
            'c' => '',              // ??? Category
            'g' => '',              // ??? Group
            'l' => '',              // Log (alert)
            'm' => 'list',          // Method (action)
            'o' => 'Home',          // Object (plugin)
            'p' => '1',             // Page (current)
            't' => 'TopNav',        // Theme (current)
            'x' => '',              // XHR (request)
        ],
        public array $out = [       // Theme Method partials
            'doc'   => 'SPE::10 BareBone',
            'css'   => '',
            'log'   => '',
            'nav1'  => '',
            'nav2'  => '',
            'head'  => 'BareBone PHP Example',
            'main'  => 'Error: missing plugin!',
            'foot'  => 'Copyright (C) 2015-2025 Mark Constable (AGPL-3.0)',
            'js'    => '',
        ],
        public array $nav1 = [
            ['Home',    '?o=home',      'bi bi-house-door'],
            ['Example', '?o=example',   'bi bi-gear'],
        ],
    )
    {
        Util::elog(__METHOD__);
    }
}
