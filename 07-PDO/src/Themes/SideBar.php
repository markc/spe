<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\PDO\Themes;

use SPE\PDO\Core\{Ctx, Theme, Util, NavSideBar};

class SideBar extends Theme
{
    private NavSideBar $navSideBar;

    public function __construct(private Ctx $ctx)
    {
        Util::elog(__METHOD__);

        $this->navSideBar = new NavSideBar();
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
        <aside class="sidebar left bg-body-tertiary border-end shadow-sm" id="leftSidebar">
            ' . $nav1 . '
        </aside>
        <aside class="sidebar right bg-body-tertiary border-start shadow-sm" id="rightSidebar">
            ' . $nav2 . '
        </aside>

        <main class="main-content" id="main">
            <div class="container-fluid">
                <div class="content-section" id="ajaxhere">' . $main . '
                </div>
            </div>
        </main>
        ' . $foot . $js . '
    </body>
</html>
';
    }

    public function nav1(): string
    {
        Util::elog(__METHOD__);

        $return = $this->navSideBar->render($this->ctx->nav);
        Util::elog("return=$return");
        return $return;
    }

    public function nav2(): string
    {
        Util::elog(__METHOD__);

        return $this->navSideBar->render($this->ctx->nav2);
    }

    public function css(): string
    {
        Util::elog(__METHOD__);

        return '
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
            <link href="public/css/SideBar.css" rel="stylesheet">';
    }

    public function js(): string
    {
        Util::elog(__METHOD__);

        return '
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            <script src="public/js/ThemeSwitcher.js"></script>
            <script src="public/js/AjaxLoader.js"></script>
            <script src="public/js/ShowToast.js"></script>
            <script src="public/js/SideBar.js"></script>';
    }

    public function doc(): string
    {
        Util::elog(__METHOD__);

        return $this->ctx->out['head'];
    }

    public function head(): string
    {
        Util::elog(__METHOD__);

        return '
            <nav class="navbar navbar-height navbar-expand-md bg-body-tertiary fixed-top border-bottom shadow-sm">
                <div class="container-fluid d-flex align-items-center">
                    <button class="btn" id="leftSidebarToggle" type="button">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <a class="navbar-brand mx-auto" href="/">
                        « ' . $this->ctx->out['doc'] . '
                    </a>
                    <div class="d-flex align-items-center">
                        <a class="nav-link" href="#" onclick="toggleTheme(); return false;">
                            <i id="theme-icon" class="bi bi-sun-fill"></i>
                        </a>
                        <button class="btn" id="rightSidebarToggle" type="button">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                    </div>
                </div>
            </nav>';
    }

    public function main(): ?string
    {
        Util::elog(__METHOD__);

        return $this->ctx->out['main'];
    }

    public function foot(): string
    {
        Util::elog(__METHOD__);

        return '

        <footer class="bg-body-tertiary text-center py-3 mt-auto border-top shadow-sm">
            <div class="container">
                <p class="text-muted mb-0"><small>[SideBar] ' . $this->ctx->out['foot'] . '</small></p>
            </div>
        </footer>';
    }
}
