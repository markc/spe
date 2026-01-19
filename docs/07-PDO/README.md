# SPE::07 PDO

_Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)_

Chapter Seven adds persistent storage. Where previous chapters kept all content in PHP code—static navigation arrays, hardcoded page text—this chapter introduces database access through PDO and SQLite. Pages become editable. Blog posts can be created, modified, and deleted. Navigation builds dynamically from database records. The application transforms from a static demonstration into a genuine content management foundation. This chapter also marks a pivotal architectural shift: the first use of shared library code in `app/lib/`, demonstrating why common functionality belongs in reusable components rather than duplicated across chapters.

## The Shared Library

Previous chapters were self-contained. Each had its own `Core/Util.php` with whatever helper methods it needed. This worked when utilities were simple—Chapter Six needed only a `timeAgo()` function. But database access requires substantially more code: connection management, query building, result fetching, schema initialization. Duplicating this across chapters would create maintenance nightmares and obscure the actual chapter-specific learning.

Chapter Seven introduces the `app/lib/` directory containing shared components:

```
app/lib/
├── Db.php       # Database abstraction extending PDO
├── Env.php      # Environment configuration loader
├── Schema.php   # Database schema definitions
└── Util.php     # Shared utilities (md, excerpt, timeAgo, etc.)
```

The namespace `SPE\App` maps to this directory, allowing any chapter to import shared functionality:

```php
use SPE\App\{Db, QueryType};
use SPE\App\Util;
```

This architectural decision mirrors real-world PHP development where common code lives in vendor packages or shared libraries. The progression from Chapter Six's minimal local `Util.php` (19 lines, one method) to Chapter Seven's use of `SPE\App\Util` (140+ lines, many methods) demonstrates why shared libraries become necessary as applications grow.

## The Database Abstraction

The `Db` class extends PHP's PDO to provide a cleaner CRUD interface:

```php
final class Db extends PDO
{
    public function __construct(string $name = 'blog')
    {
        $type = Env::get('DB_TYPE', 'sqlite');

        if ($type === 'sqlite' && !Schema::exists($name)) {
            $this->ensureDir($name);
            Schema::init($name);
        }

        $dsn = match ($type) {
            'sqlite' => 'sqlite:' . Schema::path($name),
            default => sprintf('mysql:host=%s;port=%s;dbname=%s', ...),
        };

        parent::__construct($dsn, Env::get('DB_USER'), Env::get('DB_PASS'), self::OPTS);
    }
}
```

The constructor handles database creation automatically. When using SQLite (the default), if the database file doesn't exist, it creates the directory structure and initializes the schema. This zero-configuration approach means the application works immediately without manual database setup.

The CRUD methods provide a fluent interface for common operations:

```php
// Create
$id = $db->create('posts', ['title' => 'New Post', 'content' => '...']);

// Read with QueryType enum
$posts = $db->read('posts', '*', "type='post'", [], QueryType::All);
$post = $db->read('posts', '*', 'id=:id', ['id' => 5], QueryType::One);
$count = $db->read('posts', 'COUNT(*)', '', [], QueryType::Col);

// Update
$db->update('posts', ['title' => 'Updated'], 'id=:id', ['id' => 5]);

// Delete
$db->delete('posts', 'id=:id', ['id' => 5]);
```

The `QueryType` enum eliminates magic strings for fetch modes:

```php
enum QueryType: string { case All = 'all'; case One = 'one'; case Col = 'col'; }
```

Using `QueryType::All` returns all matching rows as an array. `QueryType::One` returns a single row. `QueryType::Col` returns a single column value—useful for counts and aggregates.

## Dynamic Navigation

Previous chapters defined navigation as a static array in `Ctx`:

```php
// Chapter 6: Static navigation
public array $nav = [['🏠 Home', 'Home'], ['📖 About', 'About'], ['✉️ Contact', 'Contact']]
```

Chapter Seven builds navigation from the database:

```php
// Chapter 7: Dynamic navigation from pages
$this->db = new Db('blog');
$this->nav = array_map(
    fn($r) => [trim(($r['icon'] ?? '') . ' ' . $r['title']), ucfirst($r['slug'])],
    $this->db->read('posts', 'id,title,slug,icon', "type='page' ORDER BY id", [], QueryType::All)
);
$this->nav[] = ['📝 Blog', 'Blog'];
```

Pages stored with `type='page'` become navigation items. Each page's icon and title combine for the display text; the slug becomes the URL parameter. The Blog link appends at the end, providing access to the post listing.

This approach means adding a new navigation item requires only creating a new page record—no code changes needed. Reordering navigation means updating the database. The application becomes data-driven rather than code-driven.

## The Blog Plugin

The Blog plugin handles all content management through `BlogModel` and `BlogView`:

```php
final class BlogModel {
    public function create(): array {
        if ($_POST) {
            $this->ctx->db->create('posts', [
                'title' => $this->f['title'],
                'slug' => $this->f['slug'] ?: $this->slug($this->f['title']),
                'content' => $content,
                'type' => $this->f['type'],
                // ...
            ]);
            $this->ctx->flash('msg', 'Post created successfully');
            header('Location: ?o=Blog&edit');
            exit;
        }
        return [];
    }
}
```

The model handles form processing. When `$_POST` contains data, it creates the record, sets a flash message for confirmation, and redirects to the edit listing. The slug auto-generates from the title if not provided, using a pipe chain:

```php
private function slug(string $t): string {
    return $t |> strtolower(...) |> (fn($s) => preg_replace('/[^a-z0-9]+/', '-', $s)) |> (fn($s) => trim($s, '-'));
}
```

The view renders content with Markdown support:

```php
return "<div class='card mt-4'><h2>$ti</h2>
    <div class='prose mt-2'>" . Util::md($a['content']) . "</div>
    // ...
</div>";
```

