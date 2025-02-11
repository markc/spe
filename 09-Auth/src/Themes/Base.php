<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250212
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Auth\Themes;

use SPE\Auth\Core\Cfg;
use SPE\Auth\Core\Ctx;
use SPE\Auth\Core\Util;

abstract class Base
{
    protected Cfg $cfg;
    protected Ctx $ctx;

    public function __construct(Cfg $cfg, Ctx $ctx)
    {
        Util::elog(__METHOD__);

        $this->cfg = $cfg;
        $this->ctx = $ctx;
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        return $this->html();
    }

    public function css(): string
    {
        Util::elog(__METHOD__);

        return '
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            body { padding-top: 4.5rem; }
        </style>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>';
    }

    public function js(): string
    {
        Util::elog(__METHOD__);

        return '
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const toastElList = document.querySelectorAll(".toast");
                toastElList.forEach(function(toastEl) {
                    const toast = new bootstrap.Toast(toastEl, {
                        autohide: true,
                        delay: 3000
                    });
                    toast.show();
                });
            });
            function showToast(message, type) {
                const toastContainer = document.createElement("div");
                toastContainer.setAttribute("aria-live", "polite");
                toastContainer.setAttribute("aria-atomic", "true");
                toastContainer.style.position = "fixed";
                toastContainer.style.top = "20px";
                toastContainer.style.right = "20px";
                toastContainer.style.zIndex = "1050";

                toastContainer.innerHTML = 
                    \'<div class="toast align-items-center text-white bg-\' + type + \' border-0" role="alert" aria-live="assertive" aria-atomic="true">\' +
                        \'<div class="d-flex">\' +
                            \'<div class="toast-body">\' + message + \'</div>\' +
                            \'<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>\' +
                        \'</div>\' +
                    \'</div>\';

                document.body.appendChild(toastContainer);
                const toastElement = toastContainer.querySelector(".toast");
                const toast = new bootstrap.Toast(toastElement, {
                    autohide: true,
                    delay: 3000
                });
                toast.show();

                toastElement.addEventListener("hidden.bs.toast", () => {
                    toastContainer.remove();
                });
            }    
        </script>';
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

        $links = join('', array_map(function ($n) use ($o)
        {
            $url = is_string($n[1]) ? $n[1] : '';
            $c = $o === $url ? ' active' : '';
            $icon = isset($n[2]) ? '<i class="' . $n[2] . ' me-1"></i>' : '';
            return '
                        <li class="nav-item">
                            <a class="nav-link' . $c . '" href="' . $url . '"' . ($c ? ' aria-current="page"' : '') . '>' . $icon . $n[0] . '</a>
                        </li>';
        }, $this->ctx->nav));

        return '
        <nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
            <div class="container">
                <a class="navbar-brand" href="/">« ' . $this->ctx->out['head'] . '</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">' . $links . '
                    </ul>
                </div>
            </div>
        </nav>';
    }

    public function head(): string
    {
        Util::elog(__METHOD__);

        return $this->ctx->out['nav1'];
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

        <footer class="bg-light text-center py-3 mt-auto">
            <div class="container">
                <p class="text-muted mb-0"><small>' . $this->ctx->out['foot'] . '</small></p>
            </div>
        </footer>';
    }

    public function html(): string
    {
        Util::elog(__METHOD__) . ' ' . var_export($this->ctx->out, true);

        extract($this->ctx->out, EXTR_SKIP);
        return '<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Simple PHP Example with Plugins">
        <meta name="author" content="Mark Constable">
        <link rel="icon" href="favicon.ico">
        <title>Theme ' . $doc . '</title>' . $css . '
    </head>
    <body class="d-flex flex-column min-vh-100">' . $head . $log . $main . $foot . $js . '
    </body>
</html>';
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
