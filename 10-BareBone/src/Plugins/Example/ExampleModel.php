<?php

declare(strict_types=1);
// Created: 20250201 - Updated: 20250214
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\BareBone\Plugins\Example;

use SPE\BareBone\Core\{Plugin, Util};

class ExampleModel extends Plugin
{
    public function create(): array
    {
        Util::elog(__METHOD__);

        return ['msg' => __METHOD__];
    }

    public function read(): array
    {
        Util::elog(__METHOD__);

        return ['msg' => __METHOD__];
    }

    public function update(): array
    {
        Util::elog(__METHOD__);

        return ['msg' => __METHOD__];
    }

    public function delete(): array
    {
        Util::elog(__METHOD__);

        return ['msg' => __METHOD__];
    }

    public function list(): array
    {
        Util::elog(__METHOD__);

        return ['msg' => __METHOD__];
    }
}
