# SPE::05 Autoload

_Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)_

Introduces PSR-4 autoloading via Composer with proper namespace structure.

## PHP 8.x Features Demonstrated

### PHP 8.2
- Readonly classes: `readonly class Init`

### PHP 8.3
- Typed class constants: `private const string NS = 'SPE\\Autoload\\'`
- `#[\Override]` attribute on plugin methods

### PHP 8.4
- Asymmetric visibility in Ctx class

### PHP 8.5
- Pipe operator for input processing and nav rendering

## Quick Start

```bash
composer install
cd 05-Autoload/public
php -S localhost:8000
# Open http://localhost:8000
```

## What's New from 04-Themes

- PSR-4 autoloading via Composer
- Proper namespace structure: `SPE\Autoload\{Core,Plugins,Themes}`
- Separate files for each class
- `public/` directory as web root

## Directory Structure

```
05-Autoload/
├── public/
│   └── index.php          # Entry point
└── src/
    ├── Core/
    │   ├── Ctx.php        # Global context
    │   ├── Init.php       # Bootstrap
    │   ├── Plugin.php     # Base plugin
    │   ├── Theme.php      # Base theme
    │   └── Util.php       # Utilities
    ├── Plugins/
    │   ├── Home/
    │   │   ├── HomeModel.php
    │   │   └── HomeView.php
    │   ├── About/
    │   └── Contact/
    └── Themes/
        ├── Simple.php
        ├── TopNav.php
        └── SideBar.php
```

## Namespace Convention

```php
namespace SPE\Autoload\Core;
namespace SPE\Autoload\Plugins\Home;
namespace SPE\Autoload\Themes;
```

## composer.json

```json
{
    "autoload": {
        "psr-4": {
            "SPE\\Autoload\\": "05-Autoload/src/"
        }
    }
}
```
