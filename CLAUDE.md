# CLAUDE.md

This file provides guidance to Claude Code when working with this repository.

## Project Overview

**SPE (Simple PHP Engine)** is a progressive PHP 8.5 tutorial in nine chapters (01–09). It builds one small web application nine times, each chapter adding exactly one idea, to teach plain modern PHP as a framework-free alternative to Laravel/Symfony for small-to-moderate projects — readable by humans and AI agents alike.

**The authoritative rules are in [docs/CONVENTIONS.md](docs/CONVENTIONS.md).** The machine-readable chapter list is `chapters.json`, verified against the tree by `bin/check-chapters.php`. `00-Tutorial/` is video-generation tooling, not a chapter.

## Requirements

- PHP 8.5+ (pipe operator `|>`, property hooks, `#[\NoDiscard]`, URI extension)
- Composer (chapters 05–09)
- `pdo_sqlite` (chapters 07–09)

## Quick reference

```bash
composer install

# Serve everything through the root router:
php -S localhost:8000 index.php          # http://localhost:8000/

# Or one chapter standalone:
php -S localhost:8009 -t 09-Blog/public

composer check      # bin/check-chapters.php + mago lint + pest
composer test       # pest
composer lint       # mago
composer format     # mago format
_bin/dcs-sync       # verify vendored DCS files
```

`base.css`, `site.css` and `base.js` are vendored from dcs.spa at the revision pinned in `dcs-upstream.json`; SPE-specific CSS lives in `spe.css`.

Later chapters seed a login: `admin@example.com` / `admin`, `user@example.com` / `user`.

## The request contract (chapters 03+)

```
?o=Blog&m=read&i=1&x=json
o = plugin      validated /^[A-Z][A-Za-z]*$/ and must be a Plugin subclass
m = method      create | read | update | delete | list
i = id          integer
x = export      '' (HTML) | json
```

Chapter 09 also validates `page` (int) and `tag` (slug). Everything is validated once in `Ctx`.

### Request flow

```
public/index.php → new Init(new Ctx)
Ctx   validates the request; opens the DB and restores the user in later chapters
Init  resolves {o}Model, calls ->{m}(), passes the data to {o}View->{m}()
Theme wraps the view output in the HTML document   (Init emits JSON when x=json)
```

## Architecture

### Directory structure (05–09)

```
XX-Chapter/
├── public/index.php     # require ../../vendor/autoload.php; echo new Init(new Ctx);
├── src/Core/            # Ctx, Init, Plugin, View, Theme (+ Db, enums, value objects as introduced)
├── src/Plugins/{Name}/  # {Name}Model.php (returns data), {Name}View.php (returns HTML)
├── schema.sql           # created + seeded on first run (07+)
└── data/                # SQLite file, gitignored
```

Chapters 01–04 are a single `public/index.php` each. PSR-4 namespace per chapter: `SPE\{Chapter}\` (e.g. `SPE\Blog\Core\Init`). Each chapter is **self-contained** — there is no shared root PHP library; duplication between chapters is intentional, because they are snapshots.

### Core classes (introduced once, kept thereafter)

`Ctx` (validated request; gains session/DB/user), `Init` (front controller), `Plugin` (model base, CRUDL), `View` (HTML base, owns `e()`), `Theme` (document), `Db` (PDO wrapper), `QueryType`/`Role`/`Type` (enums), `User`/`Post` (readonly / property-hook value objects), `Md` (safe Markdown), `Flash` (toast enum).

## Chapter progression

| # | Name | Idea | PHP features |
|---|------|------|--------------|
| 01 | Simple | request → page (anonymous class) | pipe `\|>`, typed const, `private(set)`, first-class callables |
| 02 | Styled | presentation: app shell, dark mode, toasts | `match`, heredoc/nowdoc |
| 03 | Plugins | request contract + CRUDL plugins | readonly classes, `new X()->m()`, `#[\Override]` |
| 04 | Views | model/view/theme; escape at output | `View::e()`, base-view fallback |
| 05 | Autoload | PSR-4 files via Composer | namespaces, `strict_types` |
| 06 | Session | sessions, flash, CSRF, POST-only writes | enums with methods, secure cookies, PRG |
| 07 | PDO | SQLite, prepared statements, Posts CRUDL | backed enums, `#[\NoDiscard]` |
| 08 | Auth | users, roles, login/logout, remember-me | enum ACL, readonly value objects, `?->` |
| 09 | Blog | Markdown content engine: posts + docs, tags, pagination | property hooks, `array_first/last`, URI ext |

## Key rules (from CONVENTIONS.md)

- **One idea per chapter; each chapter is a strict diff of the previous.** `diff -r 05-Autoload 06-Session` shows only the session idea. Change the earliest chapter an idea belongs to, then carry the identical change forward.
- **Validate input, escape output.** `Ctx` constrains each parameter to an allow-list; models carry raw data; views escape every dynamic value with `View::e()` (`htmlspecialchars` + `ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5`). The only unescaped output is app-produced HTML, named `$html` (chapter 09 markdown).
- **Writes only on a CSRF-checked POST** via `Ctx::post()`; delete and logout are POST. SQL is always prepared; identifiers are never from input. Passwords hashed; session id regenerated on login/logout.
- **Tight code, comprehensive docs.** Source carries almost no comments; the explanation lives in each chapter's README. Prefer shortening the code and lengthening the README.
- **Stable names, HTML in heredocs (never concatenated), Lucide icons (pinned), no CSS framework.**

## Documentation

`docs/` is the GitHub Pages source and the single source of truth. The root `README.md` and each `XX-Chapter/README.md` are symlinks into `docs/`. Each chapter README follows a fixed section order (opening · what changed · walkthrough · PHP features · security · try it · next); see `.claude/runbooks/documentation-style-guide.md`. After changing a chapter, update its README and `chapters.json`, then run `composer check`.

## Styling

`base.css` (colour-agnostic structure) + `site.css` (all colours, OKLCH, light/dark + six schemes) + `base.js` (theme, sidebars, carousels, toasts), with SPE additions in `spe.css`. Shared by every chapter so the look stays fixed while the code evolves. Referenced by relative paths (`../base.css`, `../site.css`, `../spe.css`).

## ai.txt

Two copies (`ai.txt` and `docs/ai.txt`) because GitHub raw URLs don't follow symlinks — keep them in sync.

## License

MIT — Copyright (C) 2015-2026 Mark Constable <mc@netserva.org>
