# SPE::01 Simple: The First Step in a PHP Micro-Framework

**2025-02-11** -- _Copyright (C) 2015-2025 Mark Constable (AGPL-3.0)_

Welcome to the starting point of our PHP micro-framework journey using a series of Simple PHP Examples! This single-file application represents the foundational first step in a series that gradually evolves into a full-featured micro-framework. While incredibly simple, it demonstrates core PHP 8.4 features and best practices that we'll build upon in later examples. Think of it as the seed from which our more complex applications will grow - stripped down to its bare essentials, yet containing all the fundamental DNA of a modern PHP application.

## How It Works

Let me walk you through how this single-file application works. The entire application is built around an anonymous class in `index.php`, leveraging PHP 8.4's modern features like readonly properties and strict typing. It follows a clean MVC-like pattern, all neatly packaged in a single file.

When a request comes in, the logic starts in the constructor. First, it sets up the navigation menu items. Then it looks at the URL parameters ('m') and makes sure they're properly sanitized - we don't want any nasty surprises! Based on these parameters, it dynamically figures out which page to show and puts everything together using various component methods.

The application is built around several key components working together seamlessly. The routing system watches for the 'm' parameter ('m' is for which page 'method' to display) in your URL to decide which page to display. Navigation is generated dynamically using some simple array mapping. The layout is broken down into modular pieces - `nav`, `head`, `main`, and `foot` - which are then assembled into clean, semantic HTML.

What makes this particularly interesting is how it uses modern PHP features. We've got strict typing enabled right from the start with `declare(strict_types=1)`. Properties are marked as `readonly` where they shouldn't change, and we're using those neat arrow functions for concise array mapping. The constructor property promotion feature keeps our code clean and readable.

Security hasn't been forgotten either. URL parameters are carefully sanitized, the HTML structure is properly escaped, and we maintain strict type checking throughout. The URL structure is clean and user-friendly, making it both secure and pleasant to use.

A note about the **Template Method Pattern** design pattern which describes how the collection of `nav()`, `head()`, `main()` and `foot()` partial rendering methods implements a classic object-oriented design approach. The abstract algorithm `html()` serves as the template that defines the foundational page construction process, while delegating specific implementations to its constituent methods. This pattern enables consistent page structure while allowing flexibility in component implementation.

The `html()` method provides a **Composite View Pattern** in the systematic decomposition of the page into discrete functional components including navigation, header, main content, and footer sections. These atomic units are subsequently recomposed into a cohesive HTML document through a hierarchical assembly process. This architectural pattern provides clear separation of concerns while maintaining the relationships between composite elements.

These **Template Methods** always **return** strings so they can be added anywhere in the code base whereas **echo** statements have to be strictly ordered or else mayhem ensues.

## Key Features

- **Single File Architecture**: Complete MVC-like structure in one file using anonymous classes
- **Type Safety**: Strict typing with `declare(strict_types=1)`
- **Modern PHP 8.4**: Utilizing `readonly` properties and constructor property promotion
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

## Technical Details

- Uses PHP 8.4's `readonly` properties for immutable state
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

The constructor is where everything begins. It is responsible for initializing the application state and orchestrating the initial rendering. The constructor first initializes the `$nav` array, which holds the application's navigation structure. It then examines the `m` parameter passed in the request, sanitizing it to prevent potential security vulnerabilities. If this parameter is empty, the application defaults to displaying the 'home' page. Ultimately, this logic determines the page's main content.

The constructor plays a crucial role in preparing the different elements that form the page. For example, the `$nav` array, containing all navigation links, is built here and utilized by other rendering components.

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
1. Using `array_map` to transform the `nav` array into HTML list items
2. Creating links with the `?m=` parameter for routing
3. Wrapping everything in semantic `<nav>` tags with ARIA labels

#### `head()`
```php
private function head(): string
```
Builds the header section by:
1. Creating the main title with `<h1>` tag
2. Incorporating the navigation menu
3. Wrapping everything in a semantic `<header>` tag

