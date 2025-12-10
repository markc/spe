<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Plugins\About;

use SPE\PDO\Core\Ctx;

final class AboutView {
    public function __construct(private Ctx $ctx) {}

    public function list(): string {
        return "<div class=\"card\"><h2>{$this->ctx->ary['head']}</h2><p>{$this->ctx->ary['main']}</p></div>";
    }
}
