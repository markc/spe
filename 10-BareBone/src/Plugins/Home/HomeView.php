<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250213
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\BareBone\Plugins\Home;

use SPE\BareBone\Core\{Ctx, Util};

final class HomeView
{
    public function __construct(private Ctx $ctx)
    {
        Util::elog(__METHOD__);
    }

    public function list(): string
    {
        Util::elog(__METHOD__);

        return '
            <div class="px-4 py-5 rounded-3 border">
                <div class="row d-flex justify-content-center">
                    <div class="col-lg-8 col-md-10 col-sm-12">
                        <h1 class="display-5 fw-bold text-center">&#x2699; ' . $this->ctx->ary['head'] . '</h1>
                        <p class="lead mb-4">' . $this->ctx->ary['main'] . '</p>  
                        <p class="text-center">
                            <a class="btn btn-primary" href="https://github.com/markc/spe">&#x2699; SPE Project Page</a>
                            <a class="btn btn-primary" href="https://github.com/markc/spe/issues">&#x2699; SPE Issue Tracker</a>
                        </p>
                        <footer class="mb-4 text-center">' . $this->ctx->ary['foot'] . '</footer>
                    </div>
                </div>
            </div>';
    }
}
