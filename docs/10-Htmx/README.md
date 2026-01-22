# Chapter 10 - Htmx Integration

Blog CMS enhanced with htmx for SPA-like navigation without page reloads.

## Features

- SPA-like navigation with URL history preservation
- Partial page updates without full reloads
- Live search with debouncing
- Inline CRUD operations
- Server-triggered toast notifications

## Key htmx Features Demonstrated

- **Navigation**: All links use `hx-get`/`hx-target` for partial updates
- **Forms**: `hx-post` for AJAX form submission
- **Live Search**: `hx-trigger="keyup changed delay:300ms"`
- **Delete**: `hx-confirm` for confirmation dialogs
- **Loading States**: `htmx-indicator` class for spinners
- **URL History**: `hx-push-url` preserves browser history

## PHP 8.5 Features Demonstrated

- **Pipe operator** (`|>`) for functional transformations
- **Readonly classes** for immutable configuration
- **First-class callables** (`...`)
- **Match expressions**
- **Constructor property promotion**
- **Typed constants**
- **`#[\Override]` attribute**

## Architecture

### htmx Integration Points

```
Init.php     → Detects HX-Request header, returns partial HTML
Theme.php    → Loads htmx script, adds hx-boost to body
nav()        → Links with hx-get, hx-target="#main", hx-push-url
Views        → All links/forms enhanced with htmx attributes
```

### Directory Structure

```
10-Htmx/
├── public/
│   └── index.php
└── src/
    ├── Core/           # Init (htmx detection), Ctx, Theme
    ├── Plugins/        # Views with hx-* attributes
    └── Themes/         # Layouts with id="main" target
```

## How It Works

1. **Initial page load**: Full HTML response
2. **Subsequent navigation**: htmx sends `HX-Request` header
3. **Server detects header**: Returns only main content (partial)
4. **htmx swaps**: Replaces `#main` element, updates URL
5. **Flash messages**: Sent via `HX-Trigger` header

## Key Code Examples

### Detect htmx Request

```php
// In Init.php
if (isset($_SERVER['HTTP_HX_REQUEST'])) {
    return $this->out['main'];  // Partial HTML only
}
```

### Navigation with htmx

```php
<a href="/blog"
   hx-get="/blog"
   hx-target="#main"
   hx-push-url="true">Blog</a>
```

### Live Search

```php
<input hx-get="?o=Users"
       hx-trigger="keyup changed delay:300ms"
       hx-target="#main"
       name="search">
```

### Inline Delete

```php
<a hx-get="?o=Users&m=delete&id=1"
   hx-confirm="Delete this user?"
   hx-swap="outerHTML">Delete</a>
```

## Requirements

- PHP 8.5+
- Composer

## Running

```bash
cd 10-Htmx/public
php -S localhost:8080
```

Open http://localhost:8080

## Benefits

- **No JavaScript framework** required
- **Graceful degradation**: Works without JS (full page loads)
- **Hypermedia-driven**: Server controls application state
- **Progressive enhancement**: Enhances existing HTML

## License

MIT License - Copyright (C) 2015-2026 Mark Constable
