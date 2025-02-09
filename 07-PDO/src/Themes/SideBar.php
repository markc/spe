<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250208
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\PDO\Themes;

use SPE\PDO\Core\Cfg;
use SPE\PDO\Core\Ctx;
use SPE\PDO\Core\Util;

class SideBar extends Base
{
    public function __construct(Cfg $cfg, Ctx $ctx)
    {
        Util::elog(__METHOD__);

        parent::__construct($cfg, $ctx);
    }

    public function foot(): string
    {
        Util::elog(__METHOD__);

        return '

        <footer class="bg-light text-center py-3 mt-auto">
            <div class="container">
                <p class="text-muted mb-0"><small>[SideBar] ' . $this->ctx->out['foot'] . '</small></p>
            </div>
        </footer>';
    }
}
