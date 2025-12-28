<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Core;

final class Ctx {
    public array $in;
    public array $out;

    public function __construct(
        public string $email = 'mc@netserva.org',
        array $in = ['o' => 'Home', 'm' => 'list', 't' => 'Simple', 'x' => ''],
        array $out = ['doc' => 'SPE::05', 'head' => '', 'main' => '', 'foot' => ''],
        public array $nav = [['🏠 Home', 'Home'], ['📖 About', 'About'], ['✉️ Contact', 'Contact']],
        public array $themes = [['🎨 Simple', 'Simple'], ['🎨 TopNav', 'TopNav'], ['🎨 SideBar', 'SideBar']]
    ) {
        $this->in = array_map(static fn($k, $v) => ($_REQUEST[$k] ?? $v)
            |> trim(...)
            |> htmlspecialchars(...), array_keys($in), $in)
            |> (static fn($v) => array_combine(array_keys($in), $v));
        $this->out = $out;
    }
}
