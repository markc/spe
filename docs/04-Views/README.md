# SPE::04 Views

_Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)_

Chapter 04 splits each plugin into a **Model** that returns data and a **View** that renders HTML, and moves the whole-document rendering into a **Theme**. The screen is unchanged again, but this separation is what makes the rest of the series possible — and it is where SPE introduces the single most important habit it teaches: **input is validated, output is escaped**, with one escaping function, `View::e()`, applied to every dynamic value at the moment it becomes HTML.

## The one idea

**Model returns data, View returns HTML, Theme wraps it — and escaping happens at output.** A model deals in plain arrays; a view turns those arrays into HTML and escapes every value on the way; the theme is the only place that knows what a full page looks like.

## What's on the screen

The same three pages. The visible proof that something changed is on the Home page: the model's text deliberately contains `<b>tags</b>`, and you see the characters `<b>tags</b>` rather than bold text — because the view escaped it. That is the whole lesson made visible.

## Walkthrough

`Ctx` and `Init` are as in chapter 03, with one change in `Init`: it now resolves **two** classes per request and runs them in sequence.

```php
[$model, $view] = ["{$o}Model", "{$o}View"];
if (is_subclass_of($model, Plugin::class)) {
    $data = new $model($ctx)->$m();
} else {
    http_response_code(404);
    $data = ['title' => 'Not found', 'body' => 'There is no such plugin.'];
}
$view = is_a($view, View::class, true) ? $view : View::class;
$this->out = [...$ctx->out, ...$data, 'main' => new $view($ctx, $data)->$m()];
```

The model's method returns an array; the same-named view method turns that array into the `main` HTML. If a plugin has no dedicated view, `Init` falls back to the base `View`, which renders any `{title, body}` as a card. This is why `About` needs only a model — the base view is enough.

### Plugin (the model base)

```php
abstract class Plugin
{
    /** @return array{title: string, body: string} */
    public function list(): array { return $this->todo('list'); }
    /* create, read, update, delete … */
}
```

CRUDL methods now return **arrays**, not HTML. The `@return array{title: string, body: string}` annotation documents the shape so static analysis and readers know the keys without running the code.

### View (the HTML base)

```php
class View
{
    public function __construct(protected Ctx $ctx, protected array $data) {}
    public function list(): string { return $this->card(); }
    /* … */
    protected function e(string|int|float|null $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
```

`e()` is the one escaping function for the whole series. The flags matter: `ENT_QUOTES` escapes both single and double quotes (so a value is safe inside an attribute, not just between tags), `ENT_SUBSTITUTE` replaces malformed UTF-8 with a replacement character instead of returning an empty string, and `ENT_HTML5` uses the HTML5 entity set. Every dynamic value a view prints goes through `e()`; you can see it in `HomeView`:

```php
<h2>{$this->e($this->data['title'])}</h2>
<p>{$this->e($this->data['body'])}</p>
```

**Why escape at output and never at input?** Because the correct escaping depends on the destination. A value that is safe between `<p>` tags is not safe inside an `href`, a `<script>`, or a JSON document. Escaping at input has to guess where the value will end up; escaping at output *knows*. So models carry raw data and views escape it for the exact context they place it in. This rule is the reason the old version of this project had XSS holes and this one does not.

### Theme (the document)

`Theme` builds the shell — top bar, both sidebars, nav and scheme links via pipelines — and drops `$out['main']` into `<main>`. It is the only class that emits `<html>`, so there is exactly one place to change the page frame. Views never see the document; they only produce fragments.

Views use `#[\Override]` on each method and can declare `static` return types where a fluent API returns `$this`; the base classes stay open for extension (`class View`, `abstract class Plugin`) while the concrete plugins are `final`.

## PHP features introduced

- **`#[\Override]` (8.3)** across model and view methods — every override is declared and typo-proof.
- **`htmlspecialchars` with `ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5`** — one correct, attribute-safe escaper.
- **Array shape annotations (`@return array{...}`)** — the data contract between model and view is written down.
- **Base-class fallback** — a plugin without a view still renders through the base `View`.

## Security

This chapter establishes output escaping as a rule the rest of the series never breaks: every dynamic value passes through `View::e()` at the point of output, and models never escape. The test proves it — the model text containing `<b>tags</b>` appears as literal characters, and `?o=Nope` renders a 404 through the base view rather than erroring. The only values a view is ever allowed to print unescaped are HTML the application itself produced (which appears from chapter 09, where markdown output is named `$html` so the exception is visible).

## Try it

```bash
php -S localhost:8004 -t 04-Views/public
```

Look at the Home page source and find `&lt;b&gt;tags&lt;/b&gt;`. Try `?o=About` (base view), `?o=Contact&m=update` (unimplemented, names the model), and `?o=About&x=json` (model data as JSON).

## Next

Chapter 05 makes no visible or structural change at all: it takes these exact classes and moves them into separate files under `src/`, loaded by Composer through PSR-4 autoloading. It is the shortest diff in the series and the one that turns the single file into a real project layout.
