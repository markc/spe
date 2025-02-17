<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Session\Core;

// Base Plugin class with CRUDL methods
abstract class Plugin
{
    public function __construct(
        protected Ctx $ctx
    )
    {
        Util::elog(__METHOD__);
    }

    public function create(): array
    {
        Util::elog(__METHOD__);

        return [
            'head' => 'Create',
            'main' => "Plugin::create() not implemented yet!",
            'foot' => __METHOD__
        ];
    }

    public function read(): array
    {
        Util::elog(__METHOD__);

        return [
            'head' => 'Read',
            'main' => "Plugin::read() not implemented yet!",
            'foot' => __METHOD__
        ];
    }

    public function update(): array
    {
        Util::elog(__METHOD__);

        return [
            'head' => 'Update',
            'main' => "Plugin::update() not implemented yet!",
            'foot' => __METHOD__
        ];
    }

    public function delete(): array
    {
        Util::elog(__METHOD__);

        return [
            'head' => 'Delete',
            'main' => "Plugin::delete() not implemented yet!",
            'foot' => __METHOD__
        ];
    }

    public function list(): array
    {
        Util::elog(__METHOD__);

        return [
            'head' => 'List',
            'main' => "Plugin::list() not implemented yet!",
            'foot' => __METHOD__
        ];
    }
}
