<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

/**
 * Feature Tests for 02-Styled/public/index.php
 *
 * Tests complete user scenarios and integration:
 * - Full page rendering with styling
 * - Container-based layout structure
 * - User journey through pages
 * - Contact form workflow
 * - Edge cases and security
 */

describe('Full Page Rendering', function () {

    test('renders complete home page with all sections', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'home']);

        // Document structure
        expect($html)->toContain('<!DOCTYPE html>');
        expect($html)->toContain('<html lang="en">');
        expect($html)->toContain('</html>');

        // Head section with assets
        expect($html)->toContain('<head>');
        expect($html)->toContain('<title>SPE::02 Home Page</title>');
        expect($html)->toContain('href="/site.css"');
        expect($html)->toContain('</head>');

        // Body with container layout
        expect($html)->toContain('<body>');
        expect($html)->toContain('<div class="container">');
        expect($html)->toContain('<header');
        expect($html)->toContain('<nav');
        expect($html)->toContain('<main class="mt-4 mb-4">');
        expect($html)->toContain('<footer');
        expect($html)->toContain('</div>');
        expect($html)->toContain('src="/base.js"');
        expect($html)->toContain('</body>');

        // Page-specific content
        expect($html)->toContain('Home Page');
        expect($html)->toContain('Styled');
    });

    test('renders complete about page', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'about']);

        expect($html)->toContain('<title>SPE::02 About Page</title>');
        expect($html)->toContain('<h2>About Page</h2>');
        expect($html)->toContain('dark mode');
        expect($html)->toContain('toast</b> notifications');
    });

    test('renders complete contact page with form', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'contact']);

        expect($html)->toContain('<title>SPE::02 Contact Page</title>');
        expect($html)->toContain('<h2>Contact Page</h2>');
        expect($html)->toContain('<form');
        expect($html)->toContain('Subject');
        expect($html)->toContain('Message');
        expect($html)->toContain('Send Message');
    });

    test('invalid route defaults to home page', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'invalid']);

        expect($html)->toContain('<title>SPE::02 Home Page</title>');
        expect($html)->toContain('Welcome to the <b>Styled</b> chapter');
    });

});

describe('Container Layout Structure', function () {

    test('uses container div for layout', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toContain('<div class="container">');
    });

    test('header contains brand link', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toContain('<header');
        expect($html)->toContain('<a class="brand" href="/">');
        expect($html)->toContain('Styled PHP Example');
    });

    test('main content is wrapped in card', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toMatch('/<main.*>.*<div class="card-hover">.*<\/div>.*<\/main>/s');
    });

    test('footer has copyright and centered text', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toContain('<footer class="text-center">');
        expect($html)->toContain('© 2015-2026 Mark Constable');
        expect($html)->toContain('MIT License');
    });

    test('toast buttons are centered below content', function () {
        $html = renderPage(styledIndexPath(), []);

        expect($html)->toContain('<div class="flex justify-center mt-4">');
        expect($html)->toContain('btn-success');
        expect($html)->toContain('btn-danger');
    });

});

describe('User Journey Scenarios', function () {

    test('user can navigate from home to about', function () {
        $homeHtml = renderPage(styledIndexPath(), ['m' => 'home']);
        expect($homeHtml)->toContain('Home Page');
        expect($homeHtml)->toContain('href="?m=about"');

        $aboutHtml = renderPage(styledIndexPath(), ['m' => 'about']);
        expect($aboutHtml)->toContain('About Page');
    });

    test('user can navigate from about to contact', function () {
        $aboutHtml = renderPage(styledIndexPath(), ['m' => 'about']);
        expect($aboutHtml)->toContain('href="?m=contact"');

        $contactHtml = renderPage(styledIndexPath(), ['m' => 'contact']);
        expect($contactHtml)->toContain('Contact Page');
        expect($contactHtml)->toContain('<form');
    });

    test('user can navigate through all pages', function () {
        $pages = [
            'home' => 'Home Page',
            'about' => 'About Page',
            'contact' => 'Contact Page',
        ];

        foreach ($pages as $page => $title) {
            $html = renderPage(styledIndexPath(), ['m' => $page]);
            expect($html)->toContain($title);
            expect($html)->toContain("<h2>{$title}</h2>");
        }
    });

    test('active state follows current page', function () {
        foreach (['home', 'about', 'contact'] as $page) {
            $html = renderPage(styledIndexPath(), ['m' => $page]);
            expect($html)->toMatch("/<a href=\"\\?m={$page}\" class=\"active\">/");
        }
    });

    test('brand link returns to root', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'about']);

        expect($html)->toContain('<a class="brand" href="/">');
    });

});

