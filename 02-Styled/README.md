# SPE::02 Styled

_Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)_

Adds custom CSS styling via shared `spe.css` with automatic dark/light theme support.

## PHP 8.x Features Demonstrated

### PHP 8.1
- First-class callables: `array_keys(...)`

### PHP 8.3
- Typed class constants: `private const string DEFAULT = 'home'`

### PHP 8.4
- Asymmetric visibility: `public private(set) string $page`

### PHP 8.5
- Pipe operator `|>` for input sanitization and nav rendering

## Quick Start

```bash
cd 02-Styled
php -S localhost:8000
# Open http://localhost:8000
```

## What's New from 01-Simple

- External CSS via `/spe.css` (CSS variables, dark mode, responsive)
- External JS via `/spe.js` (theme toggle, toast notifications)
- Toast notification system for user feedback
- Contact form with mailto: integration
- Theme toggle button (light/dark)

## Architecture

Single anonymous class (~100 lines) with:
- Pages array: `[icon+name, title, content]`
- Automatic dark mode via `prefers-color-scheme`
- Manual theme override via localStorage

## Key Pattern

```php
// Pipe operator chain for nav with active state
$nav = $this->pages
    |> array_keys(...)
    |> (fn($keys) => array_map(
        fn($k) => sprintf(
            '<a href="?m=%s"%s>%s</a>',
            $k,
            $k === $this->page ? ' class="active"' : '',
            $this->pages[$k][0]
        ),
        $keys
    ))
    |> (fn($links) => implode(' ', $links));
```
