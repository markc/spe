<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250208
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Users\Core;

// Base Plugin class with CRUDL methods
abstract class Plugin
{
    public function __construct(
        protected Cfg $cfg,
        protected Ctx $ctx
    )
    {
        Util::elog(__METHOD__);
    }

    public function create(): void
    {
        Util::elog(__METHOD__);

        $this->ctx->ary = [
            'status' => 'Success',
            'content' => "Plugin::create() not implemented yet!"
        ];
    }

    public function read(): void
    {
        Util::elog(__METHOD__);

        $this->ctx->ary = [
            'status' => 'Success',
            'content' => "Plugin::read() not implemented yet!"
        ];
    }

    public function update(): void
    {
        Util::elog(__METHOD__);

        $this->ctx->ary = [
            'status' => 'Success',
            'content' => "Plugin::update() not implemented yet!"
        ];
    }

    public function delete(): void
    {
        Util::elog(__METHOD__);

        $this->ctx->ary = [
            'status' => 'Success',
            'content' => "Plugin::delete() not implemented yet!"
        ];
    }

    public function list(): void
    {
        Util::elog(__METHOD__);

        $this->ctx->ary = [
            'status' => 'Success',
            'content' => "Plugin::list() not implemented yet!"
        ];
    }
}
