<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

enum Flash: string
{
    case Success = 'success';
    case Danger = 'danger';
    case Warning = 'warning';

    public function icon(): string
    {
        return match ($this) {
            self::Success => 'check-circle',
            self::Danger => 'alert-circle',
            self::Warning => 'alert-triangle',
        };
    }
}