The `Util::md()` method from the shared library parses GitHub-Flavored Markdown into HTML—headings, bold, italic, links, code blocks, lists, tables. This transforms plain text content into formatted pages without requiring HTML knowledge from content editors.

## Page Dispatch

The `Init` class routes requests differently than Chapter Six:

```php
public function __construct(private Ctx $ctx) {
    [$o, $m, $t] = [$ctx->in['o'], $ctx->in['m'], $ctx->in['t']];

    if ($o === 'Blog') {
        $model = self::NS . "Plugins\\Blog\\BlogModel";
        $ary = class_exists($model) ? (new $model($ctx))->$m() : [];
        $view = self::NS . "Plugins\\Blog\\BlogView";
        $main = class_exists($view) ? (new $view($ctx, $ary))->$m() : '';
    } else {
        // Load page from database
        $ary = $ctx->db->read('posts', '*', "slug=:s AND type='page'", ['s' => strtolower($o)], QueryType::One) ?: [];
        $view = self::NS . "Plugins\\Blog\\BlogView";
        $main = $ary ? (new $view($ctx, $ary))->page() : '<div class="card"><p>Page not found.</p></div>';
    }

    $this->out = [...$ctx->out, ...$ary, 'main' => $main];
}
```

When `o=Blog`, the dispatcher loads the Blog plugin for CRUD operations. For any other value, it queries the database for a page with a matching slug. Found pages render through `BlogView::page()`; missing pages show an error. This eliminates the need for separate About, Contact, and Home plugins—they're all database pages now.

## The Content Model

The `posts` table stores both pages and blog posts:

```sql
CREATE TABLE posts (
    id INTEGER PRIMARY KEY,
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    content TEXT,
    type TEXT DEFAULT 'post',  -- 'page', 'post', or 'doc'
    icon TEXT,
    author TEXT,
    created TEXT,
    updated TEXT
);
```

The `type` field distinguishes content: pages appear in navigation, posts appear in the blog listing, docs provide reference material. The icon field stores emoji for visual identification. Slugs must be unique, serving as URL-friendly identifiers.

This single-table approach simplifies the data model while supporting multiple content types. Later chapters extend this with categories, tags, and relationships, but the core structure remains clean and queryable.

## Session Integration

Chapter Seven inherits all session functionality from Chapter Six. Sticky URL parameters persist theme and navigation choices:

```php
session_status() === PHP_SESSION_NONE && session_start();

$this->in = array_map(fn($k, $v) => $this->ses($k, $v), array_keys($in), $in)
    |> (fn($v) => array_combine(array_keys($in), $v));
```

Flash messages confirm CRUD operations:

```php
$this->ctx->flash('msg', 'Post updated successfully');
$this->ctx->flash('type', 'success');
```

The Theme base class renders these as toast notifications. Select TopNav theme once and it persists across all pages. Create a post and see the confirmation message. The session layer from Chapter Six integrates seamlessly with the database layer from Chapter Seven.

## The Directory Structure

Chapter Seven streamlines the structure by eliminating individual page plugins:

```
07-PDO/src/
├── Core
│   ├── Ctx.php      # Session + database initialization
│   ├── Init.php     # Blog plugin or page dispatch
│   ├── Plugin.php   # Abstract base (for Blog)
│   ├── Theme.php    # Abstract base with nav/flash helpers
│   └── View.php     # Base view class
├── Plugins
│   └── Blog
│       ├── BlogModel.php   # CRUD operations
│       └── BlogView.php    # List, read, edit forms
└── Themes
    ├── SideBar.php
    ├── Simple.php
    └── TopNav.php
```

Compare to Chapter Six's fifteen files across seven plugin directories. Chapter Seven has ten files in five directories—yet provides far more functionality. The Blog plugin replaces static page plugins; the database replaces hardcoded content. Fewer files, more capabilities.

Notable differences from Chapter Six:
- No `Core/Util.php`—uses `SPE\App\Util` instead
- No Home, About, Contact plugins—content lives in database
- `Ctx.php` adds database connection and dynamic navigation
- `Init.php` handles page lookup from database

## List and Edit Modes

The blog listing provides two views: public display and edit management.

Public view (`?o=Blog`) shows posts as cards with excerpts and metadata. The `Util::excerpt()` helper extracts the first 200 characters of content for preview. Each card links to the full post.

Edit view (`?o=Blog&edit`) shows a searchable table with columns for title, type, updated date, and action links. The edit listing includes search filtering by title and content, type indicators (📝 post, 📄 page, 📚 doc), and pagination for large content collections.

## Running the Application

```bash
cd /path/to/spe
composer install
cd 07-PDO/public
php -S localhost:8080
```

Navigate to `http://localhost:8080`. The database creates automatically on first load—check `app/sqlite/blog.db` for the SQLite file. The Home page loads from the database. Click through navigation items—all database pages. Visit Blog to see posts. Click "Manage Posts" to access the edit interface.

Create a new post: title, slug (auto-generated if empty), icon, type, and Markdown content. Save and see the flash confirmation. Edit existing pages—change the Home content and watch the homepage update. Delete test posts. The full CRUD cycle works immediately.

Try the theme selector—your choice persists via sessions. Search within the edit listing—results filter in real-time. Navigate between pages—URLs stay clean while state persists. The session layer from Chapter Six combines with the database layer to create a responsive, stateful application.

This chapter establishes the foundation for everything that follows. Chapter Eight adds user management with authentication. Chapter Nine builds a complete CMS with categories, tags, and access control. Chapter Ten integrates external APIs. But they all build on this core: PDO for database access, shared libraries for common code, sessions for state, and the plugin architecture for extensibility.
