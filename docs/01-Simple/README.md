# SPE::01 Simple

_Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)_

This chapter presents the most minimal possible PHP web application that still demonstrates real-world patterns. The entire application lives in a single file of around 45 lines, yet it implements proper routing, input sanitization, content management, and dynamic navigation. Understanding how this code works provides the foundation for everything that follows in the SPE series.

## The Core Pattern: Echo a Self-Rendering Object

The entire application hinges on a single statement: `echo new class { ... };`. This pattern deserves careful attention because it encapsulates a complete request-response cycle in one expression. When PHP encounters this line, it instantiates an anonymous class, which triggers the constructor to process the incoming request. The `echo` statement then converts the object to a string by calling the magic `__toString()` method, which returns the complete HTML response. The semicolon at the end terminates the anonymous class definition. This is not merely clever syntax; it represents a deliberate architectural choice where the object's entire lifecycle—construction, processing, and output—occurs as a single atomic operation.

The `declare(strict_types=1)` directive at the top enforces strict type checking throughout the file. When strict types are enabled, PHP will throw a TypeError if a function receives an argument of the wrong type, rather than silently coercing it. This catches bugs at development time rather than allowing them to manifest as subtle runtime issues. For a framework that aims to demonstrate best practices, strict typing is non-negotiable.

## Data Structure: The Pages Array

The `$pages` array serves as the application's entire content management system. Each key in the array represents a route name that can appear in the URL's query string, while the value is a two-element array containing the display title and the HTML content for that page. This structure is deliberately simple: `'home' => ['Home', '<h2>Home</h2><p>...</p>']`. The first element provides human-readable text for navigation links, while the second contains the actual page content.

This design separates route identifiers from display names, which matters more than it might initially appear. The route `'home'` is a machine-readable slug that appears in URLs, while `'Home'` is the user-facing label. In later chapters, this distinction becomes more important when routes like `'contact-us'` need to display as `'Contact Us'` in navigation menus. By establishing this pattern from the beginning, the code teaches a principle that scales naturally.

The typed constant `private const string DEFAULT = 'home'` defines which page loads when no route is specified. PHP 8.3 introduced typed constants, meaning the compiler enforces that `DEFAULT` must always be a string. Previous PHP versions allowed constants to change type accidentally during refactoring; typed constants prevent this class of bug entirely. The constant is private because no external code needs to know the default route—it's an internal implementation detail.

## Asymmetric Visibility: Controlled Access

The properties `$page` and `$main` use PHP 8.4's asymmetric visibility feature, declared as `public private(set) string $page`. This syntax means external code can read the property publicly, but only the class itself can modify it. Before PHP 8.4, achieving this pattern required writing explicit getter methods or using magic methods like `__get()`. The new syntax eliminates that boilerplate while making the intent immediately clear in the property declaration itself.

Consider why these specific properties exist. The `$page` property holds the validated route name after input processing, while `$main` holds the content that will be rendered in the page body. Both are strings, both are set once during construction, and both need to be accessible when generating output. Making them publicly readable allows the `__toString()` method to interpolate them into the HTML template, while keeping them privately writable ensures the constructor is the only code that can establish their values.

## Request Processing: The Pipe Operator

The constructor contains the application's entire request-handling logic, and it demonstrates why the PHP 8.5 pipe operator represents such a significant language improvement. The expression `($_REQUEST['m'] ?? '') |> trim(...) |> htmlspecialchars(...) |> (fn($p) => $p ?: self::DEFAULT)` reads from left to right as a data transformation pipeline.

The process begins with `$_REQUEST['m'] ?? ''`, which retrieves the `m` parameter from the query string or returns an empty string if it's missing. This value then flows through the pipe operator to `trim(...)`, a first-class callable that removes whitespace from both ends of the string. First-class callables, introduced in PHP 8.1, allow you to reference a function as a value using the `...` syntax. Without this feature, you would need to write `fn($x) => trim($x)` or use the older `'trim'` string syntax.

The trimmed value continues to `htmlspecialchars(...)`, which converts special characters to HTML entities, preventing any attempt at XSS injection through the URL parameter. Finally, the sanitized value reaches an arrow function `fn($p) => $p ?: self::DEFAULT` that applies the default if the result is empty. The `?:` operator (sometimes called the Elvis operator) returns the left operand if it's truthy, otherwise the right operand.

What makes this pattern powerful is how it reads. Each step clearly describes a transformation, and the data flows visibly from one operation to the next. Compare this to the nested equivalent: `(($p = htmlspecialchars(trim($_REQUEST['m'] ?? ''))) ? $p : self::DEFAULT)`. The pipe version expresses the same logic but reads in the order the operations actually occur.

