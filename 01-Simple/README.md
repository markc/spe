# SPE/01-Simple: The First Step in PHP Micro-Framework Evolution

**2025-02-06** -- _Copyright (C) 2015-2025 Mark Constable (AGPL-3.0)_

Welcome to the starting point of our PHP micro-framework journey! This single-file application represents the foundational first step in a series that gradually evolves into a full-featured micro-framework. While incredibly simple, it demonstrates core PHP 8.4 features and best practices that we'll build upon in later examples. Think of it as the seed from which our more complex applications will grow - stripped down to its bare essentials, yet containing all the fundamental DNA of a modern PHP application.

## How It Works

Let me walk you through how this elegant single-file application works. The entire application is built around an anonymous class in index.php, leveraging PHP 8.4's modern features like readonly properties and strict typing. It follows a clean MVC-like pattern, all neatly packaged in a single file.

When a request comes in, the magic starts in the constructor. First, it sets up the navigation menu items. Then it looks at the URL parameters (either 'm' or 'p') and makes sure they're properly sanitized - we don't want any nasty surprises! Based on these parameters, it dynamically figures out which page to show and puts everything together using various component methods.

The application is built around several key components working together seamlessly. The routing system watches for 'm' or 'p' parameters in your URL to decide which page to display. Navigation is generated dynamically using some clever array mapping. The layout is broken down into modular pieces - nav, head, main, and foot - which are then assembled into clean, semantic HTML.

What makes this particularly interesting is how it uses modern PHP features. We've got strict typing enabled right from the start with declare(strict_types=1). Properties are marked as readonly where they shouldn't change, and we're using those neat arrow functions for concise array mapping. The constructor property promotion feature keeps our code clean and readable.

Security hasn't been forgotten either. URL parameters are carefully sanitized, the HTML structure is properly escaped, and we maintain strict type checking throughout. The URL structure is clean and user-friendly, making it both secure and pleasant to use.

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
   - URL parameter handling via `?m=` or `?p=` query strings
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

    bash -c "$(curl -fsSL https://php.new/install/linux)"
    [[ ! -d ~/Dev ]] && mkdir ~/Dev
    cd ~/Dev
    git clone https://github.com/markc/spe
    cd spe
    php -S localhost:8000 -t 01-Simple

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

## License

AGPL-3.0 License - See LICENSE file for details
