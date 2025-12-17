<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Core;

final class Ctx {
    public string $buf = '';
    public array $ary = [];

    public function __construct(
        public private(set) string $email = 'mc@netserva.org',
        public array $in = ['l' => '', 'm' => 'list', 'o' => 'Home', 't' => 'Simple', 'x' => ''],
        public array $out = [
            'doc' => 'SPE::06', 'head' => 'Session PHP Example',
            'main' => 'Error: missing plugin!', 'foot' => '© 2015-2025 Mark Constable (MIT License)'
        ],
        public array $nav1 = [['🏠 Home', 'Home'], ['📖 About', 'About'], ['✉️ Contact', 'Contact']],
        public array $nav2 = [['🎨 Simple', 'Simple'], ['📍 TopNav', 'TopNav'], ['📂 SideBar', 'SideBar']]
    ) {
        session_status() === PHP_SESSION_NONE && session_start();
    }

    public function ses(string $k, mixed $v = ''): mixed {
        return $_SESSION[$k] = isset($_REQUEST[$k])
            ? (is_array($_REQUEST[$k]) ? $_REQUEST[$k] : trim($_REQUEST[$k]))
            : ($_SESSION[$k] ?? $v);
    }
}
