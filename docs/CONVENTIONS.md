# SPE Conventions

_The rules every chapter follows. If code and this document disagree, one of them is a bug._

SPE teaches plain, modern PHP 8.5 by building one small application nine times, each time adding exactly one idea. The code is kept as short as it honestly can be; the explanation lives in each chapter's README. A reader — human or AI agent — should be able to open any chapter, read every line, and know why it is there. This document is the contract that keeps the nine chapters consistent with each other.

## The ladder

| # | Chapter | The one idea it adds | PHP features it introduces |
|---|---------|----------------------|----------------------------|
| 01 | Simple | A request becomes a page: one self-rendering anonymous class | `\|>` pipe, typed constants, `public private(set)`, first-class callables `f(...)` |
| 02 | Styled | Presentation: shared CSS/JS, the app shell, dark mode, toasts | heredoc discipline, `match` |
| 03 | Plugins | The request contract (`o`, `m`, `x`) and the CRUDL plugin interface | `readonly` classes, `new X()->m()` without parentheses, abstract classes |
| 04 | Views | Model returns data, View returns HTML, Theme wraps it; escape at output | `#[\Override]`, `static` return types |
| 05 | Autoload | The same code as files: PSR-4, namespaces, Composer | `namespace`, `use`, `declare(strict_types=1)` everywhere |
| 06 | Session | State between requests: sessions, flash messages, CSRF, POST-only mutations | enums with methods, `clone()`-with on an immutable Ctx |
| 07 | PDO | Persistence: SQLite via PDO, a schema, prepared statements, a real CRUDL plugin | backed enums, `#[\NoDiscard]`, `match` on enum cases |
| 08 | Auth | Identity: users, password hashing, login/logout, roles, access control | enum methods as ACL, `?User` value object, `private(set)` session user |
| 09 | Blog | The application: content types, markdown, tags, pagination, self-hosted docs | property hooks, `array_first()`/`array_last()`, URI extension, the pipe as a render pipeline |

`00-Tutorial` is tooling for producing the video series; it is not a chapter and nothing depends on it.

## Non-negotiables

**One idea per chapter.** If a change is not part of the chapter's idea, it belongs in another chapter. Presentation changes belong in 02; there are no visual changes in 03, 04 or 05 — those chapters are deliberately invisible in the browser so the reader can concentrate on structure.

**Each chapter is a strict diff of the previous one.** `diff -r 05-Autoload 06-Session` must show only the Session idea. Files keep their names and positions across chapters; a class that exists in chapter N exists, with the same name and the same job, in every later chapter. Renaming is drift and drift is a bug.

**Each chapter is self-contained.** Everything a chapter runs lives under its own directory (`XX-Name/public`, `XX-Name/src`). There is no shared PHP library at the repository root, because shared code that changes later silently rewrites earlier chapters. Duplication between chapters is intentional: they are snapshots.

**Code is tight; docs are comprehensive.** Source files carry no comments that restate what the code does; a single `//` line is allowed where the *why* is not obvious. Every file, class, property and method is explained in the chapter README instead. When in doubt, shorten the code and lengthen the README.

**Same names, same shapes, everywhere.** The request is always `Ctx`. The front controller is always `Init`. Plugins are always `{Name}Model` and `{Name}View` under `Plugins/{Name}/`. Output is always the `$out` array with the same keys. A reader who learned the names in chapter 03 never has to relearn them.

## The request contract

From chapter 03 onward every request is described by four query parameters, read once, validated once, and then trusted:

| Param | Meaning | Validation | Default |
|-------|---------|------------|---------|
| `o` | **Object** — the plugin name | `/^[A-Z][A-Za-z]*$/` and the class must exist | `Home` |
| `m` | **Method** — one of the CRUDL verbs | `create`, `read`, `update`, `delete`, `list` | `list` |
| `i` | **Id** — the record the verb applies to | cast to `int` | `0` |
| `x` | **eXport** — alternative output format | `''` or `json` | `''` |

