<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

/** One posts table holds several kinds of content; Type says which. */
enum Type: string
{
    case Post = 'post';
    case Doc = 'doc';

    public function label(): string
    {
        return match ($this) {
            self::Post => 'Blog',
            self::Doc => 'Docs',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Post => 'newspaper',
            self::Doc => 'book-open',
        };
    }
}
