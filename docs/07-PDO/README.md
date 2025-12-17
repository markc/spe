# SPE::07 PDO

_Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)_

An ultra-compact SQLite-powered blog application in just **326 lines across 5 files**. Demonstrates the PHP 8.5 pipe operator, dynamic navigation from database, and switchable themes with a clean MVC architecture.

## Architecture

```
07-PDO/
├── public/index.php     (7 lines)   Entry point
└── src/
    ├── App.php          (44 lines)  Routing, context, navigation
    ├── Model.php        (66 lines)  Blog CRUD operations
    ├── View.php         (105 lines) Blog views and forms
    └── Theme.php        (104 lines) 3 themes with shared HTML wrapper
```

**Total: 326 lines** - a complete blog CMS with database, themes, markdown, and search.

## PHP 8.5+ Features Demonstrated

| Feature | Example |
|---------|---------|
| Pipe operator | `$_REQUEST[$k] ?? $v \|> trim(...) \|> htmlspecialchars(...)` |
| First-class callables | `trim(...)`, `strtolower(...)` |
| Match expressions | `match($this->in['x']) { 'json' => ..., default => ... }` |
| Arrow functions | `fn($r) => [trim($r['icon'] . ' ' . $r['title']), ucfirst($r['slug'])]` |
| Constructor promotion | `public function __construct(private App $c) {}` |
| Named arguments | `QueryType::One`, `QueryType::All` |
| Typed properties | `public string $buf = ''`, `public array $a = []` |
| Null coalescing | `$_REQUEST[$k] ?? $v` |

## Quick Start

```bash
# From project root
composer install
cd 07-PDO/public
php -S localhost:8080

# Open http://localhost:8080
```

SQLite database is auto-created on first run in `app/sqlite/blog.db`.

## URL Parameters

| Param | Purpose | Values |
|-------|---------|--------|
| `o` | Page/Object | `Home`, `About`, `Contact`, `Blog` |
| `m` | Method | `list`, `read`, `create`, `update`, `delete` |
| `t` | Theme | `Simple`, `TopNav`, `SideBar` |
| `id` | Record ID | Integer |
| `page` | Pagination | Integer |
| `edit` | Admin mode | (presence enables) |
| `q` | Search query | String |
| `x` | Output format | `json` for API |

### Example URLs

```
?o=Home                    # Home page
?o=Blog                    # Blog list
?o=Blog&m=read&id=1        # Read post
?o=Blog&edit               # Admin list
?o=Blog&m=create           # Create form
?o=Blog&t=TopNav           # Switch theme
?o=Blog&x=json             # JSON API
```

## Key Components

### App.php - The Core

The entire application routing in 44 lines:

```php
$this->in = array_combine(array_keys($i), array_map(fn($k, $v) =>
    ($_REQUEST[$k] ?? $v) |> trim(...) |> htmlspecialchars(...), array_keys($i), $i));
```

- Processes URL parameters with pipe operator
- Builds navigation from database pages
- Routes to Model/View based on `?o=` and `?m=`
- Supports JSON API output via `?x=json`

### Model.php - Database Operations

CRUD operations using the shared `Db` class:

```php
// List with pagination and search
$total = $this->db->read('posts', 'COUNT(*)', $where, $p, QueryType::Col);
$items = $this->db->read('posts', '*', "$where ORDER BY updated DESC LIMIT :l OFFSET :o", [...]);

// Create with slug auto-generation
$this->db->create('posts', ['title' => $t, 'slug' => $this->slug($t), ...]);
```

### View.php - Templates

Generates HTML for all blog operations:
- `list()` - Public blog or admin table
- `read()` - Single post view
- `page()` - Static page view
- `create()`/`update()` - Form with icon selector
- `form()` - Shared form builder

### Theme.php - Layouts

Three themes sharing a common HTML wrapper:

```php
private function html(string $theme, string $body): string {
    $doc = $this->c->out['doc'];
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>...</head>
<body>
$body
    <script src="/spe.js"></script>
</body>
</html>
HTML;
}
```

**Themes:**
- `Simple` - Basic centered layout with card-styled nav
- `TopNav` - Fixed top navigation, centered links
- `SideBar` - Collapsible sidebar navigation

## Features

### Dynamic Navigation

Navigation is built from database pages:

```php
$this->n1 = array_map(fn($r) => [trim(($r['icon'] ?? '') . ' ' . $r['title']), ucfirst($r['slug'])],
    $db->read('posts', 'id,title,slug,icon', "type='page' ORDER BY id", [], QueryType::All));
```

### Markdown Rendering

Full GFM markdown support via `Util::md()`:
- Headings, bold, italic, strikethrough
- Links, images, code blocks
- Blockquotes, lists, tables
- Syntax highlighting classes

### Emoji Icon Selector

26 emoji icons for posts/pages:

```php
private const array ICO = ['' => 'None', '🏠' => 'Home', '📋' => 'About',
    '✉️' => 'Contact', '📰' => 'Blog', '📝' => 'Post', '📄' => 'Page', ...];
```

### Theme Dropdown

Themes accessible via dropdown menu in Simple/TopNav:

```php
private function dd(): string {
    $links = $this->c->n2 |> (fn($a) => array_map(fn($n) =>
        sprintf('<a href="?o=%s&t=%s">%s</a>', $o, $n[1], $n[0]), $a))
        |> (fn($l) => implode('', $l));
    return "<div class=dropdown><span class=dropdown-toggle>🎨 Themes</span>...";
}
```

## Shared Libraries (app/)

Located in `app/lib/` and shared across chapters 07-10:

| File | Purpose |
|------|---------|
| `Db.php` | PDO wrapper with CRUD methods |
| `QueryType.php` | Enum for fetch modes (All, One, Col) |
| `Util.php` | HTML escaping, markdown, flash messages |
| `Env.php` | Environment variable loading |

### Db Class Usage

```php
use SPE\App\{Db, QueryType};

$db = new Db('blog');

// Create
$db->create('posts', ['title' => 'New Post', 'content' => '...']);

// Read
$posts = $db->read('posts', '*', 'type=:t', ['t' => 'post'], QueryType::All);
$post = $db->read('posts', '*', 'id=:id', ['id' => 1], QueryType::One);
$count = $db->read('posts', 'COUNT(*)', '', [], QueryType::Col);

// Update
$db->update('posts', ['title' => 'Updated'], 'id=:id', ['id' => 1]);

// Delete
$db->delete('posts', 'id=:id', ['id' => 1]);
```

## CSS Utilities

The chapter uses utility classes from shared `spe.css`:

| Class | Purpose |
|-------|---------|
| `.card`, `.mt-4` | Card styling with margin |
| `.flex`, `.gap-sm` | Flexbox layouts |
| `.table`, `.th`, `.td` | Table styling |
| `.btn`, `.btn-muted` | Button variants |
| `.nav-card` | Card-styled navigation |

## What's Different from Previous Chapters

| Aspect | 06-Session | 07-PDO |
|--------|------------|--------|
| Data storage | Session/arrays | SQLite database |
| Architecture | Plugin system | Ultra-compact 5 files |
| Line count | ~500+ lines | 326 lines |
| Navigation | Static | Database-driven |
| Themes | Separate files | Combined with wrapper |

## Design Philosophy

This chapter prioritizes:

1. **Compactness** - Minimal code, maximum functionality
2. **Readability** - Clean heredocs for HTML, short but clear variable names
3. **Modern PHP** - Heavy use of pipe operator, arrow functions, match
4. **DRY** - Shared `html()` wrapper, reusable form builder
5. **Pragmatism** - No unnecessary abstractions

## License

MIT License
