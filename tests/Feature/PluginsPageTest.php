<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

/**
 * Feature Tests for 03-Plugins/public/index.php
 *
 * Tests complete user scenarios and integration:
 * - Full page rendering with plugin system
 * - CRUDL workflow patterns
 * - API output modes
 * - User journey through plugins
 * - Error handling
 * - Security
 */

describe('Full Page Rendering', function () {

    test('renders complete Home page with all sections', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home']);

        // Document structure
        expect($html)->toContain('<!DOCTYPE html>');
        expect($html)->toContain('<html lang="en">');

        // Head with assets
        expect($html)->toContain('<title>SPE::03 Home</title>');
        expect($html)->toContain('href="/site.css"');

        // Body with container
        expect($html)->toContain('<div class="container">');
        expect($html)->toContain('<header>');
        expect($html)->toContain('<nav');
        expect($html)->toContain('<main class="mt-4 mb-4">');
        expect($html)->toContain('<footer');

        // Plugin content
        expect($html)->toContain('Home Page');
        expect($html)->toContain('src="/base.js"');
    });

    test('renders complete About page', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'About']);

        expect($html)->toContain('<title>SPE::03 About</title>');
        expect($html)->toContain('About Page');
        expect($html)->toContain('plugin architecture');
    });

    test('renders complete Contact page with form', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Contact']);

        expect($html)->toContain('<title>SPE::03 Contact</title>');
        expect($html)->toContain('Contact Page');
        expect($html)->toContain('<form');
        expect($html)->toContain('Send Message');
    });

    test('renders error page for invalid plugin', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Invalid']);

        expect($html)->toContain('<!DOCTYPE html>');
        expect($html)->toContain('Error: plugin not found');
    });

    test('renders error page for invalid method', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home', 'm' => 'invalid']);

        expect($html)->toContain('<!DOCTYPE html>');
        expect($html)->toContain('Error: method not found');
    });

});

describe('CRUDL Workflow', function () {

    test('list is the default method', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home']);

        expect($html)->toContain('Home Page');
        expect($html)->not->toContain('not implemented');
    });

    test('all CRUDL methods are accessible', function () {
        $methods = ['create', 'read', 'update', 'delete', 'list'];

        foreach ($methods as $method) {
            $html = renderPage(pluginsIndexPath(), ['o' => 'Home', 'm' => $method]);

            if ($method === 'list') {
                expect($html)->toContain('Home Page');
            } else {
                expect($html)->toContain(ucfirst($method) . ': not implemented');
            }
        }
    });

    test('CRUDL returns proper content in main area', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home', 'm' => 'create']);

        expect($html)->toMatch('/<main.*>.*Create: not implemented.*<\/main>/s');
    });

});

describe('API Output Modes', function () {

    test('default mode returns HTML', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home']);

        expect($html)->toContain('<!DOCTYPE html>');
        expect($html)->toContain('<html');
    });

    test('json mode returns valid JSON', function () {
        $output = renderPage(pluginsIndexPath(), ['o' => 'Home', 'x' => 'json']);

        $data = json_decode($output, true);
        expect(json_last_error())->toBe(JSON_ERROR_NONE);
        expect($data)->toBeArray();
    });

    test('json mode includes all output keys', function () {
        $output = renderPage(pluginsIndexPath(), ['o' => 'Home', 'x' => 'json']);

        $data = json_decode($output, true);
        expect($data)->toHaveKey('doc');
        expect($data)->toHaveKey('nav');
        expect($data)->toHaveKey('head');
        expect($data)->toHaveKey('main');
        expect($data)->toHaveKey('foot');
    });

    test('json mode includes plugin content', function () {
        $output = renderPage(pluginsIndexPath(), ['o' => 'About', 'x' => 'json']);

        $data = json_decode($output, true);
        expect($data['main'])->toContain('About Page');
    });

    test('json mode works with different plugins', function () {
        foreach (['Home', 'About', 'Contact'] as $plugin) {
            $output = renderPage(pluginsIndexPath(), ['o' => $plugin, 'x' => 'json']);
            $data = json_decode($output, true);

            expect($data['main'])->toContain("{$plugin} Page");
        }
    });

    test('json mode includes error for invalid plugin', function () {
        $output = renderPage(pluginsIndexPath(), ['o' => 'Invalid', 'x' => 'json']);

        $data = json_decode($output, true);
        expect($data['main'])->toContain('Error: plugin not found');
    });

});

