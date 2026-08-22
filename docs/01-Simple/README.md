# SPE::01 Simple

_Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)_

The first chapter is a complete web page produced by a single PHP statement. There is no framework, no configuration, no autoloader — one file, `01-Simple/public/index.php`, that turns a request into an HTML document and prints it. Everything the rest of the series builds is already implied here in miniature: a request selects a page, some code decides what that page contains, and the result is rendered as HTML. Starting this small means every later chapter can be understood as one deliberate addition to something you already grasp completely.

## The one idea

**A request becomes a page.** The browser asks for a page by name in the query string (`?o=about`); the code validates that name, looks up the content, and returns a whole HTML document. That request-to-response cycle is the spine of every chapter that follows.

## What's on the screen

Three pages — Home, About, Contact — with a row of navigation links and the current page marked active. It is intentionally plain: no CSS, no JavaScript, just enough HTML to see the structure. Chapter 02 adds the styling; here the point is the PHP.

## Walkthrough

The entire program is `echo new class { ... };`. PHP instantiates an anonymous class and hands it to `echo`, which triggers the class's `__toString()` method. So the object's whole job is to be born knowing which page was requested, and to render itself when printed.

```php
private const array PAGES = [
    'home'    => ['Home', '<h2>Home</h2><p>This is the <b>Home</b> page.</p>'],
    'about'   => ['About', '<h2>About</h2><p>This is the <b>About</b> page.</p>'],
    'contact' => ['Contact', '<h2>Contact</h2><p>This is the <b>Contact</b> page.</p>'],
];
```

`PAGES` is a **typed class constant** (`private const array`, from PHP 8.3). Each entry maps a page key to a `[title, html]` pair. Because it is a constant, it is fixed at parse time and shared by every request — there is no per-object copy and nothing can reassign it.

```php
public private(set) string $page;
public private(set) string $main;
```

These two properties use **asymmetric visibility** (`public private(set)`, from PHP 8.4): any code may read `$page`, but only code inside the class may write it. This is exactly the guarantee you want for a value that is computed once from the request and must not be tampered with afterwards. In one keyword it replaces the usual private-property-plus-getter boilerplate.

The constructor works out which page was asked for:

```php
$o = $_GET['o'] ?? '';
$this->page = (is_string($o) ? $o : '')
    |> trim(...)
    |> strtolower(...)
    |> (static fn(string $p) => $p === '' ? self::DEFAULT : $p);
```

This is the **pipe operator** (`|>`, from PHP 8.5) with **first-class callables** (`trim(...)`, from PHP 8.1). Read it top to bottom as a pipeline: take the raw input, trim it, lower-case it, and if what remains is empty fall back to the default page. The value flows left to right through each step, which is the order the operations actually happen. Compare the nested equivalent, which you have to read inside-out:

```php
$p = strtolower(trim(is_string($o) ? $o : ''));
$this->page = $p === '' ? self::DEFAULT : $p;
```

The pipe version says the same thing in the order a human thinks it. The `is_string` guard matters: `?o[]=x` makes `$_GET['o']` an array, and passing an array to `trim()` would be an error, so a non-string request quietly becomes the default.

Then the constructor resolves the content and sets the HTTP status:

```php
if (!isset(self::PAGES[$this->page])) {
    http_response_code(404);
}
$this->main = self::PAGES[$this->page][1] ?? '<h2>Not found</h2>...';
```

An unknown page is a genuine `404`, not a `200` that happens to say "not found" — the status line tells the truth, which matters for browsers, crawlers and tests alike.

`__toString()` builds the navigation and returns the document. The navigation is another pipeline: take the page keys, map each to an `<a>` tag (marking the current one active), and join them. The document itself is a **heredoc** — a multi-line string with `{$this->main}` and `{$nav}` interpolated in place. No string concatenation, no template engine; the HTML reads as HTML.

## PHP features introduced

- **Pipe operator `|>` (8.5)** — chains single-argument steps so a transformation reads in the order it happens. Used for input cleaning and building the nav.
- **First-class callable syntax `trim(...)` (8.1)** — refers to a function as a value so it can be a pipe step, without wrapping it in a closure.
- **Typed class constants `const array` (8.3)** — the page table is fixed and type-checked.
- **Asymmetric visibility `public private(set)` (8.4)** — a property the world can read but only the class can set, with no getter boilerplate.
- **Anonymous class + `__toString()`** — the smallest possible "render an object to a page" pattern.

## Security

The only untrusted input is `?o`. It is validated (must be a string) and normalised, and it is only ever used as an **array key** into `PAGES`, never printed back into the page or used to build a path. That is why the XSS attempt in the tests (`?o=<script>…`) produces a clean 404 with the script nowhere in the output: an unknown key simply has no entry. This is the seed of the rule the whole series follows — decide what input is allowed to be, and never hand it to the browser unescaped.

## Try it

```bash
php -S localhost:8001 -t 01-Simple/public
```

Visit `http://localhost:8001/`, then `?o=about` and `?o=contact`. Try `?o=nope` and watch for the 404 (`curl -I` shows the status line).

## Next

Chapter 02 changes nothing about this logic. It moves the presentation into shared `base.css`, `site.css` and `base.js`, wraps the pages in the application shell used for the rest of the series, and adds dark mode and toast notifications — so that from chapter 03 on, the look stays fixed and you can watch the architecture change underneath it.
