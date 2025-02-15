<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250213
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\BareBone\Core;

use SPE\BareBone\Core\{Ctx, Util};

abstract class Theme
{
    protected Ctx $ctx;

    public function __construct(Ctx $ctx)
    {
        Util::elog(__METHOD__);

        $this->ctx = $ctx;
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
        <title>[Theme] ' . $doc . '</title>' . $css . '
    </head>
    <body class="d-flex flex-column min-vh-100">' . $head . $log . $main . $foot . $js . '
    </body>
</html>';
    }

    public function css(): string
    {
        Util::elog(__METHOD__);

        return '';
    }

    public function js(): string
    {
        Util::elog(__METHOD__);

        return '';
    }

    public function log(): string
    {
        Util::elog(__METHOD__);

        if ($this->ctx->in['l'])
        {
            [$lvl, $msg] = explode(':', $this->ctx->in['l']);
            $bgClass = $lvl === 'success' ? 'bg-success' : 'bg-danger';
            isset($_SESSION['l']) && $_SESSION['l'] = '';

            return '
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1500">
            <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header ' . $bgClass . ' text-white">
                    <strong class="me-auto">Notification</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">' . $msg . '</div>
            </div>
        </div>';
        }
        return '';
    }

    public function nav1(): string
    {
        Util::elog(__METHOD__);

        $o = '?o=' . $this->ctx->in['o'];

        return join('', array_map(function ($n) use ($o)
        {
            $url = is_string($n[1]) ? $n[1] : '';
            $c = $o === $url ? ' active' : '';
            $icon = isset($n[2]) ? '<i class="' . $n[2] . ' me-1"></i>' : '';
            return '
                        <li class="nav-item">
                            <a class="nav-link' . $c . '" href="' . $url . '"' . ($c ? ' aria-current="page"' : '') . '>' . $icon . $n[0] . '</a>
                        </li>';
        }, $this->ctx->nav));
    }

    public function head(): string
    {
        Util::elog(__METHOD__);

        return '
        <nav class="navbar navbar-expand-md fixed-top">
            <div class="container">
                <a class="navbar-brand" href="/">« ' . $this->ctx->out['head'] . '</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="toggleTheme(); return false;">
                                <i id="theme-icon" class="bi bi-sun-fill"></i>
                            </a>
                        </li>' . $this->ctx->out['nav1'] . '
                    </ul>
                </div>
            </div>
        </nav>';
    }

    public function main(): string
    {
        Util::elog(__METHOD__);

        return '

        <main class="container py-4">' . $this->ctx->out['main'] . '
        </main>';
    }

    public function foot(): string
    {
        Util::elog(__METHOD__);

        return '

        <footer class="text-center py-3 mt-auto">
            <div class="container">
                <p class="text-muted mb-0"><small>' . $this->ctx->out['foot'] . '</small></p>
            </div>
        </footer>';
    }

    public function create(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function read(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function update(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function delete(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function list(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }
}
