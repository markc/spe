<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250210
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Session\Themes;

use SPE\Session\Core\Cfg;
use SPE\Session\Core\Ctx;
use SPE\Session\Core\Util;
use SPE\Session\Core\PluginNav;

class TopNav extends Base
{
    public function __construct(Cfg $cfg, Ctx $ctx)
    {
        Util::elog(__METHOD__);

        parent::__construct($cfg, $ctx);
    }
    /*
    public function nav1(): string
    {
        Util::elog(__METHOD__);

        return $this->renderNav($this->ctx->nav);
    }

    private function renderNav(array $items): string
    {
        $html = '<nav class="navbar navbar-expand-lg navbar-light bg-light">';
        $html .= '<div class="container">';
        $html .= '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">';
        $html .= '<span class="navbar-toggler-icon"></span>';
        $html .= '</button>';
        $html .= '<div class="collapse navbar-collapse" id="navbarNav">';
        $html .= '<ul class="navbar-nav">';

        foreach ($items as $item)
        {
            if (is_array($item[1]))
            {
                // Group menu items
                $html .= '<li class="nav-item dropdown">';
                $html .= '<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">';
                $html .= '<i class="' . $item[2] . '"></i> ' . $item[0] . '</a>';
                $html .= '<ul class="dropdown-menu">';
                foreach ($item[1] as $subItem)
                {
                    $html .= '<li><a class="dropdown-item" href="' . $subItem[1] . '">';
                    $html .= '<i class="' . $subItem[2] . '"></i> ' . $subItem[0] . '</a></li>';
                }
                $html .= '</ul></li>';
            }
            else
            {
                // Single menu item
                $html .= '<li class="nav-item">';
                $html .= '<a class="nav-link" href="' . $item[1] . '">';
                $html .= '<i class="' . $item[2] . '"></i> ' . $item[0] . '</a></li>';
            }
        }

        $html .= '</ul></div></div></nav>';
        return $html;
    }
        */
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
