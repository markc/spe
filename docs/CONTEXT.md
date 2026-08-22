# SPE Project Context

A progressive PHP 8.5 tutorial in nine chapters. MIT licensed, authored by Mark Constable. This file orients a reader (human or AI agent) quickly; the authoritative rules are in [CONVENTIONS.md](CONVENTIONS.md) and the machine-readable manifest is `chapters.json`.

## What this is

SPE builds one small web application nine times, each chapter adding exactly one idea, to teach plain modern PHP — a framework-free alternative to Laravel/Symfony for small-to-moderate projects. Each chapter is a strict diff of the previous one and runs on its own.

## Technology

PHP 8.5+ (pipe operator `|>`), SQLite via PDO (chapters 07+), Composer for PSR-4 autoloading (05+), custom CSS (`base.css` + `site.css`, OKLCH, no Bootstrap), a small vanilla-JS controller (`base.js`) for theme, sidebars and toasts. Dev tools: Pest (tests) and Mago (lint/format).

## The request contract (chapters 03+)

```
?o=Blog&m=read&i=1&x=json
o = plugin (validated /^[A-Z][A-Za-z]*$/, must be a Plugin subclass)
m = method (create | read | update | delete | list)
i = integer id
x = '' (HTML) | json
```
Chapter 09 also validates `page` (int) and `tag` (slug). Chapters 01–02 use only `o` to pick a page.

## Request flow

```
public/index.php → new Init(new Ctx)
Ctx  validates the request (opens DB / restores user in later chapters)
Init resolves {o}Model, calls ->{m}(), hands the data to {o}View->{m}()
Theme wraps it in the document; or Init emits JSON when x=json
```

## Chapter progression

| # | Folder | Idea | Key additions |
|---|--------|------|---------------|
| 01 | 01-Simple | request → page | anonymous class, pipe, `private(set)`, typed const |
| 02 | 02-Styled | presentation | app shell, dark mode, schemes, toasts, `match` |
| 03 | 03-Plugins | request contract + plugins | `Ctx`/`Init`/`Plugin`, readonly, `#[\Override]` |
| 04 | 04-Views | model/view/theme, escape at output | `View::e()`, base-view fallback |
| 05 | 05-Autoload | PSR-4 files | namespaces, Composer, `strict_types` |
| 06 | 06-Session | sessions, flash, CSRF, POST-only | `Flash` enum, secure cookies, PRG |
| 07 | 07-PDO | database | `Db`, `QueryType`, prepared statements, `#[\NoDiscard]`, Posts CRUDL |
| 08 | 08-Auth | identity | `Role` enum ACL, `User`, login/logout, remember-me, Users CRUDL |
| 09 | 09-Blog | content engine | `Type`, `Md`, `Post` (property hooks), tags, pagination, self-hosted docs |

`00-Tutorial` is the video-generation tooling and the per-chapter narration/artefacts; it is not a chapter.

## Directory structure (05–09)

```
XX-Chapter/
├── public/index.php        # require ../../vendor/autoload.php; echo new Init(new Ctx);
├── src/Core/               # Ctx, Init, Plugin, View, Theme (+ Db, enums, value objects as introduced)
├── src/Plugins/{Name}/     # {Name}Model.php (data), {Name}View.php (HTML)
├── schema.sql              # created + seeded on first run (07+)
└── data/                   # SQLite file, gitignored
```
Chapters 01–04 are a single `public/index.php` each.

## Conventions in brief

- One idea per chapter; each chapter a strict diff of the previous; each self-contained (no shared root PHP library).
- Names are stable: the request is always `Ctx`, the front controller always `Init`, plugins always `{Name}Model`/`{Name}View`.
- **Validate input** (allow-list patterns in `Ctx`), **escape output** (`View::e()` = `htmlspecialchars` with `ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5`); models carry raw data.
- Writes only on a CSRF-checked POST via `Ctx::post()`; delete and logout are POST; SQL is always prepared; passwords hashed; session id regenerated on login/logout.
- Tight code, comprehensive docs: source carries almost no comments; the explanation lives in each chapter's README.
- Icons are Lucide (pinned version); HTML is written in heredocs, never concatenated.

## Tooling

- `composer check` → `bin/check-chapters.php` (manifest vs tree) + `mago lint` + `pest`.
- Tests live in `tests/<Name>/` and drive each chapter over HTTP on its own built-in server.
- CI (`.github/workflows/ci.yml`) runs on PHP 8.5: syntax-checks every tracked file, checks `strict_types`, the manifest, mago and Pest.

## Documentation strategy

`docs/` is the GitHub Pages source and single source of truth; the root `README.md` and each `XX-Chapter/README.md` are symlinks into it. Each chapter README follows a fixed section order (opening · what changed · walkthrough · PHP features · security · try it · next), described in `.claude/runbooks/documentation-style-guide.md`.

## Links

- Site: https://markc.github.io/spe/
- Repo: https://github.com/markc/spe
