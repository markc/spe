# SPE/02-Styled - Technical Reference

_Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)_

This document provides a technical comparison between `01-Simple` and `02-Styled` versions of the Simple PHP Example (SPE) framework.

## Key Differences

### 1. Framework Structure

#### 01-Simple
- Minimal single-file PHP framework
- Basic HTML structure without CSS
- Uses readonly array for navigation
- Simple constant for default page

#### 02-Styled
- Enhanced single-file PHP framework
- Integrated Bootstrap 5 styling
- Flexible navigation system
- Modern PHP 8.x features

### 2. Request Handling

#### 01-Simple
```php
$page = filter_var(trim($_REQUEST['m'] ?? '', '/'), FILTER_SANITIZE_URL);
$method = empty($page) ? self::DEFAULT_PAGE : $page;
```
- Simple request parameter handling
- Uses constant for default page
- Basic URL sanitization

#### 02-Styled
```php
foreach ($this->in as $key => $default) {
    $this->in[$key] = filter_var(
        trim($_REQUEST[$key] ?? $default, '/'),
        FILTER_SANITIZE_URL
    ) ?: $default;
}
```
- Generalized input handling
- Loops over configurable input parameters
- Maintains defaults when empty

### 3. Template System

#### 01-Simple
- Basic HTML structure
- Simple navigation list
- No styling or CSS
- Basic header, main, and footer sections

#### 02-Styled
- Bootstrap 5 integration
- Responsive navigation bar
- Modern UI components
- Structured layout with proper spacing
- Interactive components (e.g., collapsible navbar)

### 4. Navigation System

#### 01-Simple
```php
fn($n) => '<li><a href="?m=' . $n[1] . '" rel="noopener">' . $n[0] . '</a></li>'
```
- Basic list-based navigation
- Simple href links with 'm' parameter
- No active state handling

#### 02-Styled
```php
$url = str_starts_with($n[1], 'http') ? $n[1] : "?m=$n[1]";
$c = $this->in['m'] === $n[1] ? ' active" aria-current="page"' : '"';
```
- Bootstrap navbar integration
- Smart URL handling (internal vs external)
- Active state management
- Responsive mobile menu

### 5. Content Pages

#### 01-Simple
- Basic HTML content
- Simple headings and paragraphs
- No structured layout

#### 02-Styled
- Bootstrap components and utilities
- Responsive grid system
- Modern UI elements (cards, buttons, forms)
- Consistent spacing and typography

### 6. Asset Management

#### 01-Simple
- No external dependencies
- Basic favicon
- Minimal meta tags

#### 02-Styled
- Bootstrap 5 CSS and JS integration
- Enhanced meta tags
- Favicon support
- CDN-based dependencies

## Implementation Details

### Bootstrap 5 Integration
- Uses Bootstrap 5.3.3
- CDN-based delivery for CSS and JS
- Bundle includes Popper.js for dropdowns

### PHP Features Used
- PHP 8.x type declarations
- Strict types enabled
- Null coalescing operator (??)
- Arrow functions
- String functions (str_starts_with)

### Security Considerations
- URL sanitization using FILTER_SANITIZE_URL
- XSS prevention through proper escaping
- Secure form handling

## Usage

1. Deploy to a PHP 8.x capable web server
2. Access through web browser
3. Navigate using the responsive menu
4. Interact with styled components

## Future Enhancements

- Custom theme support
- Additional Bootstrap components
- Enhanced form validation
- AJAX-based page loading
- Asset minification

## More about the Method Template Pattern

The Method Template Pattern in PHP 8.4 represents a sophisticated approach to content rendering that offers numerous compelling advantages over traditional output methods. At its core, the pattern provides a structured way to define an algorithm's skeleton while allowing specific steps to be overridden by subclasses, making it particularly powerful for HTML content generation.

One of the pattern's most significant benefits is its location independence within PHP programs. Unlike echo statements that must be carefully positioned to maintain output order, methods following this pattern can be called from anywhere in the code. This flexibility arises because the pattern returns content rather than directly outputting it, allowing developers to compose complex layouts without worrying about the physical location of the rendering code.

The pattern's ability to buffer output into a single variable represents another crucial advantage. By collecting all rendered content into a variable, developers gain complete control over the final output just before sending it to the browser. This buffering capability enables last-minute modifications, such as content filtering, string replacements, or even complete transformations of the rendered content. It also facilitates implementing caching mechanisms or applying compression algorithms to the entire output as a single unit.

PHP 8.4's enhanced type system and attributes complement the Method Template Pattern exceptionally well. Developers can leverage return type declarations to ensure methods return the expected content types, while attributes can provide metadata about how specific template methods should be processed. This type safety and metadata awareness helps prevent runtime errors and improves code maintainability.

The pattern also excels at separation of concerns. By encapsulating rendering logic within methods, it becomes easier to maintain clean boundaries between business logic and presentation code. Each method can focus on rendering its specific component while the template method orchestrates the overall composition. This separation makes the code more maintainable and testable, as individual rendering components can be unit tested in isolation.

Another substantial benefit is the pattern's natural alignment with component-based architecture. Methods can represent reusable UI components, each responsible for rendering a specific part of the interface. This modularity allows developers to build complex interfaces from simple, composable pieces while maintaining clean, organized code. The pattern's inheritance-based structure enables creation of specialized versions of components through subclassing, promoting code reuse without duplication.

The pattern's return-based approach also facilitates powerful content transformation chains. Rendered content can be passed through multiple processing steps, each adding or modifying the output in some way. This capability is particularly valuable for implementing cross-cutting concerns like security filtering, internationalization, or accessibility enhancements. Developers can create transformation pipelines that process the rendered content systematically before final output.

Error handling becomes more manageable with the Method Template Pattern. Since content is returned rather than directly output, errors in rendering can be caught and handled gracefully. This control allows for sophisticated error recovery strategies and the ability to provide fallback content when rendering fails, enhancing application robustness.

The pattern also supports lazy evaluation of content. Because rendering occurs when methods are called rather than at definition time, expensive rendering operations can be deferred until absolutely necessary. This lazy approach can improve performance by avoiding unnecessary rendering work and reducing memory usage.

Furthermore, the pattern's structured approach to content generation makes it easier to implement caching strategies. Rendered content can be cached at various levels of granularity, from individual components to entire page layouts. The buffered output can be stored in various caching systems and retrieved later, significantly improving application performance for frequently accessed content.

In conclusion, the Method Template Pattern, when implemented in PHP 8.4, provides a robust, flexible, and maintainable approach to content rendering. Its ability to buffer output, location independence, and natural alignment with modern PHP features make it an excellent choice for applications of any size. The pattern's support for transformation chains, error handling, and caching, combined with its promotion of clean architecture, makes it a powerful tool in the modern PHP developer's toolkit.

---

For more information, visit the [project page](https://github.com/markc/spe).
