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
    <body class="d-flex flex-column min-vh-100">' . $head . '
        <div class="sidebar left bg-body-tertiary" id="leftSidebar">
            ' . $nav1 . '
        </div>
        <div class="sidebar right bg-body-tertiary" id="rightSidebar">
            ' . $nav2 . '
        </div>
        <div class="main-content" id="main">
            <div class="container-fluid">
                <main class="content-section" id="ajaxhere">' . $main . '
                </main>
            </div>
        </div>' . $foot . $js . '
    </body>
</html>
';
    }

    public function nav1(): string
    {
        Util::elog(__METHOD__);

        return $this->renderPluginNav($this->ctx->nav1 ?? []);
    }

    public function nav2(): string
    {
        Util::elog(__METHOD__);

        return $this->renderPluginNav($this->ctx->nav2 ?? []);
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

    public function main(): string
    {
        Util::elog(__METHOD__);

        return $this->ctx->out['main'];

        /*
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
    */
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

        return $this->renderDropdown($navData);
    }

    private function renderDropdown(array $section): string
    {
        $sectionName = $section[0];
        $items = $section[1];
        $iconClass = $section[2] ?? ''; //Section Icon

        //Unique ID generation.
        $submenuId = str_replace(' ', '', $sectionName) . 'Submenu';

        $submenuItems = array_map(function ($item)
        {
            $name = $item[0];
            $url = $item[1];
            $itemIconClass = $item[2] ?? '';
            $linkClass = $item[3] ?? '';  // Use the provided class or an empty string
            $ajaxClass = $item[4] ?? '';
            $itemIcon = !empty($itemIconClass) ? '<i class="' . htmlspecialchars($itemIconClass) . ' fw"></i> ' : '';

            $xmain = $ajaxClass ? '&x=main' : '';

            return sprintf(
                '<li class="nav-item">
                    <a class="nav-link %s fw %s" href="%s%s">%s%s</a>
                </li>',
                htmlspecialchars($linkClass),
                htmlspecialchars($ajaxClass),
                htmlspecialchars($url),
                htmlspecialchars($xmain),
                $itemIcon,
                htmlspecialchars($name),
            );
        }, $items);
        //If the sub menu is empty.


        $html = sprintf(
            '<ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link no-underline" data-bs-toggle="collapse" href="#%s" role="button" aria-expanded="false" aria-controls="%s">
                        <i class="%s fw"></i> %s <i class="bi bi-chevron-right chevron-icon fw ms-auto"></i>
                    </a>
                    <div class="collapse submenu" id="%s">
                        <ul class="nav flex-column">
                            %s
                        </ul>
                    </div>
                </li>
            </ul>',
            htmlspecialchars($submenuId),
            htmlspecialchars($submenuId),
            htmlspecialchars($iconClass),
            htmlspecialchars($sectionName),
            htmlspecialchars($submenuId),
            implode('', $submenuItems)
        );

        return $html;
    }
}
