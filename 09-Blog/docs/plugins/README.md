# Creating Plugins

Plugins are the primary way to add functionality to SPE. Each plugin consists of a Model, View, and metadata file.

## Plugin Structure

```
src/Plugins/{Name}/
├── {Name}Model.php   # Business logic
├── {Name}View.php    # HTML rendering
└── meta.json         # Plugin metadata
```

## Quick Start

### 1. Create Plugin Directory

```bash
mkdir -p src/Plugins/MyPlugin
```

### 2. Create meta.json

```json
{
    "name": "MyPlugin",
    "description": "A custom plugin",
    "emoji": "🔌",
    "order": 50,
    "group": "main",
    "auth": false,
    "admin": false,
    "enabled": true
}
```

### 3. Create Model

```php
<?php declare(strict_types=1);
namespace SPE\Blog\Plugins\MyPlugin;

use SPE\Blog\Core\{Ctx, Plugin};

final class MyPluginModel extends Plugin {
    #[\Override]
    public function list(): array {
        return [
            'head' => 'MyPlugin',
            'main' => 'Hello from MyPlugin!'
        ];
    }
}
```

### 4. Create View (Optional)

```php
<?php declare(strict_types=1);
namespace SPE\Blog\Plugins\MyPlugin;

use SPE\Blog\Core\Ctx;

final class MyPluginView {
    public function __construct(private Ctx $ctx) {}

    public function list(array $in): string {
        return <<<HTML
        <h1>{$in['head']}</h1>
        <p>{$in['main']}</p>
        HTML;
    }
}
```

## meta.json Reference

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `name` | string | required | Plugin class name |
| `description` | string | `''` | Short description |
| `version` | string | `'1.0.0'` | Semantic version |
| `icon` | string | `''` | Icon class (unused) |
| `emoji` | string | `''` | Emoji for nav label |
| `href` | string | `''` | Custom URL (overrides auto) |
| `method` | string | `'list'` | Default method |
| `ajax` | bool | `true` | Enable AJAX loading |
| `order` | int | `100` | Sort order in nav |
| `group` | string | `'main'` | Nav grouping |
| `auth` | bool | `false` | Require login |
| `admin` | bool | `false` | Require admin |
| `enabled` | bool | `true` | Show in nav |

## CRUDL Pattern

The base `Plugin` class provides five methods that map to URL parameters:

| Method | URL Param | Purpose |
|--------|-----------|---------|
| `create()` | `?m=create` | Show form / process new item |
| `read()` | `?m=read&id=N` | View single item |
| `update()` | `?m=update&id=N` | Show form / process edit |
| `delete()` | `?m=delete&id=N` | Remove item |
| `list()` | `?m=list` (default) | List all items |

### Form Processing Pattern

```php
public function create(): array {
    // Show form if GET request
    if (!Util::is_post()) {
        return ['action' => 'form'];
    }

    // Process POST data
    $title = trim($_POST['title'] ?? '');

    if (!$title) {
        Util::log('Title is required');
        return ['action' => 'form', 'title' => $title];
    }

    // Save to database
    $id = $this->db->create('items', ['title' => $title]);

    Util::log('Item created', 'success');
    Util::redirect("?o=MyPlugin&m=read&id=$id");
}
```

## PluginMeta Class

The `PluginMeta` readonly class provides immutable plugin metadata:

```php
final readonly class PluginMeta {
    public function __construct(
        public string $name,
        public string $description = '',
        public string $emoji = '',
        public int $order = 100,
        public bool $auth = false,
        public bool $admin = false,
        public bool $enabled = true,
        // ... more fields
    ) {}

    // Helper methods
    public function label(): string;     // "📝 Posts"
    public function url(): string;       // "?o=Posts"
    public function linkClass(): string; // "ajax-link" or ""
}
```

### Creating from File

```php
$meta = PluginMeta::fromFile('/path/to/meta.json');
```

### Creating from Array

```php
$meta = PluginMeta::fromArray([
    'name' => 'Posts',
    'emoji' => '📝',
    'auth' => true
]);
```

## Navigation Groups

Plugins are grouped in navigation by the `group` field:

| Group | Description |
|-------|-------------|
| `main` | Primary navigation |
| `admin` | Admin-only section |
| `footer` | Footer links |
| `none` | Hidden from nav |

```json
{
    "name": "Users",
    "group": "admin",
    "admin": true
}
```

## Database Plugins

For plugins that need database access:

```php
final class PostsModel extends Plugin {
    private Db $db;

    #[\Override]
    public function __construct(protected Ctx $ctx) {
        parent::__construct($ctx);
        $this->db = new Db([
            'type' => 'sqlite',
            'path' => __DIR__ . '/posts.db'
        ]);
    }

    #[\Override]
    public function list(): array {
        $posts = $this->db->read('posts', '*', '1=1 ORDER BY created DESC', [], QueryType::All);
        return ['posts' => $posts];
    }
}
```

