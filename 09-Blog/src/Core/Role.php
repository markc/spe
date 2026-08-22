<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

/** The access levels, ordered. can() is the whole authorization system. */
enum Role: string
{
    case Anon = 'Anon';
    case User = 'User';
    case Admin = 'Admin';

    public function can(self $needed): bool
    {
        return $this->level() >= $needed->level();
    }

    private function level(): int
    {
        return match ($this) {
            self::Anon => 0,
            self::User => 1,
            self::Admin => 2,
        };
    }
}