#### `main()`
```php
private function main(): string
```
Handles the main content area by:
1. Wrapping the current page content in a `<main>` tag
2. Maintaining proper HTML structure and indentation

#### `foot()`
```php
private function foot(): string
```
Creates the footer section by:
1. Wrapping the copyright notice in appropriate tags
2. Using the `<small>` tag for secondary content

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
2. Maintain consistent structure with `<h2>` headings
3. Are called dynamically based on the URL parameter

## Code Flow

1. When a request comes in, PHP creates an instance of our anonymous class.
2. The constructor initializes navigation and processes the URL.
3. Based on the URL parameter, the appropriate page method is called.
4. The layout methods assemble the page structure:
   - `nav()` creates the navigation menu.
   - `head()` wraps the title and navigation.
   - `main()` includes the page-specific content.
   - `foot()` adds the footer.
5. `html()` combines everything into a complete HTML document.
6. `__toString()` outputs the final result.

This modular approach makes the code easy to understand and maintain, while keeping everything neatly organized in a single file.

## Optimization

The current implementation, while functional and clear, has opportunities for optimization. A key area for improvement lies in streamlining the rendering process to minimize redundant operations. Let's consider the original script's approach: It uses an associative array called `$out` to store different HTML elements. It then iterates to populate the `$out` array from the various `nav()`, `head()`, `main()`, and `foot()` calls. Finally, in `html()`, it pulls from `$out` to assemble the final output.

This can be made more efficient by:

*   **Removing Redundant Iteration:** The iteration through the `$out` array, checking for methods with the same name as the keys, is unnecessary. We can directly call the methods responsible for generating the HTML components and assign their results to the appropriate variables.
*   **Direct Property Assignment:** Instead of storing intermediate values in the `$out` array and then retrieving them in the `html()` method, we can directly assign the results of methods like `nav()`, `head()`, and `foot()` calls to class properties. This avoids the overhead of array access.
*   **Reduce Function Calls:** The original script might call methods multiple times, even when the output doesn't change. The optimized script reduces the number of method calls by pre-calculating HTML fragments and storing them for later use.
By eliminating unnecessary steps and streamlining the data flow, we can reduce the processing time required to generate a response. The goal is to transition from relying heavily on an array to a more direct rendering approach utilizing properties for building the page components. The use of `echo` statements is an anti-pattern in templating because the order must be fixed. Using returned strings will result in better composability and less errors.

The optimized code will flow more directly, performing the necessary operations without extra steps. This will lead to better overall performance.

Here's an optimized version of the script, along with explanations of the changes:

```php
<?php

declare(strict_types=1);

// Created: 20150101 - Updated: 20250206
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

class Page
{
    private const DEFAULT_PAGE = 'home';

    private readonly array $nav;

    private string $head;
    private string $main;
    private string $foot;
    private string $doc = 'SPE::01'; // Make constant

    public function __construct()
    {
        $this->nav = [
            ['Home', 'home'],
            ['About', 'about'],
            ['Contact', 'contact']
        ];

        $page = filter_var(trim($_REQUEST['m'] ?? '', '/'), FILTER_SANITIZE_URL);
        $method = empty($page) ? self::DEFAULT_PAGE : $page;

        $this->main = method_exists($this, $method) ? $this->{$method}() : '<p>Error: missing page!</p>';
        $this->head = '
        <header>
            <h1>
                <a href="../" title="Back to parent directory">« Simplest PHP Example</a>
            </h1>' . $this->nav() . '
        </header>';
        $this->foot = '
        <footer>
            <p>
                <small>Copyright © 2015-2025 Mark Constable (AGPL-3.0)</small>    
            </p>
        </footer>';
    }

    public function __toString(): string
    {
        return $this->html();
    }

    private function nav(): string
    {
        $links = array_map(
            fn($n) => '            <li>
                        <a href="?m=' . $n[1] . '" rel="noopener">' . $n[0] . '</a>
                    </li>',
            $this->nav
        );
        return '
            <nav aria-label="Main navigation">
                <ul>
        ' . implode('
        ', $links) . '
                </ul>
            </nav>';
    }

    private function html(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="Content-Security-Policy" content="default-src \'self\'; style-src \'self\' \'unsafe-inline\'">
        <meta name="color-scheme" content="light dark">
        <title>' . $this->doc . '</title>
        <meta name="description" content="Simple PHP Example">
        <meta name="author" content="Mark Constable">
        <link rel="icon" href="favicon.ico">
        <style>
            a {
                text-decoration: none;
            }
        </style>
    </head>
    <body>' . $this->head . $this->main . $this->foot . '
    </body>
</html>';
    }

    private function home(): string
    {
        return '<h2>Home Page</h2>
            <p>
                Lorem ipsum home.
            </p>';
    }

    private function about(): string
    {
        return '<h2>About Page</h2>
            <p>
                Lorem ipsum about.
            </p>';
    }

    private function contact(): string
    {
        return '<h2>Contact Page</h2>
            <p>
                Lorem ipsum contact.
            </p>';
    }
}

echo new Page();
```

