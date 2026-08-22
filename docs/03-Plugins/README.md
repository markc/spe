# SPE::03 Plugins

_Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)_

Chapter 03 looks identical to chapter 02 in the browser and is completely different underneath. The single anonymous class is gone; in its place are the three classes that shape every remaining chapter: `Ctx`, which reads and validates the request; `Init`, the front controller that turns a request into a page; and `Plugin`, the base class each page extends. This is where SPE stops being a script and becomes a tiny framework — and because the visible output is unchanged, you can give the new structure your whole attention.

## The one idea

**The request contract and the plugin.** From here on every request is described by a few validated query parameters — `o` (which plugin), `m` (which method), `x` (output format) — and every page is a `Plugin`: a class exposing the five CRUDL methods, of which it overrides only the ones it needs.

## What's on the screen

The same Home / About / Contact pages in the same shell. What changed is how a URL maps to content: `?o=Home` selects the Home plugin, `?m=list` selects its `list()` method, and `?x=json` returns the data as JSON instead of HTML. Every chapter from here shares this vocabulary.

## Walkthrough

### Ctx — the request

```php
final readonly class Ctx
{
    public array $in;
    public function __construct(
        public array $out = ['doc' => 'SPE::03', 'page' => '03 Plugins', 'main' => ''],
        public array $nav = [['home', 'Home', 'Home'], /* … */],
        /* … */
    ) {
        $this->in = [
            'o' => self::get('o', 'Home', '/^[A-Z][A-Za-z]{0,31}$/'),
            'm' => self::get('m', 'list', '/^(create|read|update|delete|list)$/'),
            'x' => self::get('x', '', '/^json$/'),
        ];
    }
```

`Ctx` is a **readonly class** (PHP 8.2): once constructed, nothing can change it, so the request is a fixed, trustworthy value for the rest of the cycle. Its most important job is validation. `get()` reads a query parameter and returns it **only if it matches an allow-list pattern**, otherwise the default:

```php
private static function get(string $key, string $default, string $pattern): string
{
    $v = $_GET[$key] ?? '';
    return is_string($v) && preg_match($pattern, $v) ? $v : $default;
}
```

So `o` must look like a class name, `m` must be one of the five CRUDL verbs, and `x` is either empty or `json`. Anything else silently becomes the default. This is the chapter's security foundation: the request is constrained to known-good shapes *before* any of it is used to choose a class or a method.

### Init — the front controller

```php
[$o, $m] = [$ctx->in['o'], $ctx->in['m']];
if (is_subclass_of($o, Plugin::class)) {
    $main = new $o($ctx)->$m();
} else {
    http_response_code(404);
    $main = '<div class="card"><h2>Not found</h2>…</div>';
}
```

`Init` resolves the plugin from `o` and calls the method named by `m`. Two PHP niceties appear here: `new $o($ctx)->$m()` uses **`new` without parentheses** (PHP 8.4) to construct and immediately call a method in one expression, and the guard is **`is_subclass_of($o, Plugin::class)`**, not merely `class_exists`. That distinction matters for safety: it means only real plugins are routable. `?o=Ctx`, `?o=Init` or `?o=Plugin` (the abstract base itself) all fail the check and return a 404 instead of letting the router instantiate an arbitrary class. Because `m` was already constrained to the five verbs, `$this->$m()` can never call anything but a CRUDL method.

`__toString()` then renders — either the full HTML page, or, when `?x=json` was requested, the `$out` array as JSON with the right `Content-Type`. Every chapter is therefore also a small JSON API, which is what lets the tests assert on data rather than scraping HTML.

### Plugin — the base class

```php
abstract class Plugin
{
    public function __construct(protected Ctx $ctx) {}
    public function create(): string { return $this->todo('create'); }
    public function read(): string   { return $this->todo('read'); }
    /* update, delete, list … */
}
```

`Plugin` defines the five CRUDL methods, each returning a "not implemented" card by default. A concrete plugin overrides only what it offers. `Home`, `About` and `Contact` each override `list()` with `#[\Override]` (PHP 8.3) — an attribute that tells PHP (and the reader) "this is deliberately replacing a parent method", so a typo in the method name becomes an error instead of a silently-new method.

## PHP features introduced

- **Readonly classes (8.2)** — `Ctx` is immutable, so the validated request cannot drift.
- **`new X()->method()` without parentheses (8.4)** — construct and dispatch in one expression.
- **Abstract classes and inheritance** — `Plugin` defines the CRUDL contract; pages fill it in.
- **`#[\Override]` (8.3)** — makes "I am replacing a parent method" explicit and typo-proof.
- **`match(true)` / guard-based dispatch** — routing decisions made from validated values only.

## Security

Two guarantees are established here and kept for the rest of the series. First, `o`, `m` and `x` are validated against allow-lists in `Ctx` before use, so a request can never name an arbitrary method or format. Second, dispatch uses `is_subclass_of(..., Plugin::class)`, so only classes designed to be pages can be instantiated from a URL — the core classes and any unknown name return a 404, never a fatal error or an unexpected object. Content is still static strings, so there is no escaping to do yet; that arrives with user-supplied data in chapter 04's rules and chapter 06's forms.

## Try it

```bash
php -S localhost:8003 -t 03-Plugins/public
```

Try `?o=Home&m=create` (an unimplemented method), `?o=Nope` and `?o=Ctx` (both 404), and `?o=About&x=json` (the data as JSON).

## Next

Chapter 04 splits each plugin in two: a **Model** that returns data and a **View** that renders HTML, with a **Theme** wrapping the page. That split is what lets the series introduce the single most important habit it teaches — escape every value at the moment it becomes HTML — which chapter 04 puts into `View::e()`.
