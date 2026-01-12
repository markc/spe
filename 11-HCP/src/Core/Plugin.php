<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Core;

abstract class Plugin
{
    public function __construct(
        protected Ctx $ctx,
    ) {}

    public function create(): array|string
    {
        return ['head' => 'Create', 'main' => 'Plugin::create() not implemented yet!'];
    }

    public function read(): array|string
    {
        return ['head' => 'Read', 'main' => 'Plugin::read() not implemented yet!'];
    }

    public function update(): array|string
    {
        return ['head' => 'Update', 'main' => 'Plugin::update() not implemented yet!'];
    }

    public function delete(): array|string
    {
        return ['head' => 'Delete', 'main' => 'Plugin::delete() not implemented yet!'];
    }

    public function list(): array|string
    {
        return ['head' => 'List', 'main' => 'Plugin::list() not implemented yet!'];
    }
}
