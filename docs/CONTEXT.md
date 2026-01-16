# SPE Project Context

A progressive PHP 8.5 micro-framework tutorial demonstrating modern PHP patterns across 10 chapters. MIT licensed, authored by Mark Constable.

## What This Is

SPE (Simple PHP Engine) teaches PHP development through incremental complexity. Each chapter builds on the previous, introducing one new concept while maintaining a minimal codebase. The target audience is developers learning modern PHP or those wanting a lightweight alternative to heavy frameworks.

## Technology Stack

PHP 8.5+ (required for pipe operator `|>`), SQLite via PDO, Composer for PSR-4 autoloading (chapters 05+), custom CSS (~270 lines, no Bootstrap), vanilla JavaScript for dark mode and toasts.

## URL Routing Convention

All routing uses query parameters:
```
?o=PluginName&m=method&t=ThemeName

o = Object/Plugin (Home, Users, Blog, Pages, etc.)
m = Method (list, create, read, update, delete)
t = Theme (Simple, TopNav, SideBar)
```

Default: `?o=Home&m=list&t=TopNav`

## Request Flow

```
index.php → Init(Ctx) → {Plugin}Model::method() → {Plugin}View::method() → HTML
```

## Chapter Progression

| Ch | Folder | Focus | Key Addition |
|----|--------|-------|--------------|
| 00 | 00-Tutorial | Video pipeline | Playwright, Piper TTS, MLT/FFmpeg |
| 01 | 01-Simple | Foundation | Single anonymous class, pipe operator |
| 02 | 02-Styled | Presentation | Custom CSS, dark mode toggle, toasts |
| 03 | 03-Plugins | Architecture | Plugin system with meta.json |
| 04 | 04-Themes | Separation | Model/View split, multiple layouts |
| 05 | 05-Autoload | Standards | PSR-4, Composer, namespaces |
| 06 | 06-Session | State | Session handling, flash messages |
| 07 | 07-PDO | Data | SQLite database, QueryType enum |
| 08 | 08-Users | CRUDL | Full user management |
| 09 | 09-Blog | CMS | Auth, blog posts, categories, pages |
| 10 | 10-YouTube | Integration | OAuth, YouTube API, shared services |

## Directory Structure (Chapters 05-09)

```
XX-Chapter/
├── public/
│   └── index.php           # Entry point, minimal bootstrap
├── src/
│   ├── Core/
│   │   ├── Init.php        # Application bootstrap
│   │   ├── Ctx.php         # Request context container
│   │   ├── Db.php          # PDO wrapper with QueryType enum
│   │   ├── Plugin.php      # Base plugin class
│   │   └── Theme.php       # Base theme class
│   ├── Plugins/
│   │   └── {Name}/
│   │       ├── {Name}Model.php
│   │       ├── {Name}View.php
│   │       └── meta.json   # Plugin config
│   └── Themes/
│       ├── Simple.php      # Minimal layout
│       ├── TopNav.php      # Horizontal nav
│       └── SideBar.php     # Sidebar nav
├── data/
│   └── spe.db              # SQLite database
└── composer.json
```

## PHP Version Features Used

### PHP 8.5 (Required)
```php
// Pipe operator for data transformation
$clean = $input |> trim(...) |> strtolower(...) |> htmlspecialchars(...);
```

### PHP 8.4
```php
// Asymmetric visibility
public private(set) string $page;
```

### PHP 8.3
```php
// Typed constants
private const string DEFAULT = 'home';
// Override attribute
#[\Override] public function list(): array { ... }
```

### PHP 8.2
```php
// Readonly classes
final readonly class PluginMeta { ... }
```

### PHP 8.1
```php
// Enums for query types
enum QueryType { case All; case One; case Column; }
```

## CRUDL Pattern

All data plugins follow this method convention:

| Method | Route | Purpose |
|--------|-------|---------|
| list | `?o=Users&m=list` | Display all records (paginated) |
| create | `?o=Users&m=create` | Show form / process new record |
| read | `?o=Users&m=read&i=1` | Display single record |
| update | `?o=Users&m=update&i=1` | Edit form / save changes |
| delete | `?o=Users&m=delete&i=1` | Confirm / remove record |

Parameter `i` = item ID for single-record operations.

## Database Schema (Chapter 09)

```sql
-- Users table
CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    name TEXT,
    role TEXT DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Posts table  
CREATE TABLE posts (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    title TEXT NOT NULL,
    slug TEXT UNIQUE,
    content TEXT,
    status TEXT DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Categories and pivot tables follow similar patterns
```

## CSS Architecture

Custom `spe.css` using CSS custom properties:
```css
:root {
    --bg: #ffffff;
    --fg: #333333;
    --accent: #0066cc;
    /* ... */
}
@media (prefers-color-scheme: dark) {
    :root {
        --bg: #1a1a1a;
        --fg: #e0e0e0;
    }
}
```

Manual toggle stores preference in `localStorage.theme`.

## Plugin meta.json Format

```json
{
    "name": "Users",
    "description": "User management plugin",
    "version": "1.0.0",
    "nav": true,
    "order": 10,
    "icon": "👤",
    "requires": ["Db", "Session"]
}
```

## Common Tasks

### Add a new plugin
1. Create `src/Plugins/{Name}/` folder
2. Add `{Name}Model.php` extending `Plugin`
3. Add `{Name}View.php` extending `Plugin`
4. Add `meta.json` with nav:true to appear in menu
5. Register in Ctx if needed

### Add a database table
1. Add CREATE TABLE in `data/schema.sql`
2. Create Model with CRUDL methods
3. Use `Db::qry()` with QueryType enum

### Change theme layout
1. Edit theme class in `src/Themes/`
2. Or create new theme extending `Theme`
3. Select via `?t=ThemeName` parameter

### Run locally
```bash
cd XX-Chapter
composer install  # chapters 05+
php -S localhost:8000 -t public
```

## Video Generation Pipeline (Chapter 00)

Scripts for creating tutorial videos:
```
00-Tutorial/
├── scripts/
│   ├── capture.ts      # Playwright browser recording
│   ├── narrate.sh      # Piper TTS audio generation
│   └── compose.sh      # MLT/FFmpeg video assembly
├── content/
│   └── {chapter}.md    # Narration scripts
└── output/             # Final MP4 files
```

## External Links

- Docs: https://markc.github.io/spe/
- Repo: https://github.com/markc/spe
- Videos: https://www.youtube.com/playlist?list=PLM0Did14jsitwKl7RYaVrUWnG1GkRBO4B

## Conventions

- File naming: PascalCase for classes, snake_case for scripts
- One class per file, PSR-4 compliant namespaces
- Methods return arrays (Model) or strings (View)
- Views use heredoc syntax for HTML templates
- No external CSS/JS frameworks
- SQLite for portability, no MySQL required
