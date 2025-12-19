# SPE::03 Plugins

_Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)_

Chapter Three presents a paradox: the browser shows exactly the same interface as Chapter Two—the same navigation, the same card layouts, the same dark mode toggle and toast notifications—yet the PHP code behind it has been completely restructured. Where Chapter Two demonstrated a single self-rendering anonymous class, Chapter Three introduces four named classes that separate concerns into distinct responsibilities. This architectural shift produces identical output while establishing the foundation for extensible applications. The visual sameness is intentional; it isolates the structural changes so readers can focus entirely on how the code is organized rather than what it displays.

## The Same Surface, Different Depths

Loading Chapter Three in a browser reveals no visible changes from Chapter Two. The navigation links work identically, the theme toggle persists preferences to localStorage, the contact form opens the email client, and the toast buttons display their notifications. This visual continuity masks a fundamental reorganization. Chapter Two's anonymous class handled everything—routing, rendering, content—in a single construct-to-string lifecycle. Chapter Three distributes these responsibilities across a context container, an initialization dispatcher, an abstract plugin base, and concrete plugin implementations. The output remains constant because the architecture serves the same purpose; what changes is how that purpose is achieved and how easily the system can grow.

## The Context Container

The `Ctx` class serves as a readonly value object that holds the application's configuration and state containers. Its constructor uses PHP 8.4's constructor property promotion to declare four public properties with default values:

```php
readonly class Ctx {
    public function __construct(
        public string $email = 'mc@netserva.org',
        public array $in = ['o' => 'Home', 'm' => 'list', 'x' => ''],
        public array $out = ['doc' => 'SPE::03', 'nav' => '', 'head' => '', 'main' => '', 'foot' => ''],
        public array $nav = [['🏠 Home', 'Home'], ['📖 About', 'About'], ['✉️ Contact', 'Contact']]
    ) {}
}
```

The `$email` property provides contact information that plugins can access. The `$in` array defines the expected URL parameters with their defaults: `o` for the plugin/object name, `m` for the method to invoke, and `x` for the output format. The `$out` array provides placeholders for rendered content sections. The `$nav` array defines navigation entries as pairs of display label and plugin name. By making the class readonly, PHP guarantees these defaults cannot be modified after construction—the context is immutable once created.

The significance of `Ctx` lies in what it represents: a single point of truth for application configuration. Rather than scattering defaults across multiple locations or relying on global variables, all configuration lives in one place. When `new Ctx` is instantiated at the application entry point, it carries these defaults forward. The class itself does nothing; it simply exists as a container that other classes can read from.

## The Initialization Dispatcher

The `Init` class performs the actual work of processing requests and generating output. It accepts a `Ctx` instance through constructor injection, processes the incoming request parameters, dispatches to the appropriate plugin, and renders the final HTML or JSON response.

```php
readonly class Init {
    private array $in;
    private array $out;

    public function __construct(private Ctx $ctx) {
        $this->in = array_map(fn($k, $v) => ($_REQUEST[$k] ?? $v)
            |> trim(...)
            |> htmlspecialchars(...), array_keys($ctx->in), $ctx->in)
            |> (fn($v) => array_combine(array_keys($ctx->in), $v));
        $this->out = [...$ctx->out, 'main' => $this->dispatch()];
    }
```

The constructor's input processing demonstrates a more sophisticated use of the pipe operator than Chapter Two. The `array_map` call iterates over the keys and default values from `$ctx->in`, and for each parameter, retrieves the request value or falls back to the default, then pipes that through `trim(...)` and `htmlspecialchars(...)`. The result flows through another pipe to `array_combine`, which reconstructs an associative array with the original keys. This single expression sanitizes all URL parameters in one pass while preserving any that weren't provided in the request.

The constructor then spreads the context's output array and overwrites the `main` key with the result of `dispatch()`. This pattern—starting with defaults and selectively overwriting—appears throughout SPE as a way to merge configuration with computed values.

