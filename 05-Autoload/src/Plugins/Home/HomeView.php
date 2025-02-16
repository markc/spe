<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Autoload\Plugins\Home;

use SPE\Autoload\Core\{Ctx, Util};

final class HomeView
{
    public function __construct(
        private Ctx $ctx
    )
    {
        Util::elog(__METHOD__);
    }

    public function read(): string
    {
        Util::elog(__METHOD__);

        return '
    <div class="px-4 py-5 rounded-3 border bg-body-tertiary">
        <div class="row d-flex justify-content-center">
            <h1 class="display-5 fw-bold text-center"><i class="bi bi-gear"></i> ' . $this->ctx->ary['head'] . '</h1>
            <div class="col-lg-8 col-md-10 col-sm-12">' . $this->ctx->ary['main'] . '
                <footer class="mb-4 text-center">' . $this->ctx->ary['foot'] . '</footer>
            </div>
        </div>
    </div>';
    }
}