## View Conventions

Views receive the Model's return array and render HTML:

```php
final class PostsView {
    public function __construct(private Ctx $ctx) {}

    // Method name matches Model method
    public function list(array $in): string {
        $posts = $in['posts'];

        $items = array_map(fn($p) => <<<HTML
            <article>
                <h2>{$p['title']}</h2>
                <p>{$p['excerpt']}</p>
            </article>
            HTML, $posts);

        return implode("\n", $items);
    }

    public function read(array $in): string {
        return <<<HTML
        <article class="blog-single">
            <h1>{$in['title']}</h1>
            <div>{$in['content']}</div>
        </article>
        HTML;
    }
}
```

### Fallback to Theme

If a View method doesn't exist, the Theme renders the data:

```php
// In Theme.php
public function html(array $d): string {
    return <<<HTML
    <h1>{$d['head']}</h1>
    <div>{$d['main']}</div>
    <footer>{$d['foot']}</footer>
    HTML;
}
```

## PHP 8.5 Patterns

Use modern PHP features in plugins:

### Typed Constants

```php
private const int DEFAULT_PER_PAGE = 10;
private const string TABLE = 'posts';
```

### Override Attribute

```php
#[\Override]
public function list(): array {
    // Implementation
}
```

### Match Expressions

```php
$action = match ((bool)$id) {
    true => "?o=Posts&m=update&id=$id",
    false => '?o=Posts&m=create'
};
```

### Pipe Operator

```php
$total = $this->db->read('posts', 'COUNT(*)', $where, $params, QueryType::Column)
    |> (fn($c) => (int)$c);
```

## Example: Complete Plugin

### meta.json

```json
{
    "name": "Tasks",
    "description": "Task management",
    "emoji": "✅",
    "order": 40,
    "group": "main",
    "auth": true
}
```

### TasksModel.php

```php
<?php declare(strict_types=1);
namespace SPE\Blog\Plugins\Tasks;

use SPE\Blog\Core\{Ctx, Db, Plugin, QueryType, Util};

final class TasksModel extends Plugin {
    private const int PER_PAGE = 10;
    private Db $db;

    #[\Override]
    public function __construct(protected Ctx $ctx) {
        parent::__construct($ctx);
        $this->db = new Db(['type' => 'sqlite', 'path' => __DIR__ . '/tasks.db']);
    }

    #[\Override]
    public function list(): array {
        $tasks = $this->db->read('tasks', '*',
            'user_id = :uid ORDER BY created DESC',
            ['uid' => $_SESSION['usr']['id']],
            QueryType::All
        );
        return ['tasks' => $tasks];
    }

    #[\Override]
    public function create(): array {
        if (!Util::is_post()) {
            return ['action' => 'form'];
        }

        $title = trim($_POST['title'] ?? '');
        if (!$title) {
            Util::log('Title required');
            return ['action' => 'form'];
        }

        $this->db->create('tasks', [
            'title' => $title,
            'user_id' => $_SESSION['usr']['id'],
            'created' => date('Y-m-d H:i:s')
        ]);

        Util::log('Task created', 'success');
        Util::redirect('?o=Tasks');
    }

    #[\Override]
    public function delete(): array {
        $id = (int)($_GET['id'] ?? 0);
        $this->db->delete('tasks', 'id = :id AND user_id = :uid',
            ['id' => $id, 'uid' => $_SESSION['usr']['id']]);

        Util::log('Task deleted', 'success');
        Util::redirect('?o=Tasks');
    }
}
```

### TasksView.php

```php
<?php declare(strict_types=1);
namespace SPE\Blog\Plugins\Tasks;

use SPE\Blog\Core\Ctx;

final class TasksView {
    public function __construct(private Ctx $ctx) {}

    public function list(array $in): string {
        $tasks = array_map(fn($t) => <<<HTML
            <li>
                {$t['title']}
                <a href="?o=Tasks&m=delete&id={$t['id']}"
                   onclick="return confirm('Delete?')">🗑️</a>
            </li>
            HTML, $in['tasks']);

        $list = $tasks ? '<ul>' . implode("\n", $tasks) . '</ul>' : '<p>No tasks</p>';

        return <<<HTML
        <h1>✅ My Tasks</h1>
        <a href="?o=Tasks&m=create">+ New Task</a>
        $list
        HTML;
    }

    public function create(array $in): string {
        return <<<HTML
        <h1>New Task</h1>
        <form method="post">
            <input name="title" required>
            <button type="submit">Create</button>
        </form>
        HTML;
    }
}
```
