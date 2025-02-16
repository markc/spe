<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Autoload\Core;

class Theme
{
    public function __construct(private Ctx $ctx)
    {
        Util::elog(__METHOD__);
    }

    public function __call($m, $a): string
    {
        Util::elog(__METHOD__ . " method: " . $m . ", class: " . get_class($this));

        return __METHOD__ . ' m=' . $m;
    }
}
