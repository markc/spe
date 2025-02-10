<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250210
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\PDO\Plugins\Contact;

use SPE\PDO\Core\Plugin;
use SPE\PDO\Core\Util;

final class Model extends Plugin
{
    public function list(): void
    {
        Util::elog(__METHOD__);

        $this->ctx->ary = [
            'status' => 'Success',
            'content' => 'Inside Plugins/Contact/Model::class'
        ];
    }
}
