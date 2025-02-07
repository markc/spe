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

---

For more information, visit the [project page](https://github.com/markc/spe).
