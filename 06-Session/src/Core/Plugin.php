<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Core;

abstract readonly class Plugin {
    public function __construct(protected Ctx $ctx) {}

    public function list(): array { return []; }
    public function create(): array { return []; }
    public function read(): array { return []; }
    public function update(): array { return []; }
    public function delete(): array { return []; }
}
