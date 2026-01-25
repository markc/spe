<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

/**
 * Feature Tests for 01-Simple/public/index.php
 *
 * Tests complete user scenarios and integration:
 * - Full page rendering for each route
 * - Complete HTML document validity
 * - User journey scenarios
 * - Edge cases and error handling
 */

describe('Full Page Rendering', function () {

    test('renders complete home page', function () {
        $html = renderPage(simpleIndexPath(), ['m' => 'home']);

        // Document structure
        expect($html)->toContain('<!DOCTYPE html>');
        expect($html)->toContain('<html lang="en">');
        expect($html)->toContain('</html>');

        // Head section
        expect($html)->toContain('<head>');
        expect($html)->toContain('<title>SPE::01</title>');
        expect($html)->toContain('</head>');

        // Body with all sections
        expect($html)->toContain('<body>');
        expect($html)->toContain('<header>');
        expect($html)->toContain('<nav>');
        expect($html)->toContain('<main>');
        expect($html)->toContain('<footer>');
        expect($html)->toContain('</body>');

        // Page-specific content
        expect($html)->toContain('<h2>Home</h2>');
    });

    test('renders complete about page', function () {
        $html = renderPage(simpleIndexPath(), ['m' => 'about']);

        expect($html)->toContain('<h2>About</h2>');
        expect($html)->toContain('This is the <b>About Page</b>');

        // Navigation still present
        expect($html)->toContain('<a href="?m=home">Home</a>');
        expect($html)->toContain('<a href="?m=about">About</a>');
        expect($html)->toContain('<a href="?m=contact">Contact</a>');
    });

    test('renders complete contact page', function () {
        $html = renderPage(simpleIndexPath(), ['m' => 'contact']);

        expect($html)->toContain('<h2>Contact</h2>');
        expect($html)->toContain('This is the <b>Contact Page</b>');
    });

    test('renders error page for unknown routes', function () {
        $html = renderPage(simpleIndexPath(), ['m' => 'unknown']);

        // Still renders full page structure
        expect($html)->toContain('<!DOCTYPE html>');
        expect($html)->toContain('<header>');
        expect($html)->toContain('<nav>');
        expect($html)->toContain('<main>');
        expect($html)->toContain('<footer>');

        // Error message in main content
        expect($html)->toContain('Error: page not found');
    });

});

describe('User Journey Scenarios', function () {

    test('user can navigate from home to about', function () {
        // User starts at home
        $homeHtml = renderPage(simpleIndexPath(), ['m' => 'home']);
        expect($homeHtml)->toContain('<h2>Home</h2>');
        expect($homeHtml)->toContain('<a href="?m=about">About</a>');

        // User clicks about link
        $aboutHtml = renderPage(simpleIndexPath(), ['m' => 'about']);
        expect($aboutHtml)->toContain('<h2>About</h2>');
    });

    test('user can navigate through all pages', function () {
        $pages = ['home', 'about', 'contact'];

        foreach ($pages as $page) {
            $html = renderPage(simpleIndexPath(), ['m' => $page]);
            expect($html)->toContain('<h2>' . ucfirst($page) . '</h2>');
        }
    });

    test('navigation is consistent across all pages', function () {
        $pages = ['home', 'about', 'contact'];

        foreach ($pages as $page) {
            $html = renderPage(simpleIndexPath(), ['m' => $page]);

            // All navigation links present on every page
            expect($html)->toContain('<a href="?m=home">Home</a>');
            expect($html)->toContain('<a href="?m=about">About</a>');
            expect($html)->toContain('<a href="?m=contact">Contact</a>');
        }
    });

    test('header link returns to root', function () {
        $html = renderPage(simpleIndexPath(), ['m' => 'about']);

        expect($html)->toContain('<a href="../">');
    });

});

describe('Edge Cases', function () {

    test('handles case-sensitive page names', function () {
        // 'Home' with capital H should not match 'home'
        $html = renderPage(simpleIndexPath(), ['m' => 'Home']);

        expect($html)->toContain('Error: page not found');
    });

    test('handles numeric page parameter', function () {
        $html = renderPage(simpleIndexPath(), ['m' => '123']);

        expect($html)->toContain('Error: page not found');
    });

    test('handles very long page parameter', function () {
        $longString = str_repeat('a', 1000);
        $html = renderPage(simpleIndexPath(), ['m' => $longString]);

        expect($html)->toContain('Error: page not found');
        // Page should still render without crashing
        expect($html)->toContain('<!DOCTYPE html>');
    });

    test('handles unicode in page parameter', function () {
        $html = renderPage(simpleIndexPath(), ['m' => '日本語']);

        expect($html)->toContain('Error: page not found');
    });

    test('handles null bytes in page parameter', function () {
        $html = renderPage(simpleIndexPath(), ['m' => "home\0evil"]);

        // Should not match 'home' due to null byte
        expect($html)->toContain('Error: page not found');
    });

});

describe('Security', function () {

    test('prevents HTML injection in page parameter', function () {
        $html = renderPage(simpleIndexPath(), ['m' => '<div>injected</div>']);

        expect($html)->not->toContain('<div>injected</div>');
    });

    test('prevents script injection', function () {
        $html = renderPage(simpleIndexPath(), ['m' => '<script>alert("xss")</script>']);

        expect($html)->not->toContain('<script>alert("xss")</script>');
    });

    test('escapes ampersands in parameter', function () {
        $html = renderPage(simpleIndexPath(), ['m' => 'test&param=value']);

        // The & should be escaped to &amp; by htmlspecialchars
        expect($html)->not->toContain('test&param');
    });

    test('escapes quotes in parameter', function () {
        $html = renderPage(simpleIndexPath(), ['m' => 'test"onclick="alert(1)"']);

        expect($html)->not->toContain('onclick=');
    });

});

describe('Performance Characteristics', function () {

    test('page renders in reasonable time', function () {
        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            renderPage(simpleIndexPath(), ['m' => 'home']);
        }

        $elapsed = microtime(true) - $start;

        // 100 renders should complete in under 1 second
        expect($elapsed)->toBeLessThan(1.0);
    });

    test('output size is reasonable', function () {
        $html = renderPage(simpleIndexPath(), ['m' => 'home']);

        // Page should be compact (under 2KB for this simple example)
        expect(strlen($html))->toBeLessThan(2000);
    });

});
