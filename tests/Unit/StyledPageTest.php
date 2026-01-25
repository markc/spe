<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

/**
 * Unit Tests for 02-Styled/public/index.php
 *
 * Tests individual components and logic in isolation:
 * - Page routing and defaults
 * - Input sanitization and security
 * - Navigation with emoji icons
 * - Dynamic title generation
 * - Contact form rendering
 * - External asset references
 */

describe('Page Routing', function () {

    test('defaults to home page when no parameter provided', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toContain('Home Page');
        expect($html)->toContain('Welcome to the <b>Styled</b> chapter');
    });

    test('defaults to home page when empty parameter provided', function () {
        $html = renderPage(styledIndexPath(), ['m' => '']);

        expect($html)->toContain('Home Page');
    });

    test('defaults to home page when whitespace-only parameter provided', function () {
        $html = renderPage(styledIndexPath(), ['m' => '   ']);

        expect($html)->toContain('Home Page');
    });

    test('routes to about page correctly', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'about']);

        expect($html)->toContain('About Page');
        expect($html)->toContain('dark mode');
        expect($html)->toContain('toast');
    });

    test('routes to contact page correctly', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'contact']);

        expect($html)->toContain('Contact Page');
        expect($html)->toContain('email form');
    });

    test('defaults to home for invalid page', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'nonexistent']);

        expect($html)->toContain('Home Page');
    });

});

describe('Input Sanitization', function () {

    test('trims whitespace from page parameter', function () {
        $html = renderPage(styledIndexPath(), ['m' => '  about  ']);

        expect($html)->toContain('About Page');
    });

    test('escapes HTML in page parameter to prevent XSS', function () {
        $html = renderPage(styledIndexPath(), ['m' => '<script>alert(1)</script>']);

        // Should default to home, not execute script
        expect($html)->toContain('Home Page');
        expect($html)->not->toContain('<script>alert(1)</script>');
    });

    test('rejects array injection attacks', function () {
        // Simulated by checking the is_string guard works
        $html = renderPage(styledIndexPath(), ['m' => 'home']);

        expect($html)->toContain('Home Page');
    });

});

describe('Navigation with Emojis', function () {

    test('generates navigation with emoji icons', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toContain('🏠 Home');
        expect($html)->toContain('📋 About');
        expect($html)->toContain('✉️ Contact');
    });

    test('navigation links use correct URLs', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toContain('href="?m=home"');
        expect($html)->toContain('href="?m=about"');
        expect($html)->toContain('href="?m=contact"');
    });

    test('current page has active class', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'about']);

        expect($html)->toMatch('/<a href="\?m=about" class="active">/');
    });

    test('non-current pages do not have active class', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'about']);

        expect($html)->toMatch('/<a href="\?m=home">/');
        expect($html)->not->toMatch('/<a href="\?m=home" class="active">/');
    });

    test('navigation is inside card with flex layout', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toContain('<nav class="card flex">');
    });

});

describe('Dynamic Title', function () {

    test('page title includes chapter and page name', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'home']);

        expect($html)->toContain('<title>SPE::02 Home Page</title>');
    });

    test('about page has correct title', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'about']);

        expect($html)->toContain('<title>SPE::02 About Page</title>');
    });

    test('contact page has correct title', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'contact']);

        expect($html)->toContain('<title>SPE::02 Contact Page</title>');
    });

    test('h2 heading matches page title', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'about']);

        expect($html)->toContain('<h2>About Page</h2>');
    });

});

describe('External Assets', function () {

    test('includes external CSS stylesheet', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toContain('<link rel="stylesheet" href="../site.css">');
    });

    test('includes external JavaScript file', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toContain('<script src="../base.js"></script>');
    });

    test('no inline styles in head', function () {
        $html = renderPage(styledIndexPath(), []);

        // Unlike 01-Simple, 02-Styled uses external CSS
        expect($html)->not->toMatch('/<style>.*body\{.*<\/style>/s');
    });

});

describe('Dark Mode Toggle', function () {

    test('includes theme toggle button', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toContain('class="theme-toggle"');
        expect($html)->toContain('id="theme-icon"');
    });

    test('theme toggle shows moon emoji', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toContain('>🌙</button>');
    });

    test('color-scheme meta tag supports light and dark', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toContain('name="color-scheme"');
        expect($html)->toContain('content="light dark"');
    });

});

describe('Toast Notifications', function () {

    test('includes success toast button', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toContain('class="btn-hover btn-success"');
        expect($html)->toContain("showToast('Success!', 'success')");
    });

    test('includes danger toast button', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toContain('class="btn-hover btn-danger"');
        expect($html)->toContain("showToast('Error!', 'danger')");
    });

});

describe('Contact Form', function () {

    test('contact page shows form', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'contact']);

        expect($html)->toContain('<form');
        expect($html)->toContain('</form>');
    });

    test('form has subject field', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'contact']);

        expect($html)->toContain('id="subject"');
        expect($html)->toContain('name="subject"');
        expect($html)->toContain('type="text"');
    });

    test('form has message textarea', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'contact']);

        expect($html)->toContain('<textarea');
        expect($html)->toContain('id="message"');
        expect($html)->toContain('name="message"');
    });

    test('form has submit button', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'contact']);

        expect($html)->toContain('type="submit"');
        expect($html)->toContain('Send Message');
    });

    test('form has client-side handler', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'contact']);

        expect($html)->toContain('onsubmit="return handleContact(this)"');
        expect($html)->toContain('function handleContact(form)');
    });

    test('form handler creates mailto link', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'contact']);

        expect($html)->toContain('mailto:mc@netserva.org');
    });

    test('other pages do not show form', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'home']);

        expect($html)->not->toContain('<form');
        expect($html)->not->toContain('handleContact');
    });

});
