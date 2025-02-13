<?php

declare(strict_types=1);
// Created: 20250201 - Updated: 20250213
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\BareBone\Plugins\Example;

use SPE\BareBone\Core\{Plugin, Util};

class ExampleModel extends Plugin
{
    public function create(): array
    {
        Util::elog(__METHOD__);

        return [
            'status' => 'success',
            'message' => 'Create operation'
        ];
    }

    public function read(): array
    {
        Util::elog(__METHOD__);

        return [
            'status' => 'success',
            'message' => 'Read operation'
        ];
    }

    public function update(): array
    {
        Util::elog(__METHOD__);

        return [
            'status' => 'success',
            'message' => 'Update operation'
        ];
    }

    public function delete(): array
    {
        Util::elog(__METHOD__);

        return [
            'status' => 'success',
            'message' => 'Delete operation'
        ];
    }

    public function list(): array
    {
        Util::elog(__METHOD__);

        return [
            'status' => 'success',
            'message' => 'List operation'
        ];
    }
}
