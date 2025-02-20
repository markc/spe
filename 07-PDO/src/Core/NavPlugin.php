<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250219
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\PDO\Core;

use function array_merge;
use function array_map;
use function basename;
use function dirname;
use function file_exists;
use function file_get_contents;
use function glob;
use function is_dir;
use function json_decode;
use function min;
use function str_replace;
use function usort;

class NavPlugin
{
    private string $pluginsDir;

    public function __construct(?string $baseDir = null)
    {
        Util::elog(__METHOD__);
        $this->pluginsDir = $baseDir ??= dirname(__DIR__) . '/Plugins';
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function scanPlugins(): array
    {
        Util::elog(__METHOD__);

        $navItems = [];
        $groupMinOrders = [];
        $directories = glob($this->pluginsDir . '/*') ?: [];

        foreach ($directories as $dir)
        {
            if (!is_dir($dir))
            {
                continue;
            }

            $meta = $this->getPluginMeta($dir);
            $pluginData = [
                $meta['name'],
                $meta['href'],
                $meta['icon'],
                $meta['ajax']
            ];

            if ($meta['group'])
            {
                $group = $meta['group'];
                // Track minimum order for each group
                $groupMinOrders[$group] = min($groupMinOrders[$group] ?? $meta['order'], $meta['order']);

                $groupFound = false;
                foreach ($navItems as &$item)
                {
                    if ($item[0] === $group)
                    {
                        $item[1][] = $pluginData;
                        $groupFound = true;
                        break;
                    }
                }
                unset($item);

                if (!$groupFound)
                {
                    $navItems[] = [
                        $group,
                        [$pluginData],
                        'bi bi-collection fw',
                        $meta['order']
                    ];
                }
            }
            else
            {
                $navItems[] = [$meta['name'], $meta['href'], $meta['icon'], $meta['ajax'], $meta['order']];
            }
        }

        // Sort items by order
        usort($navItems, function ($a, $b) use ($groupMinOrders)
        {
            $orderA = $groupMinOrders[$a[0]] ?? ($a[4] ?? 0);
            $orderB = $groupMinOrders[$b[0]] ?? ($b[4] ?? 0);
            return $orderA <=> $orderB;
        });

        $groups = [];
        $standaloneItems = [];

        foreach ($navItems as $item)
        {
            if (isset($item[1]) && \is_array($item[1]))
            {
                $items = array_map(function ($subItem) use ($item)
                {
                    $dir = $this->pluginsDir . '/' . str_replace('?o=', '', $subItem[1]);
                    $meta = $this->getPluginMeta($dir);
                    return [
                        'data' => $subItem,
                        'order' => $meta['order']
                    ];
                }, $item[1]);

                usort($items, fn($a, $b) => $a['order'] <=> $b['order']);

                $items = array_map(fn($item) => $item['data'], $items);

                $groups[] = [
                    $item[0],
                    $items,
                    $item[2] ?? 'bi bi-collection fw'
                ];
            }
            else
            {
                $standaloneItems[] = $item;
            }
        }

        return array_merge($groups, $standaloneItems);
    }

    /**
     * @param string $dir
     * @return array<string, mixed>
     */
    private function getPluginMeta(string $dir): array
    {
        Util::elog(__METHOD__);

        $metaFile = $dir . '/meta.json';
        if (file_exists($metaFile))
        {
            $meta = @json_decode(file_get_contents($metaFile), true);
            $meta ??= [];

            return [
                'name' => $meta['name'] ?? basename($dir),
                'icon' => $meta['icon'] ?? 'bi bi-box-seam fw',
                'href' => $meta['href'] ?? '?o=' . basename($dir),
                'ajax' => $meta['ajax'] ?? '',
                'order' => $meta['order'] ?? 999,
                'group' => $meta['group'] ?? null
            ];
        }
        return [
            'name' => basename($dir),
            'icon' => 'bi bi-box-seam fw',
            'href' => '?o=' . basename($dir),
            'ajax' => '',
            'order' => 999,
            'group' => null
        ];
    }
}
