# SPE::09 Auth

_Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)_

## Overview

This is a modular PHP framework that demonstrates a plugin-based architecture with multiple themes. It includes:

- Plugin system with CRUDL (Create, Read, Update, Delete, List) operations
- Theme system with multiple layouts (Simple, TopNav, Sidebar)
- Configuration management
- Context handling
- Logging utilities

## Features

- **Plugin Architecture**: Easily extendable with new plugins
- **Theme System**: Multiple built-in themes with customizable layouts
- **Context Management**: Centralized state handling
- **Logging**: Debug logging capabilities
- **Responsive Design**: Bootstrap-based layouts
- **AJAX Support**: Built-in toast notifications

## Requirements

- PHP 8.4+
- Composer for autoloading
- Bootstrap 5.3+ (loaded via CDN)

## Installation

1. Clone the repository
2. Install dependencies:
```bash
composer install
```
3. Set up your web server to point to the `index.php` file

## Themes

The framework includes three built-in themes:

1. **Simple**: Basic layout with minimal styling
2. **TopNav**: Layout with a top navigation bar
3. **Sidebar**: Layout with a sidebar navigation

Themes can be selected using the `t` parameter:
```php
?t=Simple
?t=TopNav
?t=Sidebar
```

## Plugin System

Plugins are organized in the `Plugins` namespace and follow the CRUDL pattern. Example plugin structure:

```php
namespace SPE\Plugins\YourPlugin;

use SPE\Core\Plugin;
use SPE\Core\Util;

final class Model extends Plugin
{
    public function read(): void
    {
        Util::elog(__METHOD__);
        
        $this->ctx->ary = [
            'status' => 'Success',
            'content' => 'Your custom plugin content'
        ];
    }
}
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the AGPL-3.0 License. See the [LICENSE](LICENSE) file for details.

## Contact

- Author: Mark Constable
- Email: markc@renta.net
- Year: 2015-2025

## Example Usage

To use the framework:

1. Access the base URL
2. Choose a theme:
   - `/?t=Simple`
   - `/?t=TopNav`
   - `/?t=Sidebar`
3. Access plugins:
   - `/?o=Home`
   - `/?o=About`
   - `/?o=Contact`

The framework will automatically load the appropriate theme and plugin based on the request parameters.

