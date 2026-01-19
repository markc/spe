<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

/**
 * Unit Tests for 01-Simple/public/index.php
 *
 * Tests individual components and logic in isolation:
 * - Page routing and defaults
 * - Input sanitization
 * - Navigation generation
 * - HTML structure
 */

describe('Page Routing', function () {

    test('defaults to home page when no parameter provided', function () {
        $html = renderPage(simpleIndexPath(), []);

        expect($html)->toContain('<h2>Home</h2>');
        expect($html)->toContain('This is the <b>Home Page</b>');
    });

    test('defaults to home page when empty parameter provided', function () {
        $html = renderPage(simpleIndexPath(), ['m' => '']);

        expect($html)->toContain('<h2>Home</h2>');
    });

    test('defaults to home page when whitespace-only parameter provided', function () {
        $html = renderPage(simpleIndexPath(), ['m' => '   ']);

        expect($html)->toContain('<h2>Home</h2>');
    });

    test('routes to about page correctly', function () {
        $html = renderPage(simpleIndexPath(), ['m' => 'about']);

        expect($html)->toContain('<h2>About</h2>');
        expect($html)->toContain('This is the <b>About Page</b>');
    });

    test('routes to contact page correctly', function () {
        $html = renderPage(simpleIndexPath(), ['m' => 'contact']);

        expect($html)->toContain('<h2>Contact</h2>');
        expect($html)->toContain('This is the <b>Contact Page</b>');
    });

    test('shows error for invalid page', function () {
        $html = renderPage(simpleIndexPath(), ['m' => 'nonexistent']);

        expect($html)->toContain('Error: page not found');
    });

});

describe('Input Sanitization', function () {

    test('trims whitespace from page parameter', function () {
        $html = renderPage(simpleIndexPath(), ['m' => '  about  ']);

        expect($html)->toContain('<h2>About</h2>');
    });

    test('escapes HTML in page parameter to prevent XSS', function () {
        $html = renderPage(simpleIndexPath(), ['m' => '<script>alert(1)</script>']);

        // Should show error page, not execute script
        expect($html)->toContain('Error: page not found');
        expect($html)->not->toContain('<script>alert(1)</script>');
    });

    test('escapes special characters in page parameter', function () {
        $html = renderPage(simpleIndexPath(), ['m' => '&<>"\'']);

        expect($html)->toContain('Error: page not found');
    });

});

describe('Navigation Generation', function () {

    test('generates navigation with all pages', function () {
        $html = renderPage(simpleIndexPath(), []);

        expect($html)->toContain('<a href="?m=home">Home</a>');
        expect($html)->toContain('<a href="?m=about">About</a>');
        expect($html)->toContain('<a href="?m=contact">Contact</a>');
    });

    test('navigation links are separated by pipe characters', function () {
        $html = renderPage(simpleIndexPath(), []);

        expect($html)->toContain('</a> | <a');
    });

    test('navigation appears in nav element', function () {
        $html = renderPage(simpleIndexPath(), []);

        expect($html)->toMatch('/<nav>.*<a href="\?m=home">Home<\/a>.*<\/nav>/s');
    });

});

describe('HTML Structure', function () {

    test('outputs valid HTML5 doctype', function () {
        $html = renderPage(simpleIndexPath(), []);

        expect($html)->toMatch('/^\s*<!DOCTYPE html>/i');
    });

    test('includes proper meta charset', function () {
        $html = renderPage(simpleIndexPath(), []);

        expect($html)->toContain('<meta charset="utf-8">');
    });

    test('includes viewport meta tag', function () {
        $html = renderPage(simpleIndexPath(), []);

        expect($html)->toContain('viewport');
        expect($html)->toContain('width=device-width');
    });

    test('includes color-scheme meta for dark mode support', function () {
        $html = renderPage(simpleIndexPath(), []);

        expect($html)->toContain('color-scheme');
        expect($html)->toContain('light dark');
    });

    test('has correct page title', function () {
        $html = renderPage(simpleIndexPath(), []);

        expect($html)->toContain('<title>SPE::01</title>');
    });

    test('includes inline styles', function () {
        $html = renderPage(simpleIndexPath(), []);

        expect($html)->toContain('<style>');
        expect($html)->toContain('</style>');
    });

    test('has header with h1 title', function () {
        $html = renderPage(simpleIndexPath(), []);

        expect($html)->toContain('<header>');
        expect($html)->toMatch('/<h1>.*Simple PHP Example.*<\/h1>/');
    });

    test('has main content area', function () {
        $html = renderPage(simpleIndexPath(), []);

        expect($html)->toContain('<main>');
        expect($html)->toContain('</main>');
    });

    test('has footer with copyright', function () {
        $html = renderPage(simpleIndexPath(), []);

        expect($html)->toContain('<footer>');
        expect($html)->toContain('Copyright');
        expect($html)->toContain('Mark Constable');
        expect($html)->toContain('MIT License');
    });

    test('main content appears between main tags', function () {
        $html = renderPage(simpleIndexPath(), ['m' => 'about']);

        expect($html)->toMatch('/<main>.*<h2>About<\/h2>.*<\/main>/s');
    });

});
