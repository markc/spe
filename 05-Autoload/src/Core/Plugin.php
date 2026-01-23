<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Core;

abstract class Plugin
{
    public function __construct(protected Ctx $ctx) {}
    public function list(): array { return ['head' => 'List', 'main' => 'Not implemented']; }
}
