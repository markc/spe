<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250212
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Auth\Themes;

use SPE\Auth\Core\{Cfg, Ctx, Theme, Util};

class TopNav extends Theme
{
    public function __construct(Cfg $cfg, Ctx $ctx)
    {
        Util::elog(__METHOD__);

        parent::__construct($cfg, $ctx);
    }

    public function html(): string
    {
        Util::elog(__METHOD__);

        extract($this->ctx->out, EXTR_SKIP);

        return '<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Simple PHP Example with Plugins">
        <meta name="author" content="Mark Constable">
        <link rel="icon" href="favicon.ico">
        <title>[TopNav] ' . $doc . '</title>' . $css . '
    </head>
    <body class="d-flex flex-column min-vh-100">' . $head . $log . $main . $foot . $js . '
    </body>
</html>';
    }

    public function foot(): string
    {
        Util::elog(__METHOD__);

        return '

        <footer class="bg-light text-center py-3 mt-auto">
            <div class="container">
                <p class="text-muted mb-0"><small>[TopNav] ' . $this->ctx->out['foot'] . '</small></p>
            </div>
        </footer>';
    }
}
