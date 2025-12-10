# SPE Documentation

Welcome to the Simple PHP Examples documentation. This guide covers the architecture, plugins, themes, and API reference for the SPE micro-framework.

## Overview

SPE is a progressive PHP 8.5 micro-framework tutorial organized into 9 chapters, each building on the previous to demonstrate modern PHP features:

| PHP Version | Features Used |
|-------------|---------------|
| **PHP 8.1** | First-class callables `array_keys(...)`, enums, readonly properties |
| **PHP 8.2** | Readonly classes `final readonly class` |
| **PHP 8.3** | Typed constants `const string X`, `#[\Override]` attribute |
| **PHP 8.4** | Asymmetric visibility `public private(set)`, new without parens |
| **PHP 8.5** | Pipe operator `\|>` for functional transformations |

## Architecture

The framework follows a simple MVC-like pattern with clear separation:

```
09-Blog/
├── public/
│   └── index.php           # Entry point: echo new Init(new Ctx)
└── src/
    ├── Core/               # Framework infrastructure
    │   ├── Ctx.php         # Context (config + state + nav building)
    │   ├── Db.php          # PDO wrapper with QueryType enum
    │   ├── Init.php        # Request router with auth checking
    │   ├── Plugin.php      # Base CRUDL plugin class
    │   ├── PluginLoader.php # Auto-discovery from meta.json
    │   ├── PluginMeta.php  # Immutable plugin metadata
    │   ├── Theme.php       # Base theme with nav helpers
    │   └── Util.php        # Helpers + Markdown parser
    ├── Plugins/            # Feature plugins (Model + View + meta.json)
    │   ├── Auth/           # Authentication
    │   ├── Blog/           # Public blog view
    │   ├── Categories/     # Category management
    │   ├── Contact/        # Contact form
    │   ├── Docs/           # Documentation viewer
    │   ├── Home/           # Home page
    │   ├── Pages/          # Static page management
    │   ├── Posts/          # Blog post management
    │   ├── Profile/        # User profile
    │   └── Users/          # User management
    └── Themes/             # Layout templates
        ├── Simple.php      # Basic single-column
        ├── TopNav.php      # Fixed top navigation
        └── SideBar.php     # Left sidebar layout
```

## Request Flow

```
1. index.php           → echo new Init(new Ctx)
2. Ctx::__construct()  → Build nav from database + PluginLoader
3. Init::__construct() → Parse URL params, check auth, dispatch
4. {Plugin}Model       → Execute method, return data array
5. {Plugin}View        → Render HTML (or fall back to Theme)
6. Init::__toString()  → Output final response
```

## URL Parameters

| Param | Description | Example |
|-------|-------------|---------|
| `o` | Plugin/Object name | `?o=Blog` |
| `m` | Method (CRUDL) | `?m=read` |
| `t` | Theme | `?t=TopNav` |
| `p` | Page slug shortcut | `?p=about` |
| `id` | Record ID | `?id=5` |

## Quick Links

- [Creating Plugins](plugins/README.md)
- [PHP 8.5 Features](php85-features.md)
- [Database API](database.md)
- [Markdown Parser](markdown.md)
- [Authentication](authentication.md)

## Chapter Progression

| # | Chapter | Key Addition |
|---|---------|--------------|
| 01 | Simple | Single-file anonymous class, pipe operator |
| 02 | Styled | Custom CSS, dark mode, toast notifications |
| 03 | Plugins | Plugin architecture, CRUDL pattern |
| 04 | Themes | Model/View separation, multiple layouts |
| 05 | Autoload | PSR-4 autoloading via Composer |
| 06 | Session | PHP session management |
| 07 | PDO | Database access with QueryType enum |
| 08 | Users | User management CRUDL |
| 09 | Blog | Full CMS: Auth, Blog, Pages, Categories, Docs |

## License

MIT License - Copyright (C) 2015-2025 Mark Constable <mc@netserva.org>
