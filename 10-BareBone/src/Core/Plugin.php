<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250213
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\BareBone\Core;

// Base Plugin class with CRUDL action methods
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
            'status' => 'Success',
            'content' => "Plugin::create() not implemented yet!"
        ];
    }

    public function read(): array
    {
        Util::elog(__METHOD__);

        return [
            'status' => 'Success',
            'content' => "Plugin::read() not implemented yet!"
        ];
    }

    public function update(): array
    {
        Util::elog(__METHOD__);

        return [
            'status' => 'Success',
            'content' => "Plugin::update() not implemented yet!"
        ];
    }

    public function delete(): array
    {
        Util::elog(__METHOD__);

        return [
            'status' => 'Success',
            'content' => "Plugin::delete() not implemented yet!"
        ];
    }

    public function list(): array
    {
        Util::elog(__METHOD__);

        return [
            'status' => 'Success',
            'content' => "Plugin::list() not implemented yet!"
        ];
    }
}
