<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Htmx\Core;

/**
 * Immutable value object representing plugin metadata
 * Uses PHP 8.4 property hooks for validation
 */
final readonly class PluginMeta
{
    public function __construct(
        public string $name,
        public string $description = '',
        public string $version = '1.0.0',
        public string $icon = '',
        public string $emoji = '',
        public string $href = '',
        public string $method = 'list',
        public bool $ajax = true,
        public int $order = 100,
        public string $group = 'main',
        public bool $auth = false,
        public bool $admin = false,
        public bool $enabled = true,
        public string $path = '',
    ) {}

    /** Create from JSON file */
    public static function fromFile(string $path): ?self
    {
        if (!file_exists($path))
            return null;

        $data = json_decode(file_get_contents($path), true) ?? [];
        $data['path'] = dirname($path);

        // Handle legacy "ajax": "ajax-link" format
        if (isset($data['ajax']) && is_string($data['ajax'])) {
            $data['ajax'] = $data['ajax'] === 'ajax-link';
        }

        return new self(...$data);
    }

    /** Create from array */
    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }

    /** Navigation label with emoji */
    public function label(): string
    {
        return trim("{$this->emoji} {$this->name}");
    }

    /** Full href with method if not default */
    public function url(): string
    {
        return $this->href ?: "?o={$this->name}" . ($this->method !== 'list' ? "&m={$this->method}" : '');
    }

    /** CSS class for AJAX links */
    public function linkClass(): string
    {
        return $this->ajax ? 'ajax-link' : '';
    }

    /** Convert to nav array format [label, name] for backwards compatibility */
    public function toNavItem(): array
    {
        return [$this->label(), $this->name];
    }
}