Chapters 01 and 02 have no plugins yet; they use only `o` to select the page.

Anything that changes state is a `POST`. The `Ctx::post()` method (chapter 06) returns the form data only when the request method is `POST` **and** the CSRF token in the form matches the one in the session; otherwise it returns `null`. Every mutating model method begins with `if ($p = $this->ctx->post())`. Deleting is a `POST` from a form with a button, never a `GET` link. Logging out is a `POST`.

`?x=json` returns the `$out` array as JSON with the correct content type. It exists so that every chapter is also an API and so that tests can assert on data rather than scraping HTML.

## Core classes

These are the only framework classes. Each appears in the chapter listed and keeps its role thereafter.

| Class | Since | Responsibility |
|-------|-------|----------------|
| `Ctx` | 03 | The request: validated `$in`, default `$out`, navigation, configuration. Immutable after construction. Gains the session, flash and `post()` in 06, the database handle in 07, the current user in 08. |
| `Init` | 03 | The front controller: resolves `{o}Model`/`{o}View`, calls `$m` on each, merges the result into `$out`, renders through `Theme` or as JSON. `echo new Init(new Ctx)` is the entire entry point. |
| `Plugin` | 03 | Abstract base for models. Declares the five CRUDL methods; each returns a "not implemented" array so a plugin overrides only what it needs. |
| `View` | 04 | Base for views. Declares the five CRUDL methods returning HTML strings and owns `e()`, the one escaping function. |
| `Theme` | 04 | Turns `$out` into the full HTML document: app shell, navigation, flash toasts. The only place the `<html>` document exists. |
| `Db` | 07 | A thin `PDO` subclass: `create`, `read`, `update`, `delete`, `qry`, all prepared, typed binding. |
| `QueryType` | 07 | Backed enum `All`, `One`, `Col` selecting the fetch shape. |
| `Role` | 08 | Backed enum `Anon`, `User`, `Admin` with `can()`; the whole access-control system. |
| `User` | 08 | Readonly value object for the signed-in user. |
| `Type` | 09 | Backed enum `Page`, `Post`, `Doc`; one `posts` table, three kinds of content, one plugin. |
| `Md` | 09 | Markdown to HTML as a pipe of small pure functions. |

Plugins are the application. The final chapter has four: `Auth`, `Posts`, `Tags`, `Users`. Each is a directory `Plugins/{Name}/` containing `{Name}Model.php` and `{Name}View.php`, nothing else.

## Input is validated, output is escaped

This is the single most important habit SPE teaches, and earlier versions of this project got it wrong. Input is never escaped. `Ctx` validates each parameter against what it is allowed to be (a whitelist, a pattern, an integer cast) and stores the typed result. Models work with raw data: raw strings from the request, raw strings from the database. Views escape every dynamic value at the moment it is placed into HTML, using `$this->e($value)`, which is `htmlspecialchars()` with `ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5` and UTF-8. The only values a view may place unescaped are ones the view itself produced or HTML that the application deliberately generated from markdown (chapter 09), and those are named `$html` so the exception is visible.

The reason is that escaping depends on the destination. A value that is safe inside an HTML element is not safe inside an attribute, a URL or a JSON document. Escaping at input guesses the destination; escaping at output knows it.

## Security baseline

Each chapter keeps every guarantee of the chapters before it.

- **03**: `o` and `m` are validated before they are used to name a class or a method; unknown values render an error page, they never reach `new $o` or `->$m()` unchecked.
- **04**: all dynamic output passes through `View::e()`.
- **06**: sessions use `cookie_httponly`, `cookie_samesite=Lax`, `use_strict_mode`; every form carries a CSRF token; `Ctx::post()` is the only way to read `$_POST`; state changes only on `POST`.
- **07**: every SQL statement is prepared; values are bound with their PHP type; table and column names never come from the request.
- **08**: passwords are hashed with `password_hash(PASSWORD_DEFAULT)` and checked with `password_verify()`; `session_regenerate_id(true)` on login and logout; access is checked in `Init` before the plugin runs, from the `Role` enum; the remember-me cookie stores a random selector plus a hashed validator, rotated on use, sent `Secure` when the request is HTTPS.
- **09**: markdown output is sanitised — link and image destinations must be `http`, `https`, `mailto` or relative; slugs are generated by the application, never taken from the request.

