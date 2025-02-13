<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250213
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\BareBone\Themes;

use SPE\BareBone\Core\{Ctx, Theme, Util};

class TopNav extends Theme
{
    public function __construct(Ctx $ctx)
    {
        Util::elog(__METHOD__);

        parent::__construct($ctx);
    }

    public function html(): string
    {
        Util::elog(__METHOD__);

        extract($this->ctx->out, EXTR_SKIP);

        return '<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="dark light">
        <meta name="description" content="Simple PHP Examples">
        <meta name="author" content="Mark Constable">
        <link rel="icon" href="favicon.ico">
        <title>[TopNav] ' . $doc . '</title>
        <script>
            if (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) {
                document.documentElement.setAttribute("data-bs-theme", "dark");
            } else {
                document.documentElement.setAttribute("data-bs-theme", "light");
            }
        </script>' . $css . '
    </head>
    <body class="d-flex flex-column min-vh-100">' . $head . $log . $main . $foot . $js . '
    </body>
</html>';
    }

    public function foot(): string
    {
        Util::elog(__METHOD__);

        return '

        <footer class="text-center py-3 mt-auto">
            <div class="container">
                <p class="text-muted mb-0"><small>&#x2699;  ' . $this->ctx->out['foot'] . '</small></p>
            </div>
        </footer>';
    }
}
