<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250219
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\PDO\Core;

class NavTopNav
{
    public function render(array $nav): string
    {
        Util::elog(__METHOD__);

        $html = '';

        // Sort standalone items by order while keeping groups at their positions
        $groups = [];
        $standaloneItems = [];

        // Separate groups and standalone items
        foreach ($nav as $item)
        {
            if (isset($item[0]) && is_string($item[0]) && isset($item[1]) && is_array($item[1]))
            {
                $groups[] = $item;
            }
            else
            {
                $standaloneItems[] = $item;
            }
        }

        // Sort standalone items by order (index 4)
        usort($standaloneItems, function ($a, $b)
        {
            $orderA = $a[4] ?? 999;
            $orderB = $b[4] ?? 999;
            return $orderA <=> $orderB;
        });

        // Process all items in the sorted order
        foreach (array_merge($standaloneItems, $groups) as $item)
        {
            // If this is a group (has a title and items array)
            if (isset($item[0]) && is_string($item[0]) && isset($item[1]) && is_array($item[1]) && isset($item[1][0]) && is_array($item[1][0]))
            {
                // This is a dropdown menu: [title, items_array, icon]
                $dropdownId = 'nav' . md5((string)$item[0]) . 'Dropdown';
                // Extract group data with type checking
                $groupName = (string)$item[0]; // Already verified as string in the if condition
                $groupItems = $item[1]; // Already verified as array in the if condition
                $groupIcon = isset($item[2]) ? (is_array($item[2]) ? '' : (string)$item[2]) : '';

                $html .= '
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="' . $dropdownId . '" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="' . $groupIcon . '"></i> ' . $groupName . '
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="' . $dropdownId . '">';

                foreach ($groupItems as $subItem)
                {
                    // Extract item data with type checking
                    $itemName = is_array($subItem[0]) ? '' : (string)$subItem[0];
                    $itemHref = is_array($subItem[1]) ? '#' : (string)$subItem[1];
                    $itemIcon = is_array($subItem[2]) ? '' : (string)$subItem[2];
                    $itemClass = isset($subItem[3]) ? (is_array($subItem[3]) ? '' : (string)$subItem[3]) : '';

                    $html .= '
                                <li>
                                    <a class="dropdown-item ' . $itemClass . '" href="' . $itemHref . '">
                                        <i class="' . $itemIcon . '"></i> ' . $itemName . '
                                    </a>
                                </li>';
                }

                $html .= '
                            </ul>
                        </li>';
            }
            // If this is a standalone item
            else if (is_array($item) && count($item) >= 3)
            {
                // Ensure this is a valid standalone item
                if (isset($item[0]) && isset($item[1]) && isset($item[2]))
                {
                    // Extract item data with type checking
                    $itemName = is_array($item[0]) ? '' : (string)$item[0];
                    $itemHref = is_array($item[1]) ? '#' : (string)$item[1];
                    $itemIcon = is_array($item[2]) ? '' : (string)$item[2];
                    $itemClass = isset($item[3]) ? (is_array($item[3]) ? '' : (string)$item[3]) : '';

                    $html .= '
                        <li class="nav-item">
                            <a class="nav-link ' . $itemClass . '" href="' . $itemHref . '">
                                <i class="' . $itemIcon . '"></i> ' . $itemName . '
                            </a>
                        </li>';
                }
            }
        }

        return $html;
    }
}
