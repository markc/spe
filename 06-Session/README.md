# SPE::06 Session

_Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)_

Adds PHP session management for state persistence across requests.

## PHP 8.x Features Demonstrated

### PHP 8.2
- Readonly classes

### PHP 8.3
- Typed class constants
- `#[\Override]` attribute

### PHP 8.4
- Asymmetric visibility

### PHP 8.5
- Pipe operator for input processing

## Quick Start

```bash
composer install
cd 06-Session/public
php -S localhost:8000
# Open http://localhost:8000
```

## What's New from 05-Autoload

- Session handling via `session_start()`
- `Util::ses()` helper for session get/set
- Persistent state across page loads
- Theme preference saved in session

## Session Helper

```php
// Get session value with default
$theme = Util::ses('t', 'TopNav');

// Set session value
Util::ses('t', $newTheme, true);
```

## Namespace

`SPE\Session\{Core,Plugins,Themes}`
