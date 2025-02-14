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
        <title>[TopNav] ' . $doc . '</title>' . $css . '
    </head>
    <body class="d-flex flex-column min-vh-100">' . $head . $log . $main . $foot . $js . '
    </body>
</html>';
    }

    public function css(): string
    {
        Util::elog(__METHOD__);

        return '
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <script>
            function setTheme(theme) {
                const htmlElement = document.documentElement;
                htmlElement.setAttribute("data-bs-theme", theme);
                localStorage.setItem("theme", theme);
                updateThemeIcon(theme);
            }
            function toggleTheme() {
                const currentTheme = document.documentElement.getAttribute("data-bs-theme");
                setTheme(currentTheme === "dark" ? "light" : "dark");
            }
            function updateThemeIcon(theme) {
                const icon = document.getElementById("theme-icon");
                if (icon) {
                    icon.className = theme === "dark" ? "bi bi-moon-fill" : "bi bi-sun-fill";
                }
            }
            const storedTheme = localStorage.getItem("theme");
            if (storedTheme) {
                setTheme(storedTheme);
            } else {
                const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
                setTheme(prefersDark ? "dark" : "light");
            }
        </script>
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

    public function js(): string
    {
        Util::elog(__METHOD__);

        return '
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>';
    }
}
