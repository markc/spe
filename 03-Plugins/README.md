# SPE::03 Plugins

_Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)_

Introduces the plugin architecture with separate classes for each page and CRUDL methods.

## PHP 8.x Features Demonstrated

### PHP 8.2
- Readonly classes: `readonly class Init`

### PHP 8.3
- `#[\Override]` attribute on plugin methods

### PHP 8.4
- Asymmetric visibility in Ctx class
- `new` without parentheses: `new Ctx`

### PHP 8.5
- Pipe operator for input processing and JSON output

## Quick Start

```bash
cd 03-Plugins
php -S localhost:8000
# Open http://localhost:8000
```

## What's New from 02-Styled

- Separate classes: `Ctx`, `Init`, `Plugin`, `Home`, `About`, `Contact`
- CRUDL pattern (Create, Read, Update, Delete, List)
- URL parameter `?o=PluginName` for object/plugin selection
- URL parameter `?m=method` for action selection
- JSON API via `?x=json`

## Architecture

```
Ctx         - Global context (input, output, navigation)
Init        - Bootstrap, dispatch to plugin, render output
Plugin      - Abstract base with CRUDL stubs
Home/About/Contact - Concrete plugins extending Plugin
```

## URL Parameters

- `?o=Home` - Plugin/object name (default: Home)
- `?m=list` - Method/action (default: list)
- `?x=json` - Output format (empty=html, json)

## Key Pattern

```php
// Match expression for plugin dispatch
$this->ctx->out['main'] = match (true) {
    !class_exists($o) => 'Error: plugin not found!',
    !method_exists($o, $m) => 'Error: method not found!',
    default => (new $o($this->ctx))->$m()
};
```
