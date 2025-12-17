<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Core;

abstract readonly class Theme {
    public function __construct(protected Ctx $ctx) {}

    abstract public function html(): string;

    protected function nav(array $items, string $p = 'o'): string {
        ['t' => $t, 'o' => $o] = $this->ctx->in;
        return $items
            |> (fn($a) => array_map(fn($n) => sprintf('<a href="?o=%s&t=%s"%s>%s</a>',
                $p === 'o' ? $n[1] : $o, $p === 't' ? $n[1] : $t,
                $this->ctx->in[$p] === $n[1] ? ' class="active"' : '', $n[0]), $a))
            |> (fn($l) => implode(' ', $l));
    }
}