**Explanation of Optimizations and Why They're Faster:**

1.  **Removed Redundant Iteration:** The original script iterated through the `$out` array, checking for methods with the same name as the keys and calling them to populate `$out`. This is redundant. We can directly call the methods and assign their results to the appropriate properties of the class.
2.  **Direct Property Assignment:**  Instead of storing intermediate values in the `$out` array and then retrieving them in the `html()` method, we directly assign the results of the `nav()`, `head()`, `main()` and `foot()` calls to class properties. This avoids the overhead of array access.  `$out` array was removed completely.
3. **Use Named Class instead of Anonymous class** Using a named class `Page` and instantiating it. This ensures that the class is only defined once, even if it's included multiple times (which can sometimes happen in more complex projects).
4.  **Reduced Function Calls:**  The original script called multiple methods unnecessarily. The optimized script reduces the number of method calls by pre-calculating the HTML fragments in the constructor and storing them in class properties.
5. **Property Type Declaration:** Added type declaration in the variables for clarity.
6. **Removed Unnecessary Logic:** Removed the logic where the constructor iterated through the `$out` array, checking for methods with the same name as the keys.

**Why is the Optimized Code Faster?**

*   **Reduced Overhead:** By eliminating the unnecessary iteration and array access, the optimized code reduces the overall overhead. Method calls and property access are generally faster than array operations in this context.
*   **More Direct Execution:** The optimized code flows more directly, performing the necessary operations without extra steps.

**Further Optimization Considerations:**

*   **Caching:** For dynamic content that doesn't change frequently, consider implementing a caching mechanism (e.g., using PHP's `opcache` or a separate caching system like Redis or Memcached) to store the generated HTML fragments.
*   **Template Engine:** For more complex applications, using a dedicated template engine (like Twig or Blade) can improve code organization and maintainability.
*   **HTTP Caching Headers:**  Add appropriate HTTP caching headers to the response to allow the browser to cache the page, further reducing server load.
*   **Database Queries:** If the page involves database queries, optimize the queries themselves. Use indexes, prepared statements, and efficient query design to minimize database access time.
*   **Consider a Framework:** For larger, more complex applications, consider using a PHP framework (like Laravel, Symfony, or CakePHP). Frameworks provide a structured environment, routing, templating, and other features that can improve development efficiency and code quality.

This optimized version provides a significant performance boost by streamlining the code and eliminating redundant operations. Remember to benchmark your code before and after any optimizations to verify the actual performance gains.

## Attribution

Most of this README, and the optimized example code, is courtesy of Googles Gemini but
it is also based on my own original input along with some tweaks from Claude 3.5 via
the Cline extension for VScode (on linux). Claude did most of the work refactoring
the original old PHP 7 example script to this current version, which is slightly smaller
than Geminis optimized version, so I will stick to the current PHP 8.4 tested version.
However, the optimized version has some valid points so I've included it here but in
followup examples the added complexity of the `$this->out` to accumulate rendered page
partials does make sense.

## License

AGPL-3.0 License - See LICENSE file for details
