<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Core;

final class Ctx {
    public string $buf = '';
    public array $ary = [];

    public function __construct(
        public private(set) string $email = 'mc@netserva.org',
        public array $in = ['l' => '', 'm' => 'list', 'o' => 'Home', 't' => 'Simple', 'x' => ''],
        public array $out = [
            'doc' => 'SPE::05', 'head' => 'Autoload PHP Example',
            'main' => 'Error: missing plugin!', 'foot' => '© 2015-2025 Mark Constable (MIT License)'
        ],
        public array $nav1 = [['🏠 Home', 'Home'], ['📖 About', 'About'], ['✉️ Contact', 'Contact']],
        public array $nav2 = [['🎨 Simple', 'Simple'], ['📍 TopNav', 'TopNav'], ['📂 SideBar', 'SideBar']]
    ) {
        foreach ($this->in as $k => $v)
            $this->in[$k] = ($_REQUEST[$k] ?? $v) |> trim(...) |> htmlspecialchars(...);
    }
}
