# SPE::04 Themes

_Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)_

Adds the theme system with Model/View separation and multiple layout options.

## PHP 8.x Features Demonstrated

### PHP 8.2
- Readonly classes: `readonly class Init`

### PHP 8.3
- `#[\Override]` attribute on plugin methods

### PHP 8.4
- Asymmetric visibility in Ctx class

### PHP 8.5
- Pipe operator for nav rendering in themes

## Quick Start

```bash
cd 04-Themes
php -S localhost:8000
# Open http://localhost:8000
```

## What's New from 03-Plugins

- Model/View separation: `{Plugin}Model` returns data, `{Plugin}View` renders HTML
- Theme classes: `Simple`, `TopNav`, `SideBar`
- URL parameter `?t=ThemeName` for theme selection
- Method Template pattern: View -> Theme -> Default fallback

## Architecture

```
Ctx             - Global context with $ary for plugin data
Init            - Model dispatch, View/Theme rendering chain
Plugin          - Abstract base returning arrays
{Plugin}Model   - Data/logic layer (returns array)
{Plugin}View    - Presentation layer (returns string)
Theme           - Base theme with nav() helper
Simple/TopNav/SideBar - Concrete themes with html() method
```

## URL Parameters

- `?o=Home` - Plugin/object name
- `?m=list` - Method/action
- `?t=TopNav` - Theme name (Simple, TopNav, SideBar)
- `?x=json` - Output format

## Theme Layouts

- **Simple** - Centered container with inline nav
- **TopNav** - Fixed top navigation bar
- **SideBar** - Left sidebar with grouped navigation

## Key Pattern

```php
// Method resolution chain
$render = fn(?object $obj, string $method) =>
    ($obj && method_exists($obj, $method)) ? $obj->$method() : null;

$this->ctx->out['main'] = $render($view, $m)
    ?? $render($theme, $m)
    ?? $this->ctx->out['main'];
```
