# SPE/01-Simple: The First Step in a PHP Micro-Framework

**2025-02-06** -- _Copyright (C) 2015-2025 Mark Constable (AGPL-3.0)_

Welcome to the starting point of our PHP micro-framework journey! This single-file application represents the foundational first step in a series that gradually evolves into a full-featured micro-framework. While incredibly simple, it demonstrates core PHP 8.4 features and best practices that we'll build upon in later examples. Think of it as the seed from which our more complex applications will grow - stripped down to its bare essentials, yet containing all the fundamental DNA of a modern PHP application.

## How It Works

Let me walk you through how this single-file application works. The entire application is built around an anonymous class in index.php, leveraging PHP 8.4's modern features like readonly properties and strict typing. It follows a clean MVC-like pattern, all neatly packaged in a single file.

When a request comes in, the logic starts in the constructor. First, it sets up the navigation menu items. Then it looks at the URL parameters ('m') and makes sure they're properly sanitized - we don't want any nasty surprises! Based on these parameters, it dynamically figures out which page to show and puts everything together using various component methods.

The application is built around several key components working together seamlessly. The routing system watches for the 'm' parameter ('m' is for which page 'method' to display) in your URL to decide which page to display. Navigation is generated dynamically using some simple array mapping. The layout is broken down into modular pieces - nav, head, main, and foot - which are then assembled into clean, semantic HTML.

What makes this particularly interesting is how it uses modern PHP features. We've got strict typing enabled right from the start with `declare(strict_types=1)`. Properties are marked as readonly where they shouldn't change, and we're using those neat arrow functions for concise array mapping. The constructor property promotion feature keeps our code clean and readable.

Security hasn't been forgotten either. URL parameters are carefully sanitized, the HTML structure is properly escaped, and we maintain strict type checking throughout. The URL structure is clean and user-friendly, making it both secure and pleasant to use.

A note about the **Template Method Pattern** design pattern which describes how the collection of `nav()`, `head()`, `main()` and `foot()` partial rendering methods implements a classic object-oriented design approach. The abstract algorithm `html()` serves as the template that defines the foundational page construction process, while delegating specific implementations to its constituent methods. This pattern enables consistent page structure while allowing flexibility in component implementation.

The `html()` method provides a **Composite View Pattern** in the systematic decomposition of the page into discrete functional components including navigation, header, main content, and footer sections. These atomic units are subsequently recomposed into a cohesive HTML document through a hierarchical assembly process. This architectural pattern provides clear separation of concerns while maintaining the relationships between composite elements.

These **Template Methods** always **return** strings so they can be added anywhere in the code base whereas **echo** statments have to be strictly ordered or else mayhem ensues.

## Key Features

- **Single File Architecture**: Complete MVC-like structure in one file using anonymous classes
- **Type Safety**: Strict typing with `declare(strict_types=1)`
- **Modern PHP 8.4**: Utilizing readonly properties and constructor property promotion
- **Clean URL Routing**: Simple but effective routing system
- **XSS Protection**: Input sanitization using `filter_var`
- **Responsive Design**: Mobile-first HTML5 structure

## Code Structure

### Core Components

1. **Routing System**
   - Simple URL parameter handling via `?m=` query string
   - Default page fallback to 'home'
   - Dynamic method calling based on URL parameters

2. **Template Engine**
   - HTML structure defined in private methods
   - Dynamic content injection
   - Automatic method-to-output mapping

3. **Navigation**
   - Dynamic menu generation
   - Clean URL support
   - SEO-friendly structure

### Pages

- Home (`/` or `/?m=home`)
- About (`/?m=about`)
- Contact (`/?m=contact`)

## Quick Start

This is the easiest way to display and develop this project...

```bash
    bash -c "$(curl -fsSL https://php.new/install/linux)"
    [[ ! -d ~/Dev ]] && mkdir ~/Dev
    cd ~/Dev
    git clone https://github.com/markc/spe
    cd spe
    php -S localhost:8000 -t 01-Simple
```
## Technical Details

- Uses PHP 8.4's readonly properties for immutable state
- Implements `__toString()` for automatic string conversion
- Employs array mapping for efficient navigation generation
- Follows PSR-12 coding standards
- Zero external dependencies

## Security Features

- URL parameter sanitization
- HTML5 security headers
- Strict type checking
- XSS prevention measures

