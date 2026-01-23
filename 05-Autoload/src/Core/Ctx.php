<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Core;

final class Ctx
{
    public array $in;

    public function __construct(
        public string $email = 'mc@netserva.org',
        array $in = ['o' => 'Home', 'm' => 'list', 'x' => ''],
        public array $out = ['doc' => 'SPE::05', 'page' => '← 05 Autoload', 'head' => '', 'main' => '', 'foot' => ''],
        public array $nav = [['home', 'Home', 'Home'], ['book-open', 'About', 'About'], ['mail', 'Contact', 'Contact']],
        public array $colors = [['circle', 'Stone', 'default'], ['waves', 'Ocean', 'ocean'], ['trees', 'Forest', 'forest'], ['sunset', 'Sunset', 'sunset']],
    ) {
        $this->in = array_map(fn($k, $v) => ($_REQUEST[$k] ?? $v) |> trim(...) |> htmlspecialchars(...), array_keys($in), $in)
            |> (fn($v) => array_combine(array_keys($in), $v));
    }
}