## Dynamic Plugin Dispatch

The `dispatch()` method embodies the plugin architecture's core mechanism. It extracts the plugin name and method from the processed input, validates that both exist, and invokes the requested method on a new plugin instance:

```php
private function dispatch(): string {
    [$o, $m] = [$this->in['o'], $this->in['m']];
    return match (true) {
        !class_exists($o) => '<p>Error: plugin not found</p>',
        !method_exists($o, $m) => '<p>Error: method not found</p>',
        default => (new $o($this->ctx))->$m()
    };
}
```

The `match (true)` pattern evaluates each arm's condition in order, returning the first match. If the class doesn't exist, an error message is returned. If the class exists but lacks the requested method, a different error appears. Only when both validations pass does the dispatch actually occur. The expression `(new $o($this->ctx))->$m()` instantiates the plugin class with the context, then immediately calls the method—all in one expression. This is PHP's variable class and method invocation: `$o` contains a string like `'Home'`, and `$m` contains a string like `'list'`, so the expression becomes effectively `(new Home($this->ctx))->list()`.

This dispatch mechanism means adding a new plugin requires only creating a new class. No routing tables, no configuration files, no registration code. If a class named `Blog` exists with a `list` method, the URL `?o=Blog&m=list` will invoke it. The simplicity is the point: the architecture scales by convention rather than configuration.

## Output Format Switching

The `__toString()` method determines whether to render HTML or JSON based on the `x` parameter:

```php
public function __toString(): string {
    return match ($this->in['x']) {
        'json' => (header('Content-Type: application/json') ?: '') . json_encode($this->out),
        default => $this->html()
    };
}
```

When `?x=json` appears in the URL, the response becomes a JSON object containing the output array. The expression `(header('Content-Type: application/json') ?: '')` sends the header and evaluates to an empty string (since `header()` returns null), allowing the JSON output to concatenate cleanly. This gives every plugin an automatic JSON API—the same dispatch mechanism that serves HTML pages also serves structured data. A JavaScript frontend could fetch `?o=Home&m=list&x=json` and receive the plugin's output as JSON without any additional server-side code.

## The HTML Template

The `html()` method generates the page structure, with navigation built using the same pipe operator pattern from Chapter Two:

```php
private function html(): string {
    $nav = $this->ctx->nav
        |> (fn($n) => array_map(fn($p) => sprintf(
            '<a href="?o=%s"%s>%s</a>',
            $p[1], $this->in['o'] === $p[1] ? ' class="active"' : '', $p[0]
        ), $n))
        |> (fn($a) => implode(' ', $a));
```

The navigation array from the context flows through a mapping function that generates anchor tags, then through implode to join them with spaces. The active class detection compares each plugin name against the current `o` parameter. The heredoc template that follows mirrors Chapter Two's structure exactly—container, header, nav, main, footer—because the visual output should be identical. The difference is that `{$this->out['main']}` now contains whatever the dispatched plugin returned, rather than content determined within the same class.

## The Plugin Base Class

The abstract `Plugin` class establishes the CRUDL contract that all plugins inherit:

```php
abstract class Plugin {
    public function __construct(protected Ctx $ctx) {}
    public function create(): string { return '<p>Create: not implemented</p>'; }
    public function read(): string { return '<p>Read: not implemented</p>'; }
    public function update(): string { return '<p>Update: not implemented</p>'; }
    public function delete(): string { return '<p>Delete: not implemented</p>'; }
    public function list(): string { return '<p>List: not implemented</p>'; }
}
```

CRUDL—Create, Read, Update, Delete, List—represents the five fundamental operations for managing data. By providing stub implementations that return "not implemented" messages, the base class allows plugins to override only the methods they need. A simple content plugin might only implement `list()`. A full data management plugin would override all five. The `protected Ctx $ctx` property gives every plugin access to the application context, including the email address, navigation structure, and any other configuration the context carries.

