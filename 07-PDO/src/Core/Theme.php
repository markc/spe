<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Core;

class Theme {
    public function __construct(protected Ctx $ctx) {}

    protected function nav(array $items, string $param = 'o'): string {
        ['o' => $o, 't' => $t] = $this->ctx->in;
        return $items |> (fn($a) => array_map(fn($n) => sprintf(
            '<a href="?o=%s&t=%s"%s>%s</a>',
            $param === 'o' ? $n[1] : $o, $param === 't' ? $n[1] : $t,
            $this->ctx->in[$param] === $n[1] ? ' class="active"' : '', $n[0]
        ), $a)) |> (fn($l) => implode(' ', $l));
    }
}
