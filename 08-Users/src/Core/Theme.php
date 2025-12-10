<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Users\Core;

class Theme {
    public function __construct(protected Ctx $ctx) {}

    protected function nav(array $items, string $param = 'o'): string {
        return $items |> (fn($a) => array_map(fn($n) => sprintf(
            '<a href="?%s=%s"%s>%s</a>', $param, $n[1],
            $this->ctx->in[$param] === $n[1] ? ' class="active"' : '', $n[0]
        ), $a)) |> (fn($l) => implode(' ', $l));
    }
}
