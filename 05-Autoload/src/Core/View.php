<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Core;

abstract readonly class View {
    public function __construct(protected Ctx $ctx) {}

    public function list(): string {
        ['head' => $h, 'main' => $m] = $this->ctx->ary;
        return "<div class=\"card\"><h2>{$h}</h2><p>{$m}</p></div>";
    }
}
