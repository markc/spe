<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Autoload\Core;

class NavRenderer
{
    public function navRender(array $nav): string
    {
        return match (true)
        {
            isset($nav[0][0]) && is_array($nav[0]) => implode('', array_map(fn($item) => $this->renderNavItem($item), $nav)),
            is_array($nav[1] ?? null) => $this->renderDropdown($nav),
            default => $this->renderNavItem($nav),
        };
    }

    private function renderDropdown(array $nav): string
    {
        $nid = 'nav' . md5(serialize($nav));
        $icon = $nav[2] ?? '';

        return '
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="' . $nid . 'Dropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="' . $icon . '"></i> ' . $nav[0] . '
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="' . $nid . 'Dropdown">' . $this->renderDropdownItems($nav[1]) . '
                            </ul>
                        </li>';
    }

    private function renderDropdownItems(array $items): string
    {
        return implode('', array_map(fn($item) => '
                                <li>
                                    <a class="dropdown-item ' . $item[3] . '" href="' . $item[1] . '">
                                        <i class="' . $item[2] . '"></i> ' . $item[0] . '
                                    </a>
                                </li>', $items));
    }

    private function renderNavItem(array $nav): string
    {
        return '
                                <li class="nav-item">
                                    <a class="nav-link ' . $nav[3] . '" href="' . $nav[1] . '">
                                        <i class="' . $nav[2] . '"></i> ' . $nav[0] . '
                                    </a>
                                </li>';
    }
}
