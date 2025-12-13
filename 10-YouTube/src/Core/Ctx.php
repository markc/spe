<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Core;

/**
 * Context container for YouTube Manager
 * Simplified from Blog - no database nav, no admin sections
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
    ) {}
}
