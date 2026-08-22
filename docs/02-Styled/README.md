# SPE::02 Styled

_Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)_

Chapter 02 makes the application look like an application. The PHP is still a single anonymous class with the same three pages, but the bare markup of chapter 01 is replaced by the **app shell** that every later chapter uses: a top bar, a left navigation sidebar, a right settings sidebar, light and dark themes, four colour schemes, and toast notifications. Crucially, all of that lives in three shared, non-PHP files — `base.css`, `site.css` and `base.js` — so from the next chapter onward the page can stay visually identical while the code behind it changes. This chapter is where the look is established and then frozen.

## The one idea

**Presentation is separate from logic, and shared.** The appearance of the whole series is defined once, in three files linked by every chapter, so that changes to structure (chapters 03–05) produce no visible change at all — which is exactly what lets you study them.

## What's on the screen

The same Home / About / Contact pages, now inside the shell: click the menu icons to open the sidebars, use the right sidebar to switch colour scheme or toggle dark mode (the choice persists in `localStorage`), and press the toast buttons on the Home page to see notifications.

## Walkthrough

The class is shaped like chapter 01's — a `DEFAULT`, a `PAGES` table, `public private(set)` properties, a pipe to validate `?o`, and `__toString()` to render — so the diff is entirely in what `__toString()` emits and in two small data tables.

```php
private const array PAGES = [
    'home'    => ['home', 'Home'],
    'about'   => ['book-open', 'About'],
    'contact' => ['mail', 'Contact'],
];
private const array SCHEMES = [
    'default' => ['circle', 'Stone'],
    'ocean'   => ['waves', 'Ocean'],
    /* … */
];
```

Each nav entry now carries a **Lucide icon name** instead of an emoji, and there is a table of colour schemes for the settings sidebar. Icons are rendered as `<i data-lucide="home"></i>`; the Lucide script (pinned to a fixed version, not `@latest`) swaps those placeholders for SVGs on load.

The `__toString()` method builds three things with pipelines — the nav links, the scheme links, and the page body — then drops them into the shell heredoc. The body is chosen with a **`match` expression** on the page:

```php
$this->main = match ($this->page) {
    'home' => $this->home(),
    'about' => $this->about(),
    'contact' => $this->contact(),
    default => '<div class="card"><h2>Not found</h2>…</div>',
};
```

`match` is strict (it compares with `===`) and exhaustive-by-intent (the `default` arm handles the 404 case, which also sets `http_response_code(404)` in the constructor). The three private methods return **nowdoc** (`<<<'HTML'`) or heredoc blocks of card markup — nowdoc where nothing is interpolated, heredoc where a value is.

The document `<head>` gains the pieces the shell needs: the two stylesheets, the Lucide script, and a tiny inline script that reads the saved theme and colour scheme from `localStorage` and sets them on `<html>` **before the body renders**, so there is no flash of the wrong theme.

### The shared assets

- **`base.css`** is the framework: layout (`.topnav`, `.sidebar`, `.container`), components (`.card`, `.btn`, `.tag`, `.toast`), and utilities. It is **colour-agnostic** — it never names a colour, only structural tokens — so it can be cached forever while the palette changes.
- **`site.css`** defines every colour, in the OKLCH colour space, for light and dark and for each scheme.
- **`base.js`** is a small controller (`Base`) that toggles theme and scheme, opens and pins the sidebars, and shows toasts; `Base.toast('Saved.', 'success')` is what the buttons call.

They are shared rather than copied because they are an *asset*, not a lesson. Each chapter links them by relative path (`../base.css`), which resolves when the project is served through the root router (`php -S localhost:8000 index.php`); that is the way to run any chapter from 02 on.

## PHP features introduced

- **`match` expression** — strict, value-returning branch selection for choosing the page body; safer than a `switch` with its fall-through and loose comparisons.
- **Heredoc vs nowdoc discipline** — `<<<HTML` when interpolating, `<<<'HTML'` when not; HTML is written as HTML, never concatenated.

## Security

The escaping story is unchanged from chapter 01: `?o` is validated and used only as an array key, and an unknown page is a 404 rendered inside the shell. No user input reaches the page. The inline theme script contains no user data. This chapter adds appearance, not new inputs, so it adds no new attack surface — a deliberate property of keeping "one idea per chapter".

## Try it

```bash
# From the repo root, so the shared base.css/site.css/base.js load:
php -S localhost:8000 index.php   # then open http://localhost:8000/02-Styled/
```

Open the sidebars, switch scheme and theme (reload — your choice sticks), and press the toast buttons.

## Next

Chapter 03 keeps this exact appearance but reorganises the PHP: it introduces `Ctx` (the request), `Init` (the front controller) and the `Plugin` base class, so that pages become self-contained plugins selected by a validated `?o`/`?m` request. Because the look does not change, you can concentrate entirely on the new structure.
