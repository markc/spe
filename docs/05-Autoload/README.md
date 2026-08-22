# SPE::05 Autoload

_Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)_

Chapter 05 is the shortest step in the series and, for a growing project, one of the most consequential. It takes the classes that chapter 04 kept in one file and puts each in its own file under `src/`, organised by namespace, loaded automatically by Composer. Nothing about the behaviour, the output, or the class code changes — the diff is almost entirely "the same class, now in a file whose path matches its name". This is the moment SPE stops being a single script and becomes a project laid out the way every serious PHP codebase is.

## The one idea

**The same code as files, found automatically.** PSR-4 autoloading maps a namespace to a directory, so `SPE\Autoload\Core\Init` lives at `05-Autoload/src/Core/Init.php` and is loaded the first time it is used — no `require` statements to write or maintain.

## What's on the screen

Nothing new. Home, About and Contact render exactly as in chapter 04. The test for this chapter asserts precisely that: the split into files changes nothing a visitor can see.

## Walkthrough

### The entry point shrinks

```php
require_once __DIR__ . '/../../vendor/autoload.php';

use SPE\Autoload\Core\{Ctx, Init};

echo new Init(new Ctx);
```

The whole of `public/index.php` is now three lines: pull in Composer's autoloader, name the two classes it needs, and run. There is not a single `require` for application code — Composer resolves each class on demand.

### The namespace-to-path mapping

The root `composer.json` declares the map:

```json
"autoload": {
    "psr-4": {
        "SPE\\Autoload\\": "05-Autoload/src",
        …
    }
}
```

That one line means every class under the `SPE\Autoload\` namespace is found under `05-Autoload/src/`, following the folder structure exactly: `SPE\Autoload\Core\Ctx` → `src/Core/Ctx.php`, `SPE\Autoload\Plugins\Home\HomeModel` → `src/Plugins/Home/HomeModel.php`. This is the **PSR-4** standard, and it is why the directory layout and the class names have to agree — a discipline the `composer dump-autoload --strict-psr` check in CI enforces.

### The classes gain a namespace and a use statement

Each class is the chapter-04 class with two additions: a `namespace SPE\Autoload\Core;` (or `…\Plugins\Home;`) line, and `use` statements for the classes it references. `Init` now resolves plugins by fully-qualified name:

```php
private const string PLUGINS = 'SPE\\Autoload\\Plugins\\';
[$model, $view] = [self::PLUGINS . "$o\\{$o}Model", self::PLUGINS . "$o\\{$o}View"];
```

The routing logic is identical to chapter 04 — validate, guard with `is_subclass_of`, dispatch, fall back to the base `View` — only the class names it builds are now namespaced. The plugins move from `src/Plugins/{Name}/` as `{Name}Model.php` and `{Name}View.php`, exactly the layout the rest of the series uses.

Every file opens with `declare(strict_types=1);`, which from now on applies to every file in the project (CI checks for it). Strict types means a function that declares `int` receives an `int` or throws, never a silently-coerced string — the errors surface at the boundary instead of deep inside.

## PHP features introduced

- **Namespaces and `use`** — classes are addressed by a logical name, not a file path, and grouped by responsibility (`Core`, `Plugins\Home`).
- **PSR-4 autoloading via Composer** — the namespace-to-directory convention that loads classes on demand; no manual `require`.
- **`declare(strict_types=1)` everywhere** — type declarations are enforced, so type errors happen at the call, not later.

## Security

No behaviour changes, so the guarantees of chapters 03–04 carry over untouched: the request is validated in `Ctx`, dispatch is guarded by `is_subclass_of`, and output is escaped by `View::e()`. One small hardening is inherent in namespacing: `Init` builds plugin class names under a fixed `SPE\Autoload\Plugins\` prefix, so even the class-name string it constructs is confined to the plugins namespace.

## Try it

```bash
composer install          # once, from the repo root
php -S localhost:8005 -t 05-Autoload/public
```

Confirm the pages are identical to chapter 04. Then compare the code: `diff` the class bodies of `04-Views/public/index.php` against the files in `05-Autoload/src/` and you will find only the namespace lines, the `use` statements, and the version label differ.

## Next

Chapter 06 adds the first thing that persists between requests: sessions. With sessions come flash messages, CSRF tokens, and the rule that anything changing state must be a POST — the machinery a real form needs, applied to a Contact form that finally submits to the server.
