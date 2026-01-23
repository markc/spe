# Simple PHP Engine (SPE)

[![PHP 8.5](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![SQLite](https://img.shields.io/badge/SQLite-003B57?logo=sqlite&logoColor=white)](https://www.sqlite.org/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![CI](https://github.com/markc/spe/actions/workflows/ci.yml/badge.svg)](https://github.com/markc/spe/actions/workflows/ci.yml)
[![Website](https://img.shields.io/badge/Website-markc.github.io-blue?logo=github)](https://markc.github.io/spe/)
[![Tutorial](https://img.shields.io/badge/Tutorial-10_Chapters-blue)](https://www.youtube.com/playlist?list=PLM0Did14jsitwKl7RYaVrUWnG1GkRBO4B)
[![Built with Claude Code](https://img.shields.io/badge/Built_with-Claude_Code-orange?logo=anthropic)](https://claude.ai/code)

_Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)_

A progressive **PHP 8.5 micro-framework tutorial** in 10 chapters. Each chapter builds on the previous, demonstrating modern PHP features and design patterns.

## Features

| Feature | Description |
|---------|-------------|
| **Pipe Operator** | PHP 8.5's `|>` for elegant data transformation chains |
| **Micro-Framework** | Minimal MVC architecture without heavy dependencies |
| **CRUD Operations** | Complete Create, Read, Update, Delete, List pattern |
| **PSR-4 Autoloading** | Modern Composer-based class loading |
| **SQLite + PDO** | Lightweight database with type-safe queries |
| **Custom CSS** | No Bootstrap - minimal responsive styling (~270 lines) |
| **Plugin System** | Extensible architecture with meta.json configuration |
| **Full CMS** | Blog, Pages, Categories, Auth, User management |

## Modern PHP Showcase

```php
// PHP 8.5 Pipe operator with first-class callables
$value = $input
    |> trim(...)
    |> strtolower(...)
    |> (fn($s) => filter_var($s, FILTER_SANITIZE_URL));

// PHP 8.4 Asymmetric visibility
public private(set) string $page;

// PHP 8.3 Typed constants + Override attribute
private const string DEFAULT = 'home';
#[\Override] public function list(): array { ... }

// PHP 8.2 Readonly classes
final readonly class PluginMeta { ... }

// PHP 8.1 Enums
enum QueryType { case All; case One; case Column; }
```

## Video Tutorials

**[Watch the complete tutorial series on YouTube](https://www.youtube.com/playlist?list=PLM0Did14jsitwKl7RYaVrUWnG1GkRBO4B)** - 10 chapters with AI narration covering all framework features.

Want to create your own tutorial videos? See [00-Tutorial](00-Tutorial/README.md) for the video generation pipeline.

## Requirements

- **PHP 8.5+** (for pipe operator `|>`)
- **Composer** (for chapters 05-09)

**Local-first**: PHP 8.5 isn't widely deployed yet. Run locally with `php -S localhost:8000` to learn and experiment.

## Quick Start

```bash
git clone https://github.com/markc/spe
cd spe
composer install
php -S localhost:8000 index.php
# Note: the extra index.php arg is need for routing
```

Open http://localhost:8000 to see the chapter index.

## Chapters

| # | Name | Description | Key Feature |
|---|------|-------------|-------------|
| 00 | [Tutorial](00-Tutorial/README.md) | Video generation pipeline | Playwright + Piper TTS |
| 01 | [Simple](01-Simple/README.md) | Single-file anonymous class | Pipe operator basics |
| 02 | [Styled](02-Styled/README.md) | Custom CSS, dark mode | Toast notifications |
| 03 | [Plugins](03-Plugins/README.md) | Plugin architecture | CRUDL pattern |
| 04 | [Themes](04-Themes/README.md) | Model/View separation | Multiple layouts |
| 05 | [Autoload](05-Autoload/README.md) | PSR-4 autoloading | Composer integration |
| 06 | [Session](06-Session/README.md) | Session management | State persistence |
| 07 | [PDO](07-PDO/README.md) | Database access | SQLite + QueryType enum |
| 08 | [Users](08-Users/README.md) | User management | Full CRUDL operations |
| 09 | [Blog](09-Blog/README.md) | Complete CMS | Auth, Blog, Docs, Categories |
| 10 | [Htmx](10-Htmx/README.md) | Htmx Integration | Dynamic UI without JavaScript |
| 11 | [HCP](11-HCP/README.md) | Hosting Control Panel | Mainly for Proxmox VM/CTs |

## Architecture

```
URL: ?o=Home&m=list&t=TopNav

o = Object/Plugin name
m = Method/Action (CRUDL: create, read, update, delete, list)
t = Theme (Simple, TopNav, SideBar)
```

### Request Flow

```
index.php → Init(Ctx) → {Plugin}Model→method() → {Plugin}View→method() → HTML
```

### Directory Structure (chapters 05-09)

```
XX-Chapter/
├── public/
│   └── index.php          # Entry point
└── src/
    ├── Core/              # Framework classes (Init, Ctx, Db, Plugin, Theme)
    ├── Plugins/           # Feature plugins (Model + View + meta.json)
    └── Themes/            # Layout themes (Simple, TopNav, SideBar)
```

## Documentation Strategy

**Single source of truth** via symlinks - edit once, appears everywhere:

```
docs/                          # Actual files (GitHub Pages serves from here)
├── README.md                  # Main project README
├── spe.css, spe.js, md.js     # Shared assets
└── */README.md                # Chapter documentation

/README.md                     → symlink → docs/README.md
/spe.css                       → symlink → docs/spe.css
/01-Simple/README.md           → symlink → ../docs/01-Simple/README.md
...
```

**Benefits:**
- **GitHub Pages** - serves real files from `docs/`
- **GitHub repo** - follows symlinks, displays README in each folder
- **Local dev** - symlinks work seamlessly on Linux/macOS

Edit `docs/*/README.md` and changes propagate to repo view and GitHub Pages automatically.

## Styling

**No Bootstrap!** Custom `spe.css` (~270 lines):

- CSS variables for light/dark theming
- `@media (prefers-color-scheme: dark)` automatic dark mode
- Manual toggle via localStorage
- Responsive layouts (TopNav, SideBar)
- Dropdown menus, toast notifications, card grids

## License

MIT License - See individual file headers for copyright notices.
