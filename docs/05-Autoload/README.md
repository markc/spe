# SPE::05 Autoload

_Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)_

Chapter Five presents a paradox: the application looks and behaves exactly like Chapter Four, yet the underlying code has undergone a fundamental transformation. Where Chapter Four packed all its logic into a single `index.php` file, Chapter Five distributes that same logic across fourteen separate files organized into a proper namespace hierarchy. The user experience remains unchanged—same pages, same themes, same interactions—but the codebase has evolved from a monolithic script into a structured project suitable for real-world development. This chapter demonstrates PSR-4 autoloading through Composer, the industry-standard approach to organizing PHP applications.

## From One File to Fourteen

The contrast between chapters tells the story. Chapter Four's `index.php` contained roughly 150 lines defining the context, initialization, plugins, views, and themes. Chapter Five's `index.php` contains exactly three meaningful lines:

```php
require_once __DIR__ . '/../../vendor/autoload.php';

use SPE\Autoload\Core\{Init, Ctx};

echo new Init(new Ctx);
```

The first line loads Composer's generated autoloader from the project root. The second imports the two classes needed for bootstrapping. The third instantiates the application and echoes its output. Every other line of code—context configuration, dispatch logic, plugin base classes, view rendering, theme layouts—now lives in dedicated files under the `src/` directory. The application's behavior is identical, but finding and modifying any specific piece of functionality now means opening a focused, single-purpose file.

## The Directory Structure

The file organization follows PSR-4 conventions, mapping namespace segments to directory paths:

```
05-Autoload/src/
├── Core
│   ├── Ctx.php
│   ├── Init.php
│   ├── Plugin.php
│   ├── Theme.php
│   └── View.php
├── Plugins
│   ├── About
│   │   ├── AboutModel.php
│   │   └── AboutView.php
│   ├── Contact
│   │   ├── ContactModel.php
│   │   └── ContactView.php
│   └── Home
│       ├── HomeModel.php
│       └── HomeView.php
└── Themes
    ├── SideBar.php
    ├── Simple.php
    └── TopNav.php
```

The `Core/` directory contains the framework infrastructure: context management, dispatch logic, and abstract base classes. The `Plugins/` directory groups each plugin's model and view into their own subdirectory. The `Themes/` directory holds the three layout implementations as separate classes. This organization means that adding a new plugin requires creating a new subdirectory with two files, and adding a new theme requires creating a single file—no modification to existing code necessary.

## PSR-4 Namespace Mapping

The project's `composer.json` defines the namespace-to-directory mapping:

```json
{
    "autoload": {
        "psr-4": {
            "SPE\\Autoload\\": "05-Autoload/src/"
        }
    }
}
```

This single line instructs Composer's autoloader that any class beginning with `SPE\Autoload\` should be found in the `05-Autoload/src/` directory. The remaining namespace segments map directly to subdirectories and filenames. `SPE\Autoload\Core\Init` lives in `05-Autoload/src/Core/Init.php`. `SPE\Autoload\Plugins\Home\HomeModel` lives in `05-Autoload/src/Plugins/Home/HomeModel.php`. `SPE\Autoload\Themes\TopNav` lives in `05-Autoload/src/Themes/TopNav.php`. The pattern is predictable and universal across the PHP ecosystem.

When PHP encounters a class it hasn't loaded, the autoloader transforms the fully-qualified class name into a file path and includes it automatically. No manual `require` statements scattered throughout the codebase, no worrying about include order, no accidentally loading the same file twice. The developer declares classes with their proper namespaces, Composer handles the rest.

## Fully Qualified Class Names in Init

The `Init` class demonstrates how autoloaded applications construct class names dynamically:

```php
final readonly class Init {
    private const string NS = 'SPE\\Autoload\\';
    private array $out;

    public function __construct(private Ctx $ctx) {
        [$o, $m, $t] = [$ctx->in['o'], $ctx->in['m'], $ctx->in['t']];

        $model = self::NS . "Plugins\\{$o}\\{$o}Model";
        $ary = class_exists($model) ? (new $model($ctx))->$m() : [];

        $view = self::NS . "Plugins\\{$o}\\{$o}View";
        $main = class_exists($view) ? (new $view($ctx, $ary))->$m() : "<p>{$ary['main']}</p>";

        $this->out = [...$ctx->out, ...$ary, 'main' => $main];
    }
}
```

The namespace prefix is stored as a typed constant, ensuring the base namespace appears in exactly one place. When the URL parameter `o=Home` arrives, the code constructs `SPE\Autoload\Plugins\Home\HomeModel` as the fully-qualified class name. The `class_exists()` check triggers the autoloader, which translates this to `05-Autoload/src/Plugins/Home/HomeModel.php` and includes the file. The same pattern applies to views and themes—string concatenation builds class names, the autoloader resolves them to files.

The `__toString()` method follows the same approach for themes:

