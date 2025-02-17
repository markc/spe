<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Session\Themes;

use SPE\Session\Core\{Ctx, Theme, Util};

class SideBar extends Theme
{
    public function __construct(private Ctx $ctx)
    {
        Util::elog(__METHOD__);
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
    <body class="d-flex flex-column min-vh-100">' . $head . $main . $foot . $js . '
    </body>
</html>
';
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

    public function main(): string
    {
        Util::elog(__METHOD__);

        $lhsNav = $this->renderPluginNav($this->ctx->nav1 ?? []);
        $rhsNav = $this->renderPluginNav($this->ctx->nav2 ?? []);

        return '
            <div class="sidebar left bg-body-tertiary" id="leftSidebar">
                ' . $lhsNav . '
            </div>
            <div class="sidebar right bg-body-tertiary" id="rightSidebar">
                ' . $rhsNav . '
            </div>
            <div class="main-content" id="main">
                <div class="container-fluid">
                    <main class="content-section" id="content-section">
                        ' . $this->ctx->out['main'] . '
                    </main>
                </div>
            </div>';
    }

    public function foot(): string
    {
        Util::elog(__METHOD__);

        return '

        <footer class="bg-body-tertiary text-center py-3 mt-auto">
            <div class="container">
                <p class="text-muted mb-0"><small>[SideBar] ' . $this->ctx->out['foot'] . '</small></p>
            </div>
        </footer>';
    }

    public function renderPluginNav(array $navData): string
    {
        if (!isset($navData[0]))
        {
            return '';
        }

        // Since plugins use a fixed structure [section_name, items_array, icon],
        // we treat it as a dropdown
        return $this->renderDropdown(
            [
                $navData[0],  // Section name (e.g., "Plugins")
                $navData[1],  // Array of plugin items
                $navData[2]   // Section icon
            ]
        );
    }

    private function renderDropdown(array $section): string
    {
        $currentPlugin = $this->ctx->in['o'] ?? 'Home';
        $icon = isset($section[2]) ? '<i class="' . $section[2] . ' fw"></i> ' : '';

        $submenuItems = array_map(
            function ($item) use ($currentPlugin)
            {
                $isActive = strtolower($currentPlugin) === strtolower($item[0]) ? ' active' : '';
                $itemIcon = isset($item[2]) ? '<i class="' . $item[2] . ' fw"></i> ' : '';

                return '
                        <li class="nav-item">
                            <a class="nav-link' . $isActive . ' fw" href="' . $item[1] . '">' .
                    $itemIcon . $item[0] .
                    '</a>
                        </li>';
            },
            $section[1]
        );

        return '
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#' . $section[0] . 'Submenu" 
                   role="button" aria-expanded="false" aria-controls="' . $section[0] . 'Submenu">' .
            $icon . $section[0] . ' <i class="bi bi-chevron-right chevron-icon fw ms-auto"></i>
                </a>
                <div class="collapse submenu" id="' . $section[0] . 'Submenu">
                    <ul class="nav flex-column">' .
            implode('', $submenuItems) . '
                    </ul>
                </div>
            </li>
        </ul>';
    }

    private function renderSingleNav(array $item): string
    {
        $currentPlugin = $this->ctx->in['o'] ?? 'Home';
        $isActive = $currentPlugin === $item[1] ? ' active' : '';
        $icon = isset($item[2]) ? '<i class="' . $item[2] . '"></i> ' : '';

        return '
        <ul class="nav flex-column">
            <li class="nav-item' . $isActive . '">
                <a class="nav-link" href="' . $item[1] . '">' . $icon . $item[0] . '</a>
            </li>
        </ul>';
    }
}
