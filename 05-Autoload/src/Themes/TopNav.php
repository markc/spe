<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Autoload\Themes;

use SPE\Autoload\Core\{Ctx, Theme, Util, NavRenderer};

class TopNav extends Theme
{
    public function __construct(
        private Ctx $ctx
    )
    {
        Util::elog(__METHOD__);
        //parent::__construct($ctx);
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
        <meta name="description" content="Simple PHP Example">
        <meta name="author" content="Mark Constable">
        <link rel="icon" href="favicon.ico">
        <title>' . $doc . '</title>' . $css . '
    </head>
    <body class="d-flex flex-column min-vh-100">' . $head . '
        <main class="container py-5 mt-5" id="ajaxhere">' . $main . '
        </main>' . $foot . $js . '
    </body>
</html>
';
    }

    public function css(): string
    {
        Util::elog(__METHOD__);

        return '
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">';
    }

    public function js(): string
    {
        Util::elog(__METHOD__);

        return '
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            <script src="public/js/AjaxLoader.js"></script>
            <script src="public/js/TopNav.js"></script>';
    }

    public function log(): string
    {
        Util::elog(__METHOD__);
        //Util::elog(__METHOD__ . ' ' . var_export($this->ctx, true));

        if ($this->ctx->in['l'])
        {
            [$lvl, $msg] = explode(':', $this->ctx->in['l']);
            $bgClass = $lvl === 'success' ? 'bg-success' : 'bg-danger';
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

    public function head(): string
    {
        Util::elog(__METHOD__);

        $nav = new NavRenderer();
        return '
        <nav class="navbar navbar-expand-md bg-body-secondary fixed-top border-bottom shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="/">« ' . $this->ctx->out['head'] . '</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="toggleTheme(); return false;">
                                <i id="theme-icon" class="bi bi-sun-fill"></i>
                            </a>
                        </li>'
            . $nav->navRender($this->ctx->nav1)
            . $nav->navRender($this->ctx->nav2) . '
                    </ul>
                </div>
            </div>
        </nav>';
    }

    public function main(): string
    {
        Util::elog(__METHOD__);

        return $this->ctx->out['main'];
    }

    public function foot(): string
    {
        Util::elog(__METHOD__);

        return '

        <footer class="container-fluid text-center py-3 mt-auto bg-body-secondary border-top shadow-sm">
            <div class="container">
                <p class="text-muted mb-0"><small>' . $this->ctx->out['foot'] . '</small></p>
            </div>
        </footer>';
    }
}
