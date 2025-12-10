# SPE::01 Simple

_Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)_

The simplest possible PHP micro-framework in a single file using an anonymous class.

## PHP 8.x Features Demonstrated

### PHP 8.1
- First-class callables: `array_keys(...)`
- Arrow functions for concise mapping

### PHP 8.3
- Typed class constants: `private const string DEFAULT = 'home'`

### PHP 8.4
- Asymmetric visibility: `public private(set) string $page`
- Constructor property promotion with visibility modifiers

### PHP 8.5
- Pipe operator `|>` for functional data transformation chains

## Quick Start

```bash
cd 01-Simple
php -S localhost:8000
# Open http://localhost:8000
```

## Architecture

Single anonymous class with:
- `__construct()` - Process URL parameter, select page
- `__toString()` - Return complete HTML document
- Pages array with content for home/about/contact

## URL Routing

- `?m=home` - Home page (default)
- `?m=about` - About page
- `?m=contact` - Contact page

## Key Patterns

```php
// Pipe operator for input sanitization
$this->page = ($_REQUEST['m'] ?? '')
    |> trim(...)
    |> (fn($s) => filter_var($s, FILTER_SANITIZE_URL))
    |> (fn($p) => $p && isset($this->pages[$p]) ? $p : self::DEFAULT);

// First-class callable with pipe for nav generation
$nav = $this->pages
    |> array_keys(...)
    |> (fn($k) => array_map(fn($n) => "<a href=\"?m=$n\">$n</a>", $k))
    |> (fn($a) => implode(' | ', $a));
```

## No External Dependencies

- No CSS framework (uses browser defaults + `color-scheme: light dark`)
- No JavaScript
- No Composer
- Single 80-line PHP file
