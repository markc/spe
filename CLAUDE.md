# CLAUDE.md

This file provides guidance to Claude Code when working with this repository.

## Project Overview

**SPE (Simple PHP Examples)** is a progressive PHP 8.5 micro-framework tutorial in 10 chapters (00-10). Each chapter builds on the previous, demonstrating modern PHP features and design patterns.

**For tutorial video generation**, see [00-Tutorial/CLAUDE.md](00-Tutorial/CLAUDE.md).

## Requirements

- PHP 8.5+ (for pipe operator `|>`)
- Composer (for chapters 05-10)

## Quick Reference

```bash
# Run from root (serves all chapters)
php -S localhost:8000

# Run specific chapter (05-10)
cd 09-Blog/public && php -S localhost:8080

# Install dependencies
composer install
```

## PHP Features by Version

| Version | Features Used |
|---------|---------------|
| PHP 8.5 | Pipe operator `\|>` with first-class callables |
| PHP 8.4 | Asymmetric visibility `public private(set)`, `new` without parentheses |
| PHP 8.3 | Typed constants, `#[\Override]` attribute |
| PHP 8.2 | Readonly classes |
| PHP 8.1 | Enums, first-class callables `fn(...)` |

## Architecture

### URL Parameters
```
?o=Home     - Plugin/Object name
?m=list     - Method (create, read, update, delete, list)
?t=TopNav   - Theme (Simple, TopNav, SideBar)
?id=1       - Record ID for CRUD operations
```

### Request Flow
```
index.php → Init(Ctx) → {Plugin}Model→method() → {Plugin}View→method() → HTML
```

### Directory Structure
```
XX-Chapter/
├── public/index.php       # Entry point (all chapters)
└── src/                   # (chapters 05-10 only)
    ├── Core/              # Init, Ctx, Db, Plugin, Theme, Util
    ├── Plugins/{Name}/    # {Name}Model.php, {Name}View.php, meta.json
    └── Themes/            # Simple.php, TopNav.php, SideBar.php
```

### Namespacing (chapters 05-10)

PSR-4 namespace: `SPE\{Chapter}\` (e.g., `SPE\Blog\Core\Init`)

## Key Code Patterns

```php
<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

// PHP 8.5 Pipe operator
$value = $input |> trim(...) |> strtolower(...);

// PHP 8.4 Asymmetric visibility
public private(set) string $page;

// PHP 8.3 Typed constants
private const string DEFAULT = 'home';

// PHP 8.3 Override attribute
#[\Override] public function list(): array { ... }

// PHP 8.2 Readonly class
final readonly class PluginMeta { ... }

// PHP 8.1 Enum
enum QueryType { case All; case One; case Column; }
```

## Chapter Progression

| # | Name | Key Addition |
|---|------|--------------|
| 00 | Tutorial | Video generation pipeline (Playwright + Piper TTS) |
| 01 | Simple | Single-file anonymous class, pipe operator |
| 02 | Styled | Custom CSS, dark mode, toast notifications |
| 03 | Plugins | Plugin architecture, CRUDL pattern |
| 04 | Themes | Model/View separation, multiple layouts |
| 05 | Autoload | PSR-4 autoloading via Composer |
| 06 | Session | PHP session management |
| 07 | PDO | SQLite database, QueryType enum |
| 08 | Users | User management CRUDL |
| 09 | Blog | Full CMS: Auth, Blog, Pages, Categories, Docs |
| 10 | YouTube | YouTube Manager: OAuth, API integration |

## Databases (07-10)

- `blog.db` - Posts (type: post/page/doc), Categories, junction table
- `users.db` - Authentication and user profiles

## Shared Assets & Documentation

**Symlink strategy** for single source of truth:

```
docs/                      # Real files (GitHub Pages source)
├── README.md              # Project README
├── base.css               # Generic CSS framework (layouts, components)
├── site.css               # SPE-specific branding (colors, fonts)
├── base.js                # Generic JS (theme toggle, toast, animations)
├── md.js                  # Markdown rendering
└── */README.md            # Chapter docs

/README.md                 → symlink → docs/README.md
/base.css                  → symlink → docs/base.css
/site.css                  → symlink → docs/site.css
/base.js                   → symlink → docs/base.js
/01-Simple/README.md       → symlink → ../docs/01-Simple/README.md
```

- **Edit docs/** - changes appear in repo view AND GitHub Pages
- **GitHub follows symlinks** for README display
- **Local symlinks work** on Linux/macOS

All chapters 02-10 reference shared assets via absolute paths (`/base.css`, `/site.css`, `/base.js`)

### CSS Architecture (base.css + site.css)

**base.css** - Generic reusable framework (~1700 lines):
- CSS cascade layers: `@layer reset, tokens, base, components, utilities, animations`
- Design tokens (spacing, typography, shadows, transitions)
- Layouts: `.container`, `.topnav`, `.sidebar-layout`, `.sidebar`
- Components: `.card`, `.btn`, `.tag`, `.dropdown`, `.toast`, `.prose`
- Content: `.article-*`, `.data-table`, `.list-item-*`, `.pagination`
- Utilities: flex, grid, spacing, text alignment
- Animations: fade, scale, reveal, hover effects
- Accessibility: `prefers-reduced-motion`, `prefers-contrast`, `:focus-visible`

**site.css** - Colors only (~67 lines):
- Color tokens (light/dark themes via CSS custom properties)
- Single brand class: `.btn-php`

**Inline theme script** (in `<head>` to prevent FOUC):
```html
<script>(function(){const t=localStorage.getItem("base-theme");document.documentElement.className=t||(matchMedia("(prefers-color-scheme:dark)").matches?"dark":"light")})();</script>
```

## Icons

Unicode emoji instead of icon libraries:
- Home: `🏠` About: `📋` Contact: `✉️` Blog: `📰` Posts: `📝`
- Pages: `📄` Categories: `🏷️` Users: `👥` Docs: `📚` Auth: `🔒`

## License

MIT License - Copyright (C) 2015-2025 Mark Constable <mc@netserva.org>