No chapter ever shells out, reads a filesystem path from the request, or trusts `HTTP_HOST`.

## Code style

Every PHP file begins with exactly these two lines:

```php
<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
```

Classes are `final` unless they are designed to be extended (`Plugin`, `View`) and `readonly` unless they must hold mutable state. Constructor property promotion is the default way to declare properties. Return types and parameter types are always declared. `array` return types carry a one-line `@return array{...}` shape annotation when the keys matter.

HTML is written in heredocs. The opening `<<<HTML` ends the line; the closing `HTML;` sits in column one of its own line; dynamic values are interpolated as `{$this->e($x)}`. Use `<<<'HTML'` (nowdoc) when nothing is interpolated. Never concatenate HTML with `.`.

Pipes `|>` are used where data genuinely flows through a sequence of single-argument steps. They are never used to make a two-step expression look clever. Closures in a pipe are wrapped in parentheses: `|> (fn($x) => ...)`.

Icons are Lucide, rendered as `<i data-lucide="name"></i>`, from a pinned version of the library. Emoji are not used in application code.

Navigation, colour schemes and other configuration live in `Ctx` as plain arrays of tuples, in the same order they appear on screen.

## Assets

`docs/base.css`, `docs/site.css` and `docs/base.js` are the only shared files and they are not PHP. `base.css` is colour-agnostic structure; `site.css` is every colour. Chapters link them by relative path (`../base.css`) so each chapter also works when served on its own with `php -S localhost:8000 -t XX-Name/public`. They are shared because they are an asset, not code, and because the whole point of chapters 03–05 is that the page does not change.

## Documentation contract

Each chapter's README (`docs/XX-Name/README.md`, symlinked from `XX-Name/README.md`) has these sections in this order:

1. **Opening paragraph** — what the chapter adds and why it comes here in the ladder.
2. **What changed** — a short table of files added, changed and removed relative to the previous chapter. `diff -rq` between the two directories must agree with it.
3. **Walkthrough** — prose, in request order, covering every file, class, property and method. Quote the actual code being discussed.
4. **PHP features introduced** — each one: what it is, which version, why it is the natural tool here, what the code would look like without it.
5. **Security** — the guarantees added in this chapter and how to verify them.
6. **Try it** — how to run the chapter, URLs to visit, a `?x=json` example.
7. **Next** — one paragraph on what the following chapter changes and why this chapter made it possible.

The prose style is described in `.claude/runbooks/documentation-style-guide.md`: paragraphs over bullets, explain why before what, no "simply" or "obviously".

`chapters.json` at the repository root is the machine-readable manifest: one entry per chapter with its directory, idea, the files it adds, the PHP features it introduces, how to run it and how to test it. `bin/check-chapters.php` verifies that the manifest, the directories, the READMEs and the chapter tables in the root `index.php` and `docs/README.md` all agree, and CI runs it.

## Tests

Every chapter has a Pest suite under `tests/XX-Name/`. Tests start the chapter with PHP's built-in server and talk to it over HTTP, so chapters never share a process and class names never collide. Each suite asserts at least: the default page renders; each navigation target renders; unknown `o`/`m` values produce the error page and not a PHP error; `?x=json` returns valid JSON with the expected keys; and, from chapter 06, that a `POST` without a valid token is rejected. Later chapters add tests for their own idea (login, CRUD round-trips, pagination).

## How to add a chapter or change one

Change the earliest chapter where the idea belongs, then carry the identical change forward through every later chapter so the diffs stay clean. Update that chapter's README sections 2–5. Update `chapters.json`. Run `composer check`. If a change needs a new class name or a new request parameter, stop: that is a new idea and probably a new chapter.
