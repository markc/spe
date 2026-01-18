<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Htmx\Core;

abstract class Plugin
{
    public function __construct(
        protected Ctx $ctx,
    ) {}

    public function create(): array
    {
        return ['head' => 'Create', 'main' => 'Plugin::create() not implemented yet!', 'foot' => __METHOD__];
    }

    public function read(): array
    {
        return ['head' => 'Read', 'main' => 'Plugin::read() not implemented yet!', 'foot' => __METHOD__];
    }

    public function update(): array
    {
        return ['head' => 'Update', 'main' => 'Plugin::update() not implemented yet!', 'foot' => __METHOD__];
    }

    public function delete(): array
    {
        return ['head' => 'Delete', 'main' => 'Plugin::delete() not implemented yet!', 'foot' => __METHOD__];
    }

    public function list(): array
    {
        return ['head' => 'List', 'main' => 'Plugin::list() not implemented yet!', 'foot' => __METHOD__];
    }
}