The abstract class cannot be instantiated directly—PHP enforces this—so it serves purely as a template for concrete plugins. This is classical object-oriented inheritance: define the interface in the parent, implement the specifics in the children.

## Concrete Plugin Implementations

Each page becomes a final class extending Plugin. The `Home` plugin demonstrates the basic pattern:

```php
final class Home extends Plugin {
    #[\Override] public function list(): string {
        return <<<'HTML'
        <div class="card">
            <h2>Home Page</h2>
            <p>Welcome to the <b>Plugins</b> example demonstrating the plugin architecture with CRUDL methods.</p>
        </div>
        <div class="flex justify-center mt-2">
            <button class="btn btn-success" onclick="showToast('Success!', 'success')">Success</button>
            <button class="btn btn-danger" onclick="showToast('Error!', 'danger')">Danger</button>
        </div>
        HTML;
    }
}
```

The `#[\Override]` attribute, introduced in PHP 8.3, tells the compiler this method intentionally overrides a parent method. If the parent's method signature changes or the method name is misspelled, PHP will raise an error rather than silently creating a new method. The `final` keyword prevents further subclassing—these plugins are leaf nodes in the inheritance tree.

The `Contact` plugin shows how plugins access context data:

```php
final class Contact extends Plugin {
    #[\Override] public function list(): string {
        return <<<HTML
        <div class="card">
            <h2>Contact Page</h2>
            <p>Get in touch using the <b>email form</b> below.</p>
            <form class="mt-2" onsubmit="return handleContact(this)">
                ...
            </form>
        </div>
        <script>
        function handleContact(form) {
            location.href = 'mailto:{$this->ctx->email}?subject=' + ...
        }
        </script>
        HTML;
    }
}
```

The heredoc uses `HTML` (without quotes) to enable variable interpolation, allowing `{$this->ctx->email}` to insert the email address from the context. This demonstrates why the context object exists: plugins can access shared configuration without hardcoding values or accepting constructor parameters beyond the context itself.

## The Entry Point

The application launches with a single expression that chains the entire request lifecycle:

```php
echo new Init(new Ctx);
```

This creates a context with default values, passes it to Init which processes the request and dispatches to a plugin, then echoes the result (triggering `__toString()`). The entire architecture—context, initialization, dispatch, plugin execution, rendering—executes from this one line. Compare this to Chapter Two's `echo new class {...}`: both are single expressions, but Chapter Three's expression orchestrates multiple classes working together.

## URL Parameter Reference

The plugin architecture introduces URL parameters that Chapter Two's static routing didn't need:

- `?o=PluginName` selects which plugin class to instantiate (default: `Home`)
- `?m=methodName` selects which CRUDL method to call (default: `list`)
- `?x=json` switches output from HTML to JSON (default: HTML)

These parameters combine freely: `?o=Contact&m=list&x=json` returns the Contact plugin's list output as JSON. The defaults mean that accessing the application with no parameters loads `Home::list()` as HTML, matching Chapter Two's behavior.

## Running the Application

Start the PHP development server from the project root to serve the shared CSS and JavaScript:

```bash
cd /path/to/spe
php -S localhost:8080
```

Navigate to `http://localhost:8080/03-Plugins/public/` to see the application. Click through the navigation to confirm it works identically to Chapter Two. Then examine the URLs—notice how `?o=About` and `?o=Contact` replace Chapter Two's `?m=about` and `?m=contact`. Add `&x=json` to any URL to see the JSON API output.

The visual experience is deliberately unchanged. What changes is the code's organization and its potential for growth. Chapter Two's anonymous class would require modification to add new pages. Chapter Three's plugin architecture requires only new classes—the dispatch mechanism discovers them automatically. This difference matters little for a three-page site but becomes essential as applications grow. The pattern established here carries forward through the remaining chapters, eventually supporting database-backed plugins, authenticated routes, and full content management.
