# SPE::04 Themes

_Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)_

Chapter Four continues the pattern established in Chapter Three: the application looks and behaves identically, but the internal architecture has evolved significantly. Where Chapter Three introduced plugins that returned HTML strings directly, Chapter Four separates data from presentation. Models return arrays of content, Views transform those arrays into HTML fragments, and a dedicated Theme class wraps everything in one of three switchable layouts. The user sees the same pages with the same navigation—plus a new dropdown menu for selecting themes—while the code demonstrates how a single request flows through distinct layers of responsibility.

## The Same Application, Deeper Separation

Clicking through Chapter Four reveals familiar territory: Home, About, and Contact pages with card layouts, toast notifications, and dark mode. The navigation now includes a dropdown labeled "Themes" that offers three layout options: Simple, TopNav, and SideBar. Selecting a different theme immediately restructures the page—navigation moves to a fixed top bar or a left sidebar—while the content remains unchanged. This visual demonstration of layout switching hints at the architectural change beneath: the content generation has been completely decoupled from the page structure.

Chapter Three's plugins did everything. A `Home` plugin's `list()` method returned a complete HTML string including the card wrapper, heading, and content. Chapter Four splits this responsibility. `HomeModel::list()` returns an array with `head` and `main` keys containing raw data. `HomeView::list()` receives that array and returns an HTML fragment. The `Theme` class then wraps that fragment in the selected layout. Three layers, three responsibilities, one output.

## The Extended Context

The `Ctx` class gains a new array for theme definitions alongside the existing navigation:

```php
readonly class Ctx {
    public array $in;
    public function __construct(
        public string $email = 'mc@netserva.org',
        array $in = ['o' => 'Home', 'm' => 'list', 't' => 'Simple', 'x' => ''],
        public array $out = ['doc' => 'SPE::04', 'head' => '', 'main' => '', 'foot' => ''],
        public array $nav = [['🏠 Home', 'Home'], ['📖 About', 'About'], ['✉️ Contact', 'Contact']],
        public array $themes = [['🎨 Simple', 'Simple'], ['🎨 TopNav', 'TopNav'], ['🎨 SideBar', 'SideBar']]
    ) {
        $this->in = array_map(fn($k, $v) => ($_REQUEST[$k] ?? $v)
            |> trim(...)
            |> htmlspecialchars(...), array_keys($in), $in)
            |> (fn($v) => array_combine(array_keys($in), $v));
    }
}
```

The `$in` array now includes a `t` parameter for theme selection, defaulting to `'Simple'`. The `$themes` array mirrors the structure of `$nav`: pairs of display label and theme class name. The input processing uses the same pipe operator pattern from Chapter Three, sanitizing all URL parameters in the constructor. By centralizing both navigation and theme definitions in the context, the application maintains a single source of truth for its menu structures.

## The Dispatch Chain

The `Init` class orchestrates a more sophisticated dispatch than Chapter Three's simple plugin invocation:

```php
public function __construct(private Ctx $ctx) {
    [$o, $m, $t] = [$ctx->in['o'], $ctx->in['m'], $ctx->in['t']];
    $model = "{$o}Model";
    $ary = class_exists($model) ? (new $model($ctx))->$m() : [];
    $view = "{$o}View";
    $main = class_exists($view) ? (new $view($ctx, $ary))->$m() : "<p>{$ary['main']}</p>";
    $this->out = [...$ctx->out, ...$ary, 'main' => $main];
}
```

The constructor extracts three parameters: `$o` for the plugin, `$m` for the method, and `$t` for the theme. It first attempts to instantiate a Model class (e.g., `HomeModel`) and call the requested method, receiving an array of data. If the model doesn't exist, an empty array is used. Next, it attempts to instantiate a View class (e.g., `HomeView`) with both the context and the model's data array, calling the same method to produce HTML. If no view exists, it wraps the model's `main` value in a paragraph tag as a fallback. The output array spreads both the context defaults and the model's returned data, then overwrites `main` with the rendered view content.

The `__toString()` method completes the chain by invoking the selected theme:

```php
public function __toString(): string {
    return match ($this->ctx->in['x']) {
        'json' => (header('Content-Type: application/json') ?: '') . json_encode($this->out),
        default => (new Theme($this->ctx, $this->out))->{$this->ctx->in['t']}()
    };
}
```

When JSON output is requested, the accumulated output array is returned directly. For HTML output, a new `Theme` instance receives the context and output, then the theme name from the `t` parameter is used as a method call. If `$t` is `'TopNav'`, the expression `(new Theme(...))->{'TopNav'}()` invokes the `TopNav()` method. This dynamic method invocation allows new themes to be added simply by creating new methods on the Theme class.

## Models Return Data

The Model classes inherit from the same abstract `Plugin` base as Chapter Three, but their methods return arrays instead of strings:

```php
final class HomeModel extends Plugin {
    #[\Override] public function list(): array {
        return ['head' => 'Home Page', 'main' => 'Welcome to the <b>Themes</b> example with multiple layout options.'];
    }
}
```

