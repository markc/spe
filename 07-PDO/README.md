# SPE::07 PDO

_Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)_

Adds database access layer using PDO with SQLite and introduces the QueryType enum.

## PHP 8.x Features Demonstrated

### PHP 8.1
- Enums: `enum QueryType { case All; case One; case Column; }`
- First-class callables in nav rendering

### PHP 8.2
- Readonly classes where applicable

### PHP 8.3
- Typed class constants: `private const string NS = 'SPE\\PDO\\'`
- `#[\Override]` attribute on plugin methods

### PHP 8.4
- Asymmetric visibility in Ctx class
- `new` without parentheses

### PHP 8.5
- Pipe operator for data transformation

## Quick Start

```bash
composer install
cd 07-PDO/public
php -S localhost:8000
# Open http://localhost:8000
```

## What's New from 06-Session

1. **Db.php**: PDO wrapper class with CRUD methods
2. **QueryType Enum**: Type-safe query result handling
3. **Blog Plugin**: Simple blog with SQLite storage
4. **blog.db**: SQLite database for posts

## Database Layer

```php
enum QueryType: string {
    case All = 'all';      // fetchAll() - array of rows
    case One = 'one';      // fetch() - single row
    case Column = 'column'; // fetchColumn() - single value
}

// Usage
$posts = $db->read('posts', '*', 'type = :type', ['type' => 'post'], QueryType::All);
$post = $db->read('posts', '*', 'id = :id', ['id' => 1], QueryType::One);
$count = $db->read('posts', 'COUNT(*)', '', [], QueryType::Column);
```

## Architecture

```
07-PDO/
├── public/
│   └── index.php
└── src/
    ├── Core/
    │   ├── Ctx.php
    │   ├── Db.php         # NEW: PDO wrapper
    │   ├── Init.php
    │   ├── Plugin.php
    │   ├── Theme.php
    │   └── Util.php
    ├── Plugins/
    │   ├── About/
    │   ├── Blog/          # NEW: Blog with database
    │   │   ├── BlogModel.php
    │   │   ├── BlogView.php
    │   │   └── blog.db    # SQLite database
    │   ├── Contact/
    │   └── Home/
    └── Themes/
        ├── Simple.php
        ├── TopNav.php
        └── SideBar.php
```

## License

MIT License
