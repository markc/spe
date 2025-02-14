<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250210
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\BareBone\Plugins\Home;

use SPE\BareBone\Core\{Plugin, Util};

final class HomeModel extends Plugin
{
    public function list(): array
    {
        Util::elog(__METHOD__);

        return [
            'head' => 'SPE::10 BareBone Example',
            'main' => 'This BareBone sub-project only has the Core classes plus two simple plugins; Home and Example. The purpose of this sub-project is to be able to start a fresh project from a simple baseline and add whatever plugins you care to following the guide at <a href="https://github.com/markc/spe/tree/master/10-BareBone">10-BareBone</a> and the two simple demo Plugins.',
            'foot' => __METHOD__ . ' (action)<br>Using the ' . $this->ctx->in['t'] . ' theme'
        ];
    }
}