describe('User Journey Scenarios', function () {

    test('user can navigate between all plugins', function () {
        $plugins = ['Home', 'About', 'Contact'];

        foreach ($plugins as $plugin) {
            $html = renderPage(pluginsIndexPath(), ['o' => $plugin]);

            expect($html)->toContain("{$plugin} Page");

            // All navigation links present
            expect($html)->toContain('href="?o=Home"');
            expect($html)->toContain('href="?o=About"');
            expect($html)->toContain('href="?o=Contact"');
        }
    });

    test('active state follows current plugin', function () {
        foreach (['Home', 'About', 'Contact'] as $plugin) {
            $html = renderPage(pluginsIndexPath(), ['o' => $plugin]);

            expect($html)->toMatch("/<a href=\"\\?o={$plugin}\" class=\"active\">/");
        }
    });

    test('user can access CRUDL methods via URL', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home', 'm' => 'create']);

        expect($html)->toContain('Create: not implemented');
    });

    test('brand link returns to root', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'About']);

        expect($html)->toContain('<a class="brand" href="/">');
        expect($html)->toContain('🐘 Plugins PHP Example');
    });

});

describe('Plugin Architecture', function () {

    test('each plugin renders in card container', function () {
        foreach (['Home', 'About', 'Contact'] as $plugin) {
            $html = renderPage(pluginsIndexPath(), ['o' => $plugin]);

            expect($html)->toContain('<div class="card-hover">');
        }
    });

    test('plugins have h2 headings', function () {
        foreach (['Home', 'About', 'Contact'] as $plugin) {
            $html = renderPage(pluginsIndexPath(), ['o' => $plugin]);

            expect($html)->toContain("<h2>{$plugin} Page</h2>");
        }
    });

    test('plugins inherit from abstract Plugin class', function () {
        // Test that unimplemented methods return default message
        $html = renderPage(pluginsIndexPath(), ['o' => 'About', 'm' => 'create']);

        expect($html)->toContain('Create: not implemented');
    });

});

describe('Edge Cases', function () {

    test('handles empty plugin parameter', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => '']);

        // Empty string is not a valid class name
        expect($html)->toContain('Error: plugin not found');
    });

    test('handles whitespace-only plugin parameter', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => '   ']);

        // Trimmed to empty, not a valid class name
        expect($html)->toContain('Error: plugin not found');
    });

    test('handles very long plugin parameter', function () {
        $longString = str_repeat('A', 1000);
        $html = renderPage(pluginsIndexPath(), ['o' => $longString]);

        expect($html)->toContain('Error: plugin not found');
        expect($html)->toContain('<!DOCTYPE html>');
    });

    test('handles unicode in plugin parameter', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => '日本語']);

        expect($html)->toContain('Error: plugin not found');
    });

    test('handles path traversal attempts', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => '../../../etc/passwd']);

        expect($html)->toContain('Error: plugin not found');
    });

});

describe('Security', function () {

    test('prevents script injection in plugin parameter', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => '<script>alert("xss")</script>']);

        expect($html)->not->toContain('<script>alert("xss")</script>');
    });

    test('prevents script injection in method parameter', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home', 'm' => '<script>alert("xss")</script>']);

        expect($html)->not->toContain('<script>alert("xss")</script>');
    });

    test('prevents HTML injection in plugin parameter', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => '<div onclick="evil()">click</div>']);

        expect($html)->not->toContain('<div onclick="evil()">click</div>');
    });

    test('escapes quotes in parameters', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => '" onclick="alert(1)"']);

        expect($html)->not->toContain('onclick="alert(1)"');
    });

    test('class_exists check prevents arbitrary class instantiation', function () {
        // Try to instantiate a PHP built-in class
        $html = renderPage(pluginsIndexPath(), ['o' => 'stdClass']);

        // Should fail because stdClass doesn't have the required methods
        expect($html)->toContain('Error: method not found');
    });

});

describe('Comparison with Previous Chapters', function () {

    test('uses ?o= instead of ?m= for routing', function () {
        $html = renderPage(pluginsIndexPath(), []);

        expect($html)->toContain('href="?o=');
        expect($html)->not->toMatch('/href="\?m=home"/i');
    });

    test('has plugin-based architecture', function () {
        // Can route to different plugins
        $home = renderPage(pluginsIndexPath(), ['o' => 'Home']);
        $about = renderPage(pluginsIndexPath(), ['o' => 'About']);

        expect($home)->toContain('Home Page');
        expect($about)->toContain('About Page');
    });

    test('supports JSON API output', function () {
        $styled = renderPage(styledIndexPath(), ['m' => 'home']);
        $plugins = renderPage(pluginsIndexPath(), ['o' => 'Home', 'x' => 'json']);

        // Styled doesn't have JSON mode
        expect($styled)->toContain('<!DOCTYPE html>');

        // Plugins supports JSON
        $data = json_decode($plugins, true);
        expect($data)->toBeArray();
    });

});

describe('Performance Characteristics', function () {

    test('page renders in reasonable time', function () {
        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            renderPage(pluginsIndexPath(), ['o' => 'Home']);
        }

        $elapsed = microtime(true) - $start;

        expect($elapsed)->toBeLessThan(1.0);
    });

    test('JSON output is compact', function () {
        $output = renderPage(pluginsIndexPath(), ['o' => 'Home', 'x' => 'json']);

        expect(strlen($output))->toBeLessThan(2000);
    });

    test('HTML output size is reasonable', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home']);

        expect(strlen($html))->toBeLessThan(3000);
    });

});
