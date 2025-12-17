<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Core;

abstract readonly class Theme {
    public function __construct(protected Ctx $ctx) {}

    abstract public function html(): string;

    protected function nav(array $items, string $p = 'o'): string {
        return $items
            |> (fn($a) => array_map(fn($n) => sprintf('<a href="?%s=%s"%s>%s</a>',
                $p, $n[1], $this->ctx->in[$p] === $n[1] ? ' class="active"' : '', $n[0]), $a))
            |> (fn($l) => implode(' ', $l));
    }
}
