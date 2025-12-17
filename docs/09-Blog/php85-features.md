# PHP 8.5 Features in SPE

This document showcases all PHP 8.x features used throughout the SPE framework.

## PHP 8.5: Pipe Operator

The pipe operator `|>` enables functional-style data transformation chains:

```php
// Input sanitization
$page = ($_REQUEST['m'] ?? '')
    |> trim(...)
    |> (fn($s) => filter_var($s, FILTER_SANITIZE_URL))
    |> (fn($p) => $p ?: self::DEFAULT);

// Navigation building
$nav = $this->pages
    |> array_keys(...)
    |> (fn($keys) => array_map(fn($k) => "<a href=\"?m=$k\">{$k}</a>", $keys))
    |> (fn($links) => implode(' | ', $links));

// URL-preserving theme links
private function themeLink(string $theme): string {
    return $_GET
        |> (fn($p) => [...$p, 't' => $theme])
        |> http_build_query(...)
        |> (fn($q) => "?$q");
}

// Array indexing
$catById = $categories |> (fn($cats) => array_column($cats, null, 'id'));
```

## PHP 8.4: Asymmetric Visibility

Control read vs write access separately:

```php
class Ctx {
    // Public read, private write
    public private(set) string $email = 'mc@netserva.org';
    public private(set) string $page;
    public private(set) string $content;
}

// External code can read but not write
echo $ctx->email;        // OK
$ctx->email = 'new';     // Error!
```

## PHP 8.4: New Without Parentheses

Cleaner instantiation syntax:

```php
// Before
echo new Init(new Ctx());

// After (PHP 8.4)
echo new Init(new Ctx);
```

## PHP 8.3: Typed Class Constants

Type-safe constants with explicit types:

```php
final class Init {
    private const string NS = 'SPE\\Blog\\';
    private const string DEFAULT = 'home';
}

final class PostsModel {
    private const int DEFAULT_PER_PAGE = 10;
}

final class Db {
    private const array OPTIONS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
}
```

## PHP 8.3: Override Attribute

Explicit declaration of method overrides:

```php
abstract class Plugin {
    public function list(): array { /* default */ }
}

final class HomeModel extends Plugin {
    #[\Override]
    public function list(): array {
        return ['head' => 'Home', 'main' => 'Welcome'];
    }
}
```

## PHP 8.2: Readonly Classes

Immutable value objects:

```php
final readonly class PluginMeta {
    public function __construct(
        public string $name,
        public string $description = '',
        public string $emoji = '',
        public int $order = 100,
        public bool $auth = false,
        public bool $admin = false,
        public bool $enabled = true,
    ) {}
}
```

## PHP 8.1: Enums

Type-safe enumerations:

```php
enum QueryType: string {
    case All = 'all';      // fetchAll()
    case One = 'one';      // fetch()
    case Column = 'column'; // fetchColumn()
}

// Usage with match
return match ($type) {
    QueryType::All => $stmt->fetchAll(),
    QueryType::One => $stmt->fetch(),
    QueryType::Column => $stmt->fetchColumn(),
};
```

## PHP 8.1: First-Class Callables

Functions as first-class citizens:

```php
// Direct reference to function
$keys = array_keys(...);
$trimmed = trim(...);

// With pipe operator
$value = $input |> trim(...) |> strtolower(...);

// In array operations
$nav = $items |> array_keys(...) |> (fn($k) => array_map(...));
```

## Combined Examples

Real-world usage combining multiple features:

```php
<?php declare(strict_types=1);

final class Init {
    private const string NS = 'SPE\\Blog\\';  // 8.3 typed const

    public function __construct(private Ctx $ctx) {
        // 8.5 pipe + 8.1 first-class callable
        foreach ($this->ctx->in as $k => $v)
            $this->ctx->in[$k] = ($_REQUEST[$k] ?? $v)
                |> trim(...)
                |> htmlspecialchars(...);

        // 8.4 match expression
        $this->ctx->out['main'] = match (true) {
            !class_exists($model) => 'Plugin not found',
            default => (new $model($this->ctx))->$m()  // 8.4 new without parens
        };
    }
}

final readonly class PluginMeta {  // 8.2 readonly class
    public function __construct(
        public string $name,
        public bool $auth = false,
    ) {}

    public function url(): string {
        return "?o={$this->name}";  // Immutable, safe to use
    }
}
```
