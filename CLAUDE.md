# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SPE (Simple PHP Examples) is a progressive PHP 8.5 micro-framework tutorial in 9 chapters. Each chapter builds on the previous, demonstrating modern PHP features and design patterns. **No Bootstrap dependency** - uses custom minimal CSS.

## Requirements

- PHP 8.5+ (for pipe operator `|>`)
- Composer (for chapters 05-09)

## Development Commands

```bash
# Install dependencies
composer install

# Run from root (serves all chapters)
php -S localhost:8000

# Run specific chapter (05-09)
cd 09-Blog/public && php -S localhost:8080
```

## PHP 8.x Features Used

### PHP 8.1
- First-class callables: `array_keys(...)`, `http_build_query(...)`
- Enums: `enum QueryType { case All; case One; case Column; }`
- Readonly properties

### PHP 8.2
- Readonly classes: `readonly class Init`, `final readonly class PluginMeta`
- `true`, `false`, `null` as standalone types

### PHP 8.3
- Typed class constants: `private const string DEFAULT = 'home'`
- Typed array constants: `private const array OPTIONS = [...]`
- `#[\Override]` attribute on overridden methods

### PHP 8.4
- Asymmetric visibility: `public private(set) string $prop`
- `new` without parentheses

### PHP 8.5
- Pipe operator: `$value |> trim(...) |> strtolower(...)`
- Chained transformations with first-class callables

## Architecture

### Core Request Flow

1. `public/index.php` bootstraps with `echo new Init(new Ctx)`
2. `Init` parses URL parameters (`o`=plugin, `m`=method, `t`=theme)
3. `Init` checks route protection (auth/admin) via plugin `meta.json`
4. Model class (`{Plugin}Model`) executes the method, returns data array
5. View class (`{Plugin}View`) renders the data, or falls back to Theme class
6. `Init::__toString()` outputs based on request type

### URL Parameters

```
?o=Home    - Plugin/Object name
?m=list    - Method name (CRUDL: create, read, update, delete, list)
?t=TopNav  - Theme name (Simple, TopNav, SideBar)
?p=about   - Page slug shortcut (redirects to ?o=Pages&m=read&slug=about)
?id=1      - Record ID for CRUD operations
```

### Directory Structure (chapters 05-09)

```
XX-Chapter/
├── public/
│   └── index.php          # Entry point
└── src/
    ├── Core/
    │   ├── Ctx.php        # Context with nav building
    │   ├── Init.php       # Request dispatch and auth checking
    │   ├── Plugin.php     # Base plugin with CRUDL stubs
    │   ├── Theme.php      # Base theme with nav helpers
    │   ├── Db.php         # PDO wrapper with QueryType enum (07+)
    │   ├── PluginLoader.php   # Auto-discovery from meta.json (09)
    │   ├── PluginMeta.php     # Immutable plugin metadata (09)
    │   └── Util.php       # Helpers + Markdown parser
    ├── Plugins/
    │   └── {Name}/
    │       ├── {Name}Model.php  # Data/logic layer
    │       ├── {Name}View.php   # Presentation layer
    │       └── meta.json        # Plugin configuration (09)
    └── Themes/
        ├── Simple.php     # Minimal single-page
        ├── TopNav.php     # Fixed top navigation
        └── SideBar.php    # Left sidebar layout
```

### Ctx Class

Central state object:
- `$in`: Input parameters (o, m, t, id)
- `$out`: Output partials (doc, head, main, foot)
- `$ary`: Plugin return data
- `$buf`: Final HTML buffer
- `$navPages`: Pages from database (09)
- `$nav1/$nav2`: Navigation arrays
- `$loader`: PluginLoader instance (09)

### Theme Method Resolution

Init tries methods in order:
1. `{Plugin}View->$method()` - Plugin-specific view
2. `Theme->$method()` - Theme fallback
3. `Ctx::$out[$key]` - Default value

### Plugin meta.json (09-Blog)

```json
{
    "name": "Posts",
    "emoji": "📝",
    "order": 10,
    "group": "admin",
    "auth": true,
    "admin": false,
    "enabled": true
}
```

## Shared Assets

All chapters 02-09 use shared CSS/JS from the root:

- `/spe.css` (~270 lines) - CSS variables, dark mode, responsive layouts, dropdowns
- `/spe.js` - Theme toggle, toast notifications, dropdown handling

Chapter 01-Simple has no external dependencies (inline minimal styles only).

## Key Code Patterns

```php
<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

// PHP 8.3 Typed constants
private const string DEFAULT = 'home';
private const int PER_PAGE = 10;
private const array OPTIONS = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

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

// URL-preserving theme links
private function themeLink(string $theme): string {
    return $_GET
        |> (fn($p) => [...$p, 't' => $theme])
        |> http_build_query(...)
        |> (fn($q) => "?$q");
}

// PHP 8.3 Override attribute
#[\Override] public function list(): array {
    return ['head' => 'Home', 'main' => 'Welcome'];
}

// PHP 8.1 Enum
enum QueryType: string {
    case All = 'all';
    case One = 'one';
    case Column = 'column';
}

// PHP 8.2 Readonly class
final readonly class PluginMeta {
    public function __construct(
        public string $name,
        public string $emoji = '',
        public bool $auth = false,
        // ...
    ) {}
}

// HEREDOC for HTML
return <<<HTML
<!DOCTYPE html>
<html lang="en">
...
HTML;
```

## Chapter Progression

| # | Name | Key Addition |
|---|------|--------------|
| 01 | Simple | Single-file anonymous class, pipe operator |
| 02 | Styled | Custom CSS, dark mode, toast notifications |
| 03 | Plugins | Plugin architecture, CRUDL pattern |
| 04 | Themes | Model/View separation, multiple layouts |
| 05 | Autoload | PSR-4 autoloading via Composer |
| 06 | Session | PHP session management |
| 07 | PDO | Database access with PDO/SQLite, QueryType enum |
| 08 | Users | User management CRUDL |
| 09 | Blog | Full CMS: Auth, Blog, Pages, Categories, Docs |

## Namespacing

Each chapter (05-09) has its own PSR-4 namespace: `SPE\{Chapter}\` (e.g., `SPE\Blog\Core\Init`)

## Icons

Uses Unicode emoji instead of icon libraries:
- Home: 🏠
- About: 📋
- Contact: ✉️
- Blog: 📰
- Posts: 📝
- Pages: 📄
- Categories: 🏷️
- Users: 👥
- Docs: 📚
- Auth: 🔒
- Settings: ⚙️
- Theme: 🎨
- Logout: 🚪

## Databases (07-09)

### blog.db (SQLite)
- `posts` - Content (type: 'post'|'page'|'doc')
- `categories` - Taxonomy
- `post_categories` - Many-to-many junction

### users.db (SQLite)
- `users` - Authentication and profiles

## Custom Markdown Parser

`Util::md()` (~70 lines) provides GFM-compatible parsing:
- Headings (h1-h6)
- Bold, italic, strikethrough
- Links, images
- Fenced code blocks with language class
- Blockquotes
- Ordered/unordered lists
- Horizontal rules
- **GFM Tables** with column alignment

## License

MIT License - Copyright (C) 2015-2025 Mark Constable <mc@netserva.org>