## HTML Output

Let's break down the clean, semantic HTML structure that our application generates:

1. **Document Setup**
   - Modern HTML5 doctype and language declaration
   - Proper meta tags for character encoding and viewport settings
   - SEO-friendly description and authorship metadata
   - Favicon link for browser tab icon

2. **Header Section**
   - Main title wrapped in `<h1>` for proper document hierarchy
   - Semantic `<nav>` element with ARIA label for accessibility
   - Clean, unordered list structure for navigation links
   - Simple `?m=` parameter routing for page navigation

3. **Main Content**
   - Semantic `<main>` element containing the page content
   - Proper heading hierarchy with `<h2>` for page title
   - Clean paragraph structure for content

4. **Footer Section**
   - Copyright information wrapped in `<small>` for proper semantics
   - Clean paragraph structure for footer content

Here's the actual output:

```html
    <!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="description" content="Simple PHP Example">
            <meta name="author" content="Mark Constable">
            <title>SPE::01</title>
            <link rel="icon" href="favicon.ico">
        </head>
        <body>
            <header>
                <h1>Simple PHP Example</h1>
                <nav aria-label="Main navigation">
                    <ul>
                        <li>
                            <a href="?m=home" rel="noopener">Home</a>
                        </li>
                        <li>
                            <a href="?m=about" rel="noopener">About</a>
                        </li>
                        <li>
                            <a href="?m=contact" rel="noopener">Contact</a>
                        </li>
                    </ul>
                </nav>
            </header>

            <main>
                <h2>Home Page</h2>
                <p>
                    Lorem ipsum home.
                </p>
            </main>

            <footer>
                <p>
                    <small>Copyright © 2015-2025 Mark Constable (AGPL-3.0)</small>    
                </p>
            </footer>
        </body>
    </html>
```

## Method-by-Method Breakdown

Let's walk through each method in the application and see how they work together:

### Core Methods

#### `__construct()`
```php
public function __construct()
```
The constructor is where everything begins. It:
1. Initializes the navigation array with page links
2. Processes the URL parameter (`m`) to determine which page to show
3. Dynamically calls the appropriate page method
4. Assembles the final output by calling component methods

#### `__toString()`
```php
public function __toString(): string
```
This magic method is called when the class instance is treated as a string. It:
1. Returns the final HTML output by calling `html()`
2. Enables the elegant one-line instantiation and echo: `echo new class { ... }`

### Layout Methods

#### `nav()`
```php
private function nav(): string
```
Generates the navigation menu by:
1. Using array_map to transform the nav array into HTML list items
2. Creating links with the `?m=` parameter for routing
3. Wrapping everything in semantic nav tags with ARIA labels

#### `head()`
```php
private function head(): string
```
Builds the header section by:
1. Creating the main title with h1 tag
2. Incorporating the navigation menu
3. Wrapping everything in a semantic header tag

#### `main()`
```php
private function main(): string
```
Handles the main content area by:
1. Wrapping the current page content in a main tag
2. Maintaining proper HTML structure and indentation

#### `foot()`
```php
private function foot(): string
```
Creates the footer section by:
1. Wrapping the copyright notice in appropriate tags
2. Using the small tag for secondary content

#### `html()`
```php
private function html(): string
```
Assembles the final HTML document by:
1. Adding the DOCTYPE and HTML5 structure
2. Including all necessary meta tags
3. Combining head, main, and foot sections
4. Ensuring proper viewport and character encoding

### Page Content Methods

#### `home()`, `about()`, `contact()`
```php
private function home(): string
private function about(): string
private function contact(): string
```
These content methods:
1. Return the specific content for each page
2. Maintain consistent structure with h2 headings
3. Are called dynamically based on the URL parameter

## Code Flow

1. When a request comes in, PHP creates an instance of our anonymous class
2. The constructor initializes navigation and processes the URL
3. Based on the URL parameter, the appropriate page method is called
4. The layout methods assemble the page structure:
   - `nav()` creates the navigation menu
   - `head()` wraps the title and navigation
   - `main()` includes the page-specific content
   - `foot()` adds the footer
5. `html()` combines everything into a complete HTML document
6. `__toString()` outputs the final result

This modular approach makes the code easy to understand and maintain, while keeping everything neatly organized in a single file.

## License

AGPL-3.0 License - See LICENSE file for details
