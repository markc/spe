<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250210
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Auth\Plugins\Home;

use SPE\Auth\Themes\Base;
use SPE\Auth\Core\Util;

final class View extends Base
{
    public function list(): string
    {
        Util::elog(__METHOD__);

        return '
            <div class="px-4 py-5 bg-light rounded-3 border">
                <div class="row d-flex justify-content-center">
                <div class="col-lg-8 col-md-10 col-sm-12">
                    <h1 class="display-5 fw-bold text-center">' . $this->ctx->ary['status'] . '</h1>
                    <p class="lead mb-4">' . $this->ctx->ary['content'] . '</p>
                    <form method="post">
                        <div class="d-flex flex-column flex-sm-row gap-2 mb-4">
                            <button type="button" class="btn btn-success flex-fill" onclick="showToast(\'Everything is working great!\', \'success\');">Success Message</button>
                            <button type="button" class="btn btn-danger flex-fill" onclick="showToast(\'Something went wrong!\', \'danger\');">Danger Message</button>
                        </div>
                    </form>
                    <pre id="dbg" class="text-start overflow-auto"></pre>
                </div>
                </div>
            </div>';
    }
}
