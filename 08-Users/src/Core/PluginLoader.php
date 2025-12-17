<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Users\Core;

/**
 * Auto-discovers plugins and themes from meta.json files
 */
final readonly class PluginLoader {
    private const string PLUGINS_DIR = __DIR__ . '/../Plugins';
    private const string THEMES_DIR = __DIR__ . '/../Themes';

    /** @var array<string, PluginMeta> */
    public array $plugins;

    /** @var array<string, PluginMeta> */
    public array $themes;

    public function __construct() {
        $this->plugins = $this->scanPlugins();
        $this->themes = $this->scanThemes();
    }

    /** Scan Plugins directory for meta.json files */
    private function scanPlugins(): array {
        $files = glob(self::PLUGINS_DIR . '/*/meta.json') ?: [];
        $metas = array_map(fn(string $path) => PluginMeta::fromFile($path), $files);
        $enabled = array_filter($metas, fn(?PluginMeta $m) => $m?->enabled ?? false);
        return $this->keyByName($this->sortByOrder($enabled));
    }

    /** Scan Themes directory for *.meta.json files */
    private function scanThemes(): array {
        $files = glob(self::THEMES_DIR . '/*.meta.json') ?: [];
        $metas = array_map(fn(string $path) => PluginMeta::fromFile($path), $files);
        $enabled = array_filter($metas, fn(?PluginMeta $m) => $m?->enabled ?? false);
        return $this->keyByName($this->sortByOrder($enabled));
    }

    /** Sort array of PluginMeta by order property */
    private function sortByOrder(array $items): array {
        usort($items, fn(PluginMeta $a, PluginMeta $b) => $a->order <=> $b->order);
        return $items;
    }

    /** Key array by plugin name */
    private function keyByName(array $items): array {
        $result = [];
        foreach ($items as $item) {
            $result[$item->name] = $item;
        }
        return $result;
    }

    /** Get plugins filtered by group */
    public function byGroup(string $group): array {
        return array_filter($this->plugins, fn(PluginMeta $p) => $p->group === $group);
    }

    /** Get plugins requiring authentication */
    public function authRequired(): array {
        return array_filter($this->plugins, fn(PluginMeta $p) => $p->auth);
    }

    /** Get admin-only plugins */
    public function adminOnly(): array {
        return array_filter($this->plugins, fn(PluginMeta $p) => $p->admin);
    }

    /** Get public plugins (no auth required) */
    public function publicPlugins(): array {
        return array_filter($this->plugins, fn(PluginMeta $p) => !$p->auth && !$p->admin);
    }

    /** Build nav1 array (plugins) for backwards compatibility */
    public function buildNav1(?string $group = null): array {
        $plugins = $group ? $this->byGroup($group) : $this->plugins;
        return array_values(array_map(fn(PluginMeta $p) => $p->toNavItem(), $plugins));
    }

    /** Build nav2 array (themes) for backwards compatibility */
    public function buildNav2(): array {
        return array_values(array_map(fn(PluginMeta $p) => $p->toNavItem(), $this->themes));
    }

    /** Check if a plugin exists and is enabled */
    public function has(string $name): bool {
        return isset($this->plugins[$name]);
    }

    /** Get a specific plugin's metadata */
    public function get(string $name): ?PluginMeta {
        return $this->plugins[$name] ?? null;
    }

    /** Check if a theme exists */
    public function hasTheme(string $name): bool {
        return isset($this->themes[$name]);
    }

    /** Get all unique groups */
    public function groups(): array {
        return array_values(array_unique(array_map(fn(PluginMeta $p) => $p->group, $this->plugins)));
    }
}
