# Simple PHP Engine (SPE)

[![PHP 8.5](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![SQLite](https://img.shields.io/badge/SQLite-003B57?logo=sqlite&logoColor=white)](https://www.sqlite.org/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![CI](https://github.com/markc/spe/actions/workflows/ci.yml/badge.svg)](https://github.com/markc/spe/actions/workflows/ci.yml)
[![Website](https://img.shields.io/badge/Website-markc.github.io-blue?logo=github)](https://markc.github.io/spe/)
[![Built with Claude Code](https://img.shields.io/badge/Built_with-Claude_Code-orange?logo=anthropic)](https://claude.ai/code)

_Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)_

A progressive **PHP 8.5 tutorial** in nine chapters that builds one small web application nine times, each time adding exactly one idea. It shows how to write plain, modern PHP — the kind you would reach for instead of Laravel or Symfony on a small-to-moderate project — with no framework and no runtime dependencies. It is written to be read: by people learning current PHP, and by AI agents that need a clean, consistent reference for how to do it.

The rules the whole series follows are in **[CONVENTIONS.md](CONVENTIONS.md)**, and the machine-readable chapter list is in [`chapters.json`](https://github.com/markc/spe/blob/main/chapters.json).

## What it teaches

Each chapter is a strict diff of the one before it — `diff -r 05-Autoload 06-Session` shows only the session idea — so you can see exactly what each concept costs.

| # | Chapter | The one idea it adds | PHP features |
|---|---------|----------------------|--------------|
| 01 | [Simple](01-Simple/README.md) | A request becomes a page (one anonymous class) | pipe `\|>`, typed constants, `private(set)`, first-class callables |
| 02 | [Styled](02-Styled/README.md) | Presentation: shared assets, app shell, dark mode, toasts | `match`, heredoc/nowdoc |
| 03 | [Plugins](03-Plugins/README.md) | The request contract (`o`,`m`,`x`) and the CRUDL plugin | readonly classes, `new X()->m()`, `#[\Override]` |
| 04 | [Views](04-Views/README.md) | Model/View/Theme split; escape at output | `#[\Override]`, `htmlspecialchars` flags |
| 05 | [Autoload](05-Autoload/README.md) | The same code as PSR-4 files, via Composer | namespaces, `strict_types` |
| 06 | [Session](06-Session/README.md) | Sessions, flash, CSRF, POST-only writes | enums with methods, secure cookies |
| 07 | [PDO](07-PDO/README.md) | SQLite, prepared statements, a real CRUDL | backed enums, `#[\NoDiscard]` |
| 08 | [Auth](08-Auth/README.md) | Users, roles, login/logout, remember-me | enum ACL, readonly value objects, `?->` |
| 09 | [Blog](09-Blog/README.md) | Content engine: Markdown, tags, pagination, self-hosted docs | property hooks, `array_first/last`, URI ext |

## The request contract

From chapter 03 on, every request is described by a few validated query parameters, read and constrained once in `Ctx`:

```
?o=Blog&m=read&i=1&x=json

o = Object    the plugin (must match /^[A-Z][A-Za-z]*$/ and be a Plugin subclass)
m = Method    one of create, read, update, delete, list
i = Id        integer record id
x = eXport    '' for HTML, or json
```

### Request flow

```
public/index.php → new Init(new Ctx)
    Ctx     validates the request, opens the DB, restores the user
    Init    picks {o}Model, calls ->{m}(), passes the data to {o}View->{m}()
    Theme   wraps the view output in the HTML document   (or Init emits JSON for ?x=json)
```

### Layout (chapters 05–09)

```
XX-Chapter/
├── public/index.php     # 3-line entry point: require autoloader, echo new Init(new Ctx)
├── src/
│   ├── Core/            # Ctx, Init, Plugin, View, Theme (+ Db, enums, value objects as introduced)
│   └── Plugins/{Name}/  # {Name}Model.php returns data, {Name}View.php returns HTML
├── schema.sql           # created + seeded on first run (07+)
└── data/                # the SQLite file (gitignored)
```

Chapters 01–04 are a single `public/index.php` each; everything they need is in that one file.

## Core classes

Introduced once and kept, with the same name and job, thereafter: **`Ctx`** (the validated request; gains the session, DB and current user as the series grows), **`Init`** (the front controller), **`Plugin`** (model base, CRUDL), **`View`** (HTML base, owns `e()` escaping), **`Theme`** (the document), **`Db`** (PDO wrapper), **`QueryType`**/**`Role`**/**`Type`** (enums), **`User`**/**`Post`** (readonly/hooked value objects), **`Md`** (safe Markdown).

## The one habit worth stealing

**Validate input, escape output.** `Ctx` constrains every request parameter to what it is allowed to be; models carry raw data; views escape every dynamic value at the moment it becomes HTML, with one function (`View::e()`, `htmlspecialchars` with `ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5`). Writes happen only on a CSRF-checked POST; SQL is always a prepared statement; passwords are hashed; sessions regenerate on login. The security model is a property of these few rules, not of a framework.

## Requirements

- **PHP 8.5+** (for the pipe operator `|>`)
- **Composer** (chapters 05–09)
- **SQLite** / `pdo_sqlite` (chapters 07–09)

## Quick start

```bash
git clone https://github.com/markc/spe
cd spe
composer install

# Serve everything through the root router:
php -S localhost:8000 index.php
# → http://localhost:8000/  (chapter index)

# Or run a single chapter on its own:
php -S localhost:8009 -t 09-Blog/public
```

Sign in to the later chapters as `admin@example.com` / `admin` (or `user@example.com` / `user`).

## Development

```bash
composer check     # runs the manifest check, mago lint, and the Pest suite
composer test      # Pest only
composer lint      # mago only
composer format    # mago format
```

Each chapter has its own Pest suite under `tests/<Name>/` that starts the chapter on PHP's built-in server and drives it over HTTP, so chapters never share a process.

## Styling

No CSS framework. `base.css` is colour-agnostic structure (layout, components, utilities); `site.css` defines every colour in OKLCH for light, dark and four schemes; `base.js` handles theme, sidebars and toasts. They are shared by every chapter so the look stays fixed while the code evolves.

## Documentation

`docs/` is the GitHub Pages source and the single source of truth; the repo-root `README.md` and each chapter's `README.md` are symlinks into it. Edit under `docs/` and the change shows up everywhere.

## License

MIT — see [LICENSE](https://github.com/markc/spe/blob/main/LICENSE).
