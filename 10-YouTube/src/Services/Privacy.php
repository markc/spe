<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Services;

/**
 * YouTube video/playlist privacy status enum
 */
enum Privacy: string
{
    case Private = 'private';
    case Unlisted = 'unlisted';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Private => '🔒 Private',
            self::Unlisted => '🔗 Unlisted',
            self::Public => '🌐 Public',
        };
    }

    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::Private;
    }
}
