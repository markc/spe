<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250209
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Auth\Core;

// Static read-only global config properties
readonly class Cfg
{
    public function __construct(
        public string $email = 'markc@renta.net',
        public string $self  = '/',
        public array $nav1 = [
            ['Home',        '?o=Home',      'bi-home'],
            ['About',       '?o=About',     'bi-question-circle'],
            ['News',        '?o=News',      'bi-newspaper'],
            ['Contact',     '?o=Contact',   'bi-person-rolodex'],
        ],
        public array $nav2 = [
            ['Simple',      '?t=Simple'],
            ['TopNav',      '?t=TopNav'],
            ['Sidebar',     '?t=SideBar'],
        ],
    )
    {
        Util::elog(__METHOD__ . ' ' . __DIR__);
    }
}
