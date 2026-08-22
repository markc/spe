<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

abstract class Plugin
{
    public function __construct(protected Ctx $ctx) {}

    /** The minimum role allowed to call $m. Public by default; plugins tighten it. */
    public static function guard(string $m): Role
    {
        return Role::Anon;
    }

    /** @return array{title: string, body: string} */
    public function create(): array { return $this->todo('create'); }
    public function read(): array { return $this->todo('read'); }
    public function update(): array { return $this->todo('update'); }
    public function delete(): array { return $this->todo('delete'); }
    public function list(): array { return $this->todo('list'); }

    protected function redirect(string $query): never
    {
        header("Location: $query");
        exit;
    }

    private function todo(string $m): array
    {
        return ['title' => ucfirst($m), 'body' => static::class . "::$m() is not implemented."];
    }
}
