<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Core;

/**
 * Context container for YouTube Manager
 * Includes navigation arrays for themes
 */
final class Ctx {
    public function __construct(
        public string $buf = '',
        public array $ary = [],
        public array $in = [
            'id' => 0,
            'l' => '',
            'm' => 'list',
            'o' => 'Dashboard',
            't' => 'Simple',
            'x' => ''
        ],
        public array $out = [
            'doc' => 'SPE::10',
            'css' => '',
            'log' => '',
            'main' => 'Error: missing plugin!',
            'head' => 'YouTube Manager',
            'foot' => '© 2015-2025 Mark Constable (MIT License)',
            'js' => ''
        ],
        // Navigation items for YouTube Manager
        public array $navPages = [
            ['🏠 Dashboard', 'Dashboard'],
            ['📹 Videos', 'Videos'],
            ['📋 Playlists', 'Playlists'],
            ['📊 Channel', 'Channel'],
        ],
        // Theme options
        public array $nav2 = [
            ['🎨 Simple', 'Simple'],
            ['📍 TopNav', 'TopNav'],
            ['📂 SideBar', 'SideBar'],
        ],
    ) {}
}
