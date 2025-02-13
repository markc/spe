<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250213
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\BareBone\Core;

use SPE\BareBone\Core\Util;

class PluginNav
{
    private string $pluginsDir;
    private string $cacheFile;
    private int $cacheExpiry = 3600; // Cache expiry in seconds (1 hour)

    public function __construct(?string $baseDir = null)
    {
        Util::elog(__METHOD__);

        $this->pluginsDir = $baseDir ?? dirname(__DIR__) . '/Plugins';
        $this->cacheFile = dirname(__DIR__) . '/cache/plugins.json';
    }

    public function scanPlugins(): array
    {
        Util::elog(__METHOD__);

        // Check if cache exists and is valid
        if ($this->isCacheValid())
        {
            return $this->loadCache();
        }

        // If no valid cache exists, scan directories and create cache
        $navigation = $this->performScan();
        $this->saveCache($navigation);

        return $navigation;
    }

    private function isCacheValid(): bool
    {
        Util::elog(__METHOD__);

        if (!file_exists($this->cacheFile))
        {
            return false;
        }

        // Check if cache is expired
        $cacheTime = filemtime($this->cacheFile);
        if (time() - $cacheTime > $this->cacheExpiry)
        {
            return false;
        }

        // Check if any plugin directory or meta.json has been modified
        $directories = glob($this->pluginsDir . '/*');
        foreach ($directories as $dir)
        {
            if (!is_dir($dir)) continue;

            // Check directory modification time
            if (filemtime($dir) > $cacheTime)
            {
                return false;
            }

            // Check meta.json modification time
            $metaFile = $dir . '/meta.json';
            if (file_exists($metaFile) && filemtime($metaFile) > $cacheTime)
            {
                return false;
            }
        }

        return true;
    }

    private function loadCache(): array
    {
        Util::elog(__METHOD__);

        $cache = json_decode(file_get_contents($this->cacheFile), true);
        if (json_last_error() === JSON_ERROR_NONE)
        {
            return $cache;
        }
        // If cache is corrupted, perform fresh scan
        return $this->performScan();
    }

    private function saveCache(array $data): void
    {
        Util::elog(__METHOD__);

        $cacheDir = dirname($this->cacheFile);
        if (!is_dir($cacheDir))
        {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents($this->cacheFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function performScan(): array
    {
        Util::elog(__METHOD__);

        $navItems = [];
        $groupMinOrders = [];
        $directories = glob($this->pluginsDir . '/*');

        if ($directories === false)
        {
            return [];
        }

        foreach ($directories as $dir)
        {
            if (!is_dir($dir))
            {
                continue;
            }

            $meta = $this->getPluginMeta($dir);
            $pluginData = [
                $meta['name'],
                "?o=" . basename($dir),
                $meta['icon']
            ];

            if ($meta['group'])
            {
                // Track minimum order for each group
                if (!isset($groupMinOrders[$meta['group']]))
                {
                    $groupMinOrders[$meta['group']] = $meta['order'];
                }
                else
                {
                    $groupMinOrders[$meta['group']] = min($groupMinOrders[$meta['group']], $meta['order']);
                }

                // Initialize group if not exists
                $groupFound = false;
                foreach ($navItems as &$item)
                {
                    if ($item[0] === $meta['group'])
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
                        $meta['group'],
                        [$pluginData],
                        'bi bi-collection fw',
                        $meta['order']
                    ];
                }
            }
            else
            {
                $navItems[] = [$meta['name'], $pluginData[1], $pluginData[2], $meta['order']];
            }
        }

        // Sort items by order
        usort($navItems, function ($a, $b) use ($groupMinOrders)
        {
            $orderA = isset($groupMinOrders[$a[0]]) ? $groupMinOrders[$a[0]] : $a[3];
            $orderB = isset($groupMinOrders[$b[0]]) ? $groupMinOrders[$b[0]] : $b[3];
            return $orderA <=> $orderB;
        });

        // Clean up the final structure
        return array_map(function ($item)
        {
            array_pop($item); // Remove the order
            return $item;
        }, $navItems);
    }

    private function getPluginMeta(string $dir): array
    {
        Util::elog(__METHOD__);

        $metaFile = $dir . '/meta.json';
        if (file_exists($metaFile))
        {
            $meta = json_decode(file_get_contents($metaFile), true);
            return [
                'name' => $meta['name'] ?? basename($dir),
                'icon' => $meta['icon'] ?? 'bi bi-box-seam fw',
                'order' => $meta['order'] ?? 999,
                'group' => $meta['group'] ?? null
            ];
        }
        return [
            'name' => basename($dir),
            'icon' => 'bi bi-box-seam fw',
            'order' => 999,
            'group' => null
        ];
    }
}