```php
public function __toString(): string {
    $t = $this->ctx->in['t'];
    $theme = self::NS . "Themes\\{$t}";
    return match ($this->ctx->in['x']) {
        'json' => (header('Content-Type: application/json') ?: '') . json_encode($this->out),
        default => (new $theme($this->ctx, $this->out))->render()
    };
}
```

When `t=TopNav`, the code constructs `SPE\Autoload\Themes\TopNav`, and the autoloader finds `05-Autoload/src/Themes/TopNav.php`. Adding a fourth theme means creating `src/Themes/NewTheme.php` with the appropriate class—no modifications to Init required.

## Abstract Base Classes

Chapter Four embedded all theme layouts as methods in a single Theme class. Chapter Five separates the shared functionality into an abstract base class that concrete themes extend:

```php
abstract class Theme {
    public function __construct(protected Ctx $ctx, protected array $out) {}

    abstract public function render(): string;

    protected function nav(): string { ... }
    protected function dropdown(): string { ... }
    protected function html(string $theme, string $body): string { ... }
}
```

The base `Theme` class provides the `nav()` method for generating navigation links, `dropdown()` for the theme selector menu, and `html()` for wrapping content in the document structure. The `render()` method is declared abstract, forcing each concrete theme to implement its own layout. The `Simple`, `TopNav`, and `SideBar` classes each extend this base and override `render()` with their specific HTML structure.

The same pattern applies to plugins and views. The abstract `Plugin` class provides CRUDL method stubs that return default arrays. The base `View` class provides a default `list()` implementation. Concrete implementations override only what they need:

```php
final class HomeModel extends Plugin {
    #[\Override] public function list(): array {
        return ['head' => 'Home Page', 'main' => 'Welcome to the <b>Autoload</b> example...'];
    }
}
```

The `#[\Override]` attribute, introduced in PHP 8.3, documents intent and triggers errors if the parent method doesn't exist. Each class file is focused and brief—HomeModel is twelve lines including the license header.

## Theme Inheritance in Practice

Each theme class lives in its own file under `src/Themes/` and extends the abstract base:

```php
namespace SPE\Autoload\Themes;

use SPE\Autoload\Core\Theme;

final class Simple extends Theme {
    #[\Override] public function render(): string {
        $nav = $this->nav();
        $dd = $this->dropdown();
        $body = <<<HTML
<div class="container">
    <header><h1><a href="/">« Autoload PHP Example</a></h1></header>
    <nav class="card flex">
        $nav $dd
        <span class="ml-auto"><button class="theme-toggle" id="theme-icon">🌙</button></span>
    </nav>
    <main>{$this->out['main']}</main>
    <footer class="text-center mt-3"><small>© 2015-2026 Mark Constable (MIT License)</small></footer>
</div>
HTML;
        return $this->html('Simple', $body);
    }
}
```

The namespace declaration places this class in `SPE\Autoload\Themes`. The `use` statement imports the parent class from `SPE\Autoload\Core`. The `render()` method calls the inherited helper methods and composes the layout. This file is entirely self-contained—understanding Simple requires no knowledge of TopNav or SideBar. Modifying Simple's layout cannot accidentally break the other themes.

## The Plugin Directory Convention

Plugins follow a specific directory structure: each plugin gets a subdirectory containing its model and view files. The Home plugin lives in `src/Plugins/Home/` with `HomeModel.php` and `HomeView.php`. This convention enables the dynamic class name construction in Init—given `o=Home`, the code knows to look for `HomeModel` and `HomeView` in the `Home` subdirectory.

The namespace for plugin classes includes the plugin name: `SPE\Autoload\Plugins\Home`. This creates a natural grouping where all Home-related classes share a namespace prefix, and PSR-4 maps this directly to the directory structure. Adding a new plugin means creating `src/Plugins/NewPlugin/NewPluginModel.php` and `src/Plugins/NewPlugin/NewPluginView.php` with the appropriate namespaces—the Init class will find them automatically.

## Running the Application

The chapter requires Composer to generate the autoloader:

```bash
cd /path/to/spe
composer install
cd 05-Autoload/public
php -S localhost:8080
```

Navigate to `http://localhost:8080` and the application behaves identically to Chapter Four. The same pages load, the same theme dropdown works, the same toast notifications appear. The transformation is invisible to users but transformative for developers.

Examine the network requests or view source—nothing reveals the internal restructuring. The shared CSS and JavaScript files still load from the project root. The HTML output is byte-for-byte identical. But now, finding the TopNav layout means opening `src/Themes/TopNav.php` rather than scrolling through a 150-line file. Modifying the Contact form means editing `src/Plugins/Contact/ContactView.php` in isolation. Adding functionality means creating new files, not expanding existing ones.

This chapter establishes the project structure that carries forward through the remaining examples. Chapter Six will add session management to this autoloaded foundation. Chapter Seven introduces database access. Each addition creates new files in the appropriate directories without modifying the core dispatch mechanism. The investment in proper structure pays dividends as complexity grows.