The second line of the constructor, `$this->main = $this->pages[$this->page][1] ?? '<p>Error: page not found</p>'`, retrieves the content for the selected page. The null coalescing operator `??` provides a fallback error message if someone manually enters an invalid route in the URL. This defensive coding ensures the application never crashes, even with unexpected input.

## Output Generation: Dynamic Navigation

The `__toString()` method transforms the object into its HTML representation. This magic method is called automatically whenever PHP needs to treat the object as a string, which happens when `echo` tries to output it. The method must return a string and cannot throw exceptions (a PHP requirement for `__toString()`).

The navigation generation demonstrates another pipe operator chain: `$this->pages |> array_keys(...) |> (fn($k) => array_map(...)) |> (fn($a) => implode(' | ', $a))`. This expression starts with the full pages array and extracts just the keys (`['home', 'about', 'contact']`). Those keys flow to an `array_map` that transforms each key into an HTML anchor element. The inner arrow function `fn($p) => "<a href=\"?m=$p\">{$this->pages[$p][0]}</a>"` accesses the original pages array to retrieve the display title for each route. Finally, the array of links is joined with ` | ` separators to create the navigation string.

This approach to navigation generation is self-maintaining. When you add a new page to the `$pages` array, the navigation updates automatically. There's no separate list of menu items to keep in sync, no configuration file to edit, and no risk of forgetting to update the navigation when content changes. The single source of truth principle applies: the pages array defines both the available routes and the navigation structure.

## The HTML Template

The heredoc syntax `<<<HTML ... HTML;` provides a clean way to embed a large block of HTML without escaping quotes or concatenating strings. Variable interpolation works normally inside heredocs, so `$nav` and `{$this->main}` are replaced with their values. The curly brace syntax `{$this->main}` is required for property access, while simple variables like `$nav` can be interpolated directly.

The HTML itself is minimal but complete. The `<meta name="color-scheme" content="light dark">` tag tells the browser this page supports both light and dark modes, allowing the browser's default stylesheet to adapt to the user's system preference. This single line provides dark mode support without any CSS complexity. The inline styles are deliberately minimal: centering the body, removing link underlines, and eliminating margin from horizontal rules. This isn't a CSS framework; it's just enough styling to make the output pleasant without distracting from the PHP concepts being demonstrated.

The semantic HTML structure uses `<header>`, `<main>`, and `<footer>` elements, establishing a pattern that later chapters will build upon. The header contains the site title and navigation, main contains the page-specific content, and the footer contains the copyright notice. This separation of concerns at the HTML level mirrors the separation of concerns in the PHP code.

## Why This Structure Exists

The architecture of this file is not accidental. Every choice serves the goal of demonstrating modern PHP in its most essential form while establishing patterns that scale to larger applications.

The anonymous class pattern shows that PHP can express complete applications as single statements. This isn't how you'd build a production application, but it demonstrates the language's expressiveness and forces you to think about what an application truly needs. By removing all external dependencies, the code reveals that routing, content management, and templating require very little machinery.

The pipe operator chains demonstrate functional programming patterns in PHP. Data transformation pipelines are common in languages like F#, Elixir, and JavaScript (via libraries like Lodash). PHP 8.5 brings this pattern to the language natively, and this chapter shows why it matters: code becomes easier to read, easier to reason about, and easier to extend.

The asymmetric visibility properties show PHP's continued evolution toward expressive type systems. Rather than writing boilerplate getters or accepting that properties are fully public, developers can now express exactly the access pattern they need. The intent is clear from the declaration, not hidden in method implementations.

Most importantly, this chapter establishes a contract with the reader: complexity will be introduced only when it serves a purpose. The pages array is simple because a simple array is sufficient. The routing is a single conditional because elaborate routing isn't needed yet. As the series progresses and requirements grow, the code will evolve to meet them—but never before.

## Running the Application

To run this application, navigate to the `01-Simple` directory and start PHP's built-in development server:

```bash
cd 01-Simple/public
php -S localhost:8080
```

Open `http://localhost:8080` in a browser. Click the navigation links to see how the `?m=` parameter changes the URL and the page content updates accordingly. Try entering an invalid route like `?m=invalid` to see the error message. View the page source to see exactly what the PHP generates.

This is the simplest possible starting point, but it contains the seeds of everything that follows. The pages array will become a database. The anonymous class will split into models and views. The pipe operators will process more complex data. But the core idea—that a web application is fundamentally about transforming requests into responses—remains constant throughout the series.
