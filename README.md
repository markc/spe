# Simple PHP Examples (SPE)

_Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)_

A progressive PHP 8.5 micro-framework tutorial in 9 chapters. Each chapter builds on the previous, demonstrating modern PHP features and design patterns. **No Bootstrap** - uses custom minimal CSS.

## Requirements

- PHP 8.5+ (for pipe operator `|>`)
- Composer (for chapters 05-09)

## Quick Start

```bash
git clone https://github.com/markc/spe
cd spe
composer install
php -S localhost:8000
```

Open http://localhost:8000 to see the chapter index.

For individual chapters with PSR-4 autoloading:
```bash
cd 09-Blog/public && php -S localhost:8080
```

## Chapters

| # | Name | Description | Key Feature |
|---|------|-------------|-------------|
| 01 | [Simple](01-Simple/README.md) | Single-file anonymous class | Pipe operator basics |
| 02 | [Styled](02-Styled/README.md) | Custom CSS, dark mode | Toast notifications |
| 03 | [Plugins](03-Plugins/README.md) | Plugin architecture | CRUDL pattern |
| 04 | [Themes](04-Themes/README.md) | Model/View separation | Multiple layouts |
| 05 | [Autoload](05-Autoload/README.md) | PSR-4 autoloading | Composer integration |
| 06 | [Session](06-Session/README.md) | Session management | State persistence |
| 07 | [PDO](07-PDO/README.md) | Database access | SQLite + QueryType enum |
| 08 | [Users](08-Users/README.md) | User management | Full CRUDL operations |
| 09 | [Blog](09-Blog/README.md) | Complete CMS | Auth, Blog, Docs, Categories |

## PHP 8.x Features

### PHP 8.1
- First-class callables: `array_keys(...)`, `http_build_query(...)`
- Enums: `enum QueryType { case All; case One; case Column; }`
- Readonly properties

### PHP 8.2
- Readonly classes: `readonly class Init`
- `true`, `false`, `null` as standalone types

### PHP 8.3
- Typed class constants: `private const string DEFAULT = 'home'`
- Typed array constants: `private const array OPTIONS = [...]`
- `#[\Override]` attribute

### PHP 8.4
- Asymmetric visibility: `public private(set) string $prop`
- `new` without parentheses

### PHP 8.5
- Pipe operator: `$value |> trim(...) |> strtolower(...)`
- Chained transformations with first-class callables

## Architecture

```
URL: ?o=Home&m=list&t=TopNav

o = Object/Plugin name
m = Method/Action (CRUDL: create, read, update, delete, list)
t = Theme (Simple, TopNav, SideBar)
```

### Request Flow

1. `index.php` creates `Init(new Ctx)`
2. `Init` processes URL parameters and checks auth
3. `{Plugin}Model->$method()` returns data array
4. `{Plugin}View->$method()` renders HTML (or falls back to Theme)
5. `Init->__toString()` outputs response

### Directory Structure (chapters 05-09)

```
XX-Chapter/
├── public/
│   └── index.php          # Entry point
└── src/
    ├── Core/              # Framework classes
    ├── Plugins/           # Feature plugins (Model + View)
    └── Themes/            # Layout themes
```

## Styling

**No Bootstrap!** Uses custom `spe.css` (~270 lines):
- CSS variables for light/dark theming
- `@media (prefers-color-scheme: dark)` automatic dark mode
- Manual toggle via localStorage
- Responsive layouts (TopNav, SideBar)
- Dropdown menus with fade animation
- Toast notifications
- Blog card grid and prose styling

## Key Patterns

```php
<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

// PHP 8.3 Typed constant
private const string DEFAULT = 'home';

// PHP 8.4 Asymmetric visibility
public private(set) string $page;

// PHP 8.5 Pipe operator for transformation chains
$value = $input
    |> trim(...)
    |> (fn($s) => filter_var($s, FILTER_SANITIZE_URL))
    |> (fn($v) => $v ?: self::DEFAULT);

// First-class callable with pipe
$nav = $pages
    |> array_keys(...)
    |> (fn($k) => array_map(fn($n) => "<a href=\"?m=$n\">$n</a>", $k))
    |> (fn($a) => implode(' | ', $a));

// PHP 8.3 Override attribute
#[\Override] public function list(): array {
    return ['head' => 'Home', 'main' => 'Welcome'];
}
```

## Shared Assets

All chapters 02-09 use shared CSS/JS from the project root:

- `/spe.css` - Complete styling with CSS variables
- `/spe.js` - Theme toggle, toast notifications, dropdown handling

Chapter 01-Simple has no external dependencies (inline minimal styles only).

## License

MIT License - See individual file headers for copyright notices.
