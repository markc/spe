<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\PDO\Core;

class Theme
{
    public function __construct(private Ctx $ctx)
    {
        Util::elog(__METHOD__);
    }
    /*
    public function __call($m, $a): ?string
    {
        Util::elog(__METHOD__ . " method: " . $m . ", class: " . get_class($this));

        if (isset($this->ctx->ary[$m]))
        {
            return $this->ctx->ary[$m];
        }

        return null;
    }
    */
}
