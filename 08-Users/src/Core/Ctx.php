<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Users\Core;

final class Ctx {
    public array $nav1;
    public array $nav2;

    public function __construct(
        public private(set) string $email = 'mc@netserva.org',
        public string $buf = '',
        public array $ary = [],
        public array $nav = [],
        public array $in = ['id' => 0, 'l' => '', 'm' => 'list', 'o' => 'Home', 't' => 'Simple', 'x' => ''],
        public array $out = [
            'doc' => 'SPE::08', 'css' => '', 'log' => '', 'nav1' => '', 'nav2' => '',
            'main' => 'Error: missing plugin!',
            'head' => 'Users PHP Example',
            'foot' => '© 2015-2025 Mark Constable (MIT License)',
            'js' => ''
        ],
        public ?PluginLoader $loader = null,
    ) {
        // Auto-discover plugins and themes
        $this->loader = new PluginLoader();
        $this->nav1 = $this->loader->buildNav1();
        $this->nav2 = $this->loader->buildNav2();
    }
}