The returned array contains semantic data: a `head` value for the page title and a `main` value for the content. The model knows nothing about HTML structure, card wrappers, or CSS classes. It simply provides the information that the view will present. This separation means the same model data could be rendered by different views—a mobile view, a print view, an email template—without modifying the model itself.

## Views Render Fragments

View classes receive both the context and the model's data array, then produce HTML fragments:

```php
class View {
    public function __construct(protected Ctx $ctx, protected array $ary) {}

    public function list(): string {
        return <<<HTML
        <div class="card">
            <h2>{$this->ary['head']}</h2>
            <p>{$this->ary['main']}</p>
        </div>
        HTML;
    }
}
```

The base `View` class provides a default implementation that wraps the model data in a card. Specific views can override this to provide custom presentation. `HomeView` adds the toast demonstration buttons. `ContactView` replaces the simple paragraph with a form. The views access model data through `$this->ary` and context configuration through `$this->ctx`, maintaining clear boundaries between data sources.

The `ContactView` demonstrates accessing context data within a view:

```php
final class ContactView extends View {
    #[\Override] public function list(): string {
        return <<<HTML
        <div class="card">
            <h2>{$this->ary['head']}</h2>
            <p>{$this->ary['main']}</p>
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

The email address comes from the context, not the model. This distinction matters: the model provides page-specific content while the context provides application-wide configuration.

## The Theme Class

The `Theme` class consolidates all layout rendering into a single location with private helpers and public layout methods:

```php
final class Theme {
    public function __construct(private Ctx $ctx, private array $out) {}

    private function nav(): string {
        ['o' => $o, 't' => $t] = $this->ctx->in;
        return $this->ctx->nav
            |> (fn($n) => array_map(fn($p) => sprintf(
                '<a href="?o=%s&t=%s"%s>%s</a>',
                $p[1], $t, $o === $p[1] ? ' class="active"' : '', $p[0]
            ), $n))
            |> (fn($a) => implode(' ', $a));
    }

    private function dropdown(): string { ... }
    private function html(string $theme, string $body): string { ... }

    public function Simple(): string { ... }
    public function TopNav(): string { ... }
    public function SideBar(): string { ... }
}
```

The `nav()` method generates navigation links using the same pipe operator pattern from previous chapters, but now it preserves the current theme in each link's URL. When navigating between pages, the selected theme persists. The `dropdown()` method generates a hover-activated menu for theme selection, using CSS classes from the shared stylesheet rather than inline styles.

Each public method—`Simple()`, `TopNav()`, `SideBar()`—constructs a complete page layout. They call the private helpers for navigation, combine them with the content from `$this->out['main']`, and pass the result to `html()` for wrapping in the document structure. The layouts differ in their HTML structure: Simple uses a centered container, TopNav uses a fixed navigation bar, and SideBar splits the viewport into aside and main regions.

## The Dropdown Menu

The themes dropdown demonstrates CSS-driven interactivity without JavaScript:

```php
private function dropdown(): string {
    ['o' => $o, 't' => $t] = $this->ctx->in;
    $links = $this->ctx->themes
        |> (fn($n) => array_map(fn($p) => sprintf(
            '<a href="?o=%s&t=%s"%s>%s</a>',
            $o, $p[1], $t === $p[1] ? ' class="active"' : '', $p[0]
        ), $n))
        |> (fn($a) => implode('', $a));
    return "<div class=\"dropdown\"><span class=\"dropdown-toggle\">🎨 Themes</span><div class=\"dropdown-menu\">$links</div></div>";
}
```

The dropdown links preserve the current page (`$o`) while changing the theme (`$p[1]`). The CSS handles visibility: `.dropdown-menu` is hidden by default, revealed on `.dropdown:hover`. The active theme receives a highlight through the same conditional class pattern used for navigation. This pure CSS approach requires no JavaScript event handlers, keeping the interaction lightweight and accessible.

## URL Parameter Reference

Chapter Four adds the theme parameter to the existing set:

- `?o=PluginName` selects the Model and View classes (default: `Home`)
- `?m=methodName` selects which CRUDL method to call (default: `list`)
- `?t=ThemeName` selects the layout theme (default: `Simple`)
- `?x=json` switches output from HTML to JSON (default: HTML)

The parameters combine to produce URLs like `?o=Contact&t=SideBar`, which loads the Contact page in the SideBar layout. The JSON output includes all accumulated data: the model's returned values, the view's rendered HTML, and the context defaults.

## Running the Application

Start the PHP development server from the project root:

```bash
cd /path/to/spe
php -S localhost:8080
```

Navigate to `http://localhost:8080/04-Themes/public/` and interact with the themes dropdown. Notice how selecting TopNav moves the navigation to a fixed bar at the top. SideBar creates a two-column layout with grouped navigation on the left. Simple returns to the centered container layout. Click through pages to verify that the theme selection persists in the URL.

The Model/View/Theme separation established here carries significant implications for larger applications. Models can be tested independently since they return data structures rather than HTML. Views can be swapped for different presentation contexts—mobile, print, API responses with embedded HTML. Themes provide site-wide layout changes without touching individual page logic. Chapter Five will introduce PSR-4 autoloading to organize these growing class hierarchies into separate files and namespaces.
