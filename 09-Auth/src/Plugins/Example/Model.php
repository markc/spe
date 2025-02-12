<?php

declare(strict_types=1);
// Created: 20250201 - Updated: 20250212
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Auth\Plugins\Example;

use SPE\Auth\Core\{Plugin, Util};

class Model extends Plugin
{
    public function create(): void
    {
        Util::elog(__METHOD__);

        $this->ctx->ary = [
            'status' => 'success',
            'message' => 'Create operation'
        ];
    }

    public function read(): void
    {
        Util::elog(__METHOD__);

        $this->ctx->ary = [
            'status' => 'success',
            'message' => 'Read operation'
        ];
    }

    public function update(): void
    {
        Util::elog(__METHOD__);

        $this->ctx->ary = [
            'status' => 'success',
            'message' => 'Update operation'
        ];
    }

    public function delete(): void
    {
        Util::elog(__METHOD__);

        $this->ctx->ary = [
            'status' => 'success',
            'message' => 'Delete operation'
        ];
    }

    public function list(): void
    {
        Util::elog(__METHOD__);

        $this->ctx->ary = [
            'status' => 'success',
            'message' => 'List operation'
        ];
    }
}
