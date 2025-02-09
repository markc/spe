<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250209
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Autoload\Plugins\Home;

use SPE\Autoload\Core\Plugin;
use SPE\Autoload\Core\Util;

final class Model extends Plugin
{
    public function read(): void
    {
        Util::elog(__METHOD__);

        $this->ctx->ary = [
            'status' => 'Success',
            'content' => 'Inside Plugins/Home/Model::class'
        ];
    }
}