describe('Contact Form Workflow', function () {

    test('contact form is complete and functional', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'contact']);

        // Form structure
        expect($html)->toContain('<form class="mt-2"');
        expect($html)->toContain('onsubmit="return handleContact(this)"');

        // Subject field
        expect($html)->toContain('<label for="subject">Subject</label>');
        expect($html)->toContain('<input type="text" id="subject" name="subject" required>');

        // Message field
        expect($html)->toContain('<label for="message">Message</label>');
        expect($html)->toContain('<textarea id="message" name="message" rows="4" required>');

        // Submit
        expect($html)->toContain('<button type="submit" class="btn">Send Message</button>');
    });

    test('form handler includes email and toast', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'contact']);

        expect($html)->toContain('function handleContact(form)');
        expect($html)->toContain("location.href = 'mailto:mc@netserva.org");
        expect($html)->toContain('encodeURIComponent(form.subject.value)');
        expect($html)->toContain('encodeURIComponent(form.message.value)');
        expect($html)->toContain("showToast('Opening email client...', 'success')");
        expect($html)->toContain('return false');
    });

});

describe('Edge Cases', function () {

    test('handles case-sensitive page names', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'Home']);

        // 'Home' with capital H should default to home
        expect($html)->toContain('Home Page');
    });

    test('handles very long page parameter', function () {
        $longString = str_repeat('a', 1000);
        $html = renderPage(styledIndexPath(), ['m' => $longString]);

        expect($html)->toContain('Home Page');
        expect($html)->toContain('<!DOCTYPE html>');
    });

    test('handles unicode in page parameter', function () {
        $html = renderPage(styledIndexPath(), ['m' => '日本語']);

        expect($html)->toContain('Home Page');
    });

    test('handles special characters in page parameter', function () {
        $html = renderPage(styledIndexPath(), ['m' => '../../../etc/passwd']);

        expect($html)->toContain('Home Page');
    });

});

describe('Security', function () {

    test('prevents script injection in page parameter', function () {
        $html = renderPage(styledIndexPath(), ['m' => '<script>alert("xss")</script>']);

        expect($html)->not->toContain('<script>alert("xss")</script>');
        expect($html)->toContain('Home Page');
    });

    test('prevents HTML injection in page parameter', function () {
        $html = renderPage(styledIndexPath(), ['m' => '<div onclick="evil()">click</div>']);

        expect($html)->not->toContain('<div onclick="evil()">click</div>');
    });

    test('escapes quotes in parameter', function () {
        $html = renderPage(styledIndexPath(), ['m' => '" onclick="alert(1)"']);

        expect($html)->not->toContain('onclick="alert(1)"');
    });

});

describe('Comparison with 01-Simple', function () {

    test('uses external CSS instead of inline styles', function () {
        $styledHtml = renderPage(styledIndexPath(), []);
        $simpleHtml = renderPage(simpleIndexPath(), []);

        // Styled uses external CSS
        expect($styledHtml)->toContain('href="/site.css"');
        expect($styledHtml)->not->toContain('<style>body{');

        // Simple uses inline styles
        expect($simpleHtml)->toContain('<style>');
        expect($simpleHtml)->not->toContain('href="/site.css"');
    });

    test('uses external JS instead of no JS', function () {
        $styledHtml = renderPage(styledIndexPath(), []);
        $simpleHtml = renderPage(simpleIndexPath(), []);

        expect($styledHtml)->toContain('src="/base.js"');
        expect($simpleHtml)->not->toContain('src="/base.js"');
    });

    test('has container layout vs simple body', function () {
        $styledHtml = renderPage(styledIndexPath(), []);
        $simpleHtml = renderPage(simpleIndexPath(), []);

        expect($styledHtml)->toContain('<div class="container">');
        expect($simpleHtml)->not->toContain('class="container"');
    });

    test('has emoji icons in navigation', function () {
        $styledHtml = renderPage(styledIndexPath(), []);
        $simpleHtml = renderPage(simpleIndexPath(), []);

        expect($styledHtml)->toContain('🏠 Home');
        expect($styledHtml)->toContain('📋 About');
        expect($simpleHtml)->not->toContain('🏠');
    });

});

describe('Performance Characteristics', function () {

    test('page renders in reasonable time', function () {
        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            renderPage(styledIndexPath(), ['m' => 'home']);
        }

        $elapsed = microtime(true) - $start;

        expect($elapsed)->toBeLessThan(1.0);
    });

    test('output size is reasonable', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'home']);

        // Larger than 01-Simple due to more features, but still compact
        expect(strlen($html))->toBeLessThan(3000);
    });

    test('contact page with form is still compact', function () {
        $html = renderPage(styledIndexPath(), ['m' => 'contact']);

        expect(strlen($html))->toBeLessThan(4000);
    });

});
