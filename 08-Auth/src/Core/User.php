<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Auth\Core;

/** The signed-in user. Immutable: nothing changes a User after it is built from a row. */
final readonly class User
{
    public function __construct(
        public int $id,
        public string $email,
        public string $name,
        public Role $role,
    ) {}

    /** @param array{id:int|string,email:string,name:string,role:string} $row */
    public static function fromRow(array $row): self
    {
        return new self((int) $row['id'], $row['email'], $row['name'], Role::from($row['role']));
    }
}
