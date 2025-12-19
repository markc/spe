# SPE::02 Styled

_Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)_

Chapter One demonstrated that a complete web application can exist as a single self-rendering anonymous class. The code worked, but it relied on browser defaults and minimal inline styles. This chapter introduces the external asset layer that all subsequent chapters build upon: a shared CSS file that provides a complete design system, and a JavaScript file that handles theme switching and user feedback. The PHP code remains structurally identical to Chapter One, but the output transforms from plain HTML into a polished, responsive interface.

## From Inline Styles to External Assets

The transition from Chapter One to Chapter Two represents a fundamental shift in how the application handles presentation. In Chapter One, the entire visual styling consisted of a single inline `<style>` tag with three CSS rules. This worked for demonstration purposes, but it doesn't scale. When multiple chapters need consistent styling, duplicating inline styles across files creates maintenance problems and inconsistency.

The solution is straightforward: extract styles into `/spe.css` and behavior into `/spe.js`, then reference them from the HTML head. The PHP code adds two lines—a stylesheet link and a script tag—and gains access to hundreds of utility classes, a complete color system, responsive layouts, and interactive components. This pattern mirrors how real applications separate concerns: PHP handles data and structure, CSS handles presentation, JavaScript handles interactivity.

## Enhanced Data Structure

The pages array evolves from Chapter One's two-element structure to three elements: `['🏠 Home', 'Home Page', 'Welcome to the...']`. The first element combines an emoji icon with a short label for navigation display. The second provides a page title for the heading and browser tab. The third contains the body content. This separation allows the navigation to show compact labels while the page displays fuller titles.

The properties follow the same pattern. Where Chapter One had `$page` and `$main`, Chapter Two adds `$title` and `$content` with asymmetric visibility. The constructor extracts all three pieces from the pages array based on the validated route, making them available for interpolation throughout the template.

## The CSS Architecture

The `/spe.css` file provides a complete design system in approximately 300 lines. Rather than importing a framework like Bootstrap or Tailwind, SPE uses a custom stylesheet that demonstrates how little CSS modern applications actually need.

The foundation is CSS custom properties (variables) defined on `:root`. Colors like `--bg`, `--fg`, `--accent`, and `--muted` establish the palette. The dark theme overrides these same variables inside a `@media (prefers-color-scheme: dark)` block, meaning the entire color scheme inverts automatically based on system preference. Additional `.light` and `.dark` classes on the `<html>` element allow JavaScript to override the system preference when users click the theme toggle.

The utility classes follow a predictable naming convention. Spacing utilities like `.mt-2` (margin-top: 1rem) and `.p-3` (padding: 1.5rem) use a numeric scale. Flexbox utilities like `.flex`, `.justify-center`, and `.items-center` compose into layout patterns. The `.ml-auto` class pushes elements to the right edge of a flex container—used in the navigation to position the theme toggle button.

Component styles handle cards, buttons, forms, navigation, dropdowns, and toasts. Each component uses the CSS variables, so they automatically adapt to light and dark themes. The `.card` class provides a bordered, padded container with subtle shadow. Button variants like `.btn-success` and `.btn-danger` use semantic color variables. Form inputs inherit the color scheme and show focus states with the accent color.

## Theme Management in JavaScript

The `/spe.js` file exposes an `SPE` object with methods for theme management, toast notifications, and optional AJAX navigation. When the page loads, `SPE.initTheme()` checks localStorage for a saved preference, falls back to the system's `prefers-color-scheme` media query, and applies the appropriate class to the document element.

The theme toggle button calls `SPE.toggleTheme()`, which swaps between `.light` and `.dark` classes, saves the preference to localStorage, and updates the button icon from moon to sun or vice versa. This three-line interaction demonstrates how JavaScript can enhance CSS-driven theming without requiring page reloads or server round-trips.

Toast notifications provide user feedback for actions. The `showToast(message, type)` function creates a positioned element, applies success or danger styling, animates it into view, and automatically removes it after three seconds. The PHP template includes demo buttons that trigger toasts on click, showing how server-rendered HTML can invoke client-side behavior through inline event handlers.

## Navigation with Active State

The navigation generation becomes slightly more sophisticated than Chapter One. The pipe chain now uses `sprintf` to conditionally add an `active` class when the current route matches the link being generated:

```php
$nav = $this->pages
    |> array_keys(...)
    |> (fn($k) => array_map(fn($p) => sprintf(
        '<a href="?m=%s"%s>%s</a>',
        $p, $p === $this->page ? ' class="active"' : '', $this->pages[$p][0]
    ), $k))
    |> (fn($a) => implode(' ', $a));
```

The CSS handles the visual treatment: `nav a.active` receives the accent color and an underline. This pattern—generating semantic classes in PHP and styling them in CSS—keeps presentation logic out of the PHP code while allowing dynamic visual states.

## The Contact Form Pattern

Chapter Two introduces a private method that returns HTML: `contactForm()`. When the current page is 'contact', the main content calls this method instead of simply displaying the content string. The form demonstrates several patterns that recur throughout SPE.

The form uses CSS classes from the shared stylesheet: `.form-group` for vertical spacing, `.text-right` for button alignment. Input fields automatically receive styling through element selectors in the CSS. The submit button uses the default `.btn` class.

The JavaScript handler prevents default form submission and constructs a `mailto:` URL with the form values encoded as query parameters. This opens the user's email client with pre-filled subject and body fields. A toast notification confirms the action. This pattern avoids server-side email handling while still providing a functional contact mechanism.

## CSS Component Reference

The shared stylesheet organizes components into logical sections:

**Variables and Theming**: CSS custom properties define colors, spacing scales, border radii, and shadows. Light and dark themes override these variables rather than duplicating style rules.

**Reset and Base**: Box-sizing, typography defaults, and element-level styling ensure consistency across browsers. Body uses system fonts with reasonable line height.

**Layout**: The `.container` class provides max-width and centering. Flex and grid utilities handle most layout needs without custom CSS.

**Cards and Surfaces**: Bordered containers with background colors and shadows provide visual grouping. Card variants handle interactive and static use cases.

**Navigation**: Horizontal nav with active states, vertical sidebar navigation, and fixed topnav layouts are pre-styled. All respect the theme variables.

**Forms**: Input, textarea, select, and button elements receive consistent styling. Focus states use the accent color. Form groups handle label/input pairing.

**Buttons**: Primary, success, danger, muted, and outline variants cover common needs. Buttons include hover effects and disabled states.

**Toasts**: Fixed-position notifications with slide-in animation and automatic dismissal.

**Responsive Breakpoints**: Media queries at 768px and 600px adjust layouts for tablets and phones. Flex columns stack vertically, grids collapse to fewer columns.

## Running the Application

Start the PHP development server from the project root to serve the shared CSS and JavaScript files:

```bash
cd /path/to/spe
php -S localhost:8080
```

Navigate to `http://localhost:8080/02-Styled/public/` to see the styled application. Click the moon icon to toggle dark mode—the preference persists across page loads. Click the Success and Danger buttons to see toast notifications. Visit the Contact page to see the form integration.

The visual difference from Chapter One is dramatic, but the PHP code has barely changed. The same self-rendering anonymous class pattern, the same pipe operators, the same asymmetric visibility—now wrapped in a professional-looking interface. This separation of concerns carries forward: subsequent chapters add PHP complexity while the presentation layer remains stable.
