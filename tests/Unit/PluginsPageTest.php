<?php
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

/**
 * Unit Tests for 03-Plugins/public/index.php
 *
 * Tests individual components and logic in isolation:
 * - Plugin routing with ?o= parameter
 * - Method dispatch with ?m= parameter
 * - CRUDL method defaults
 * - Input sanitization
 * - Navigation generation
 * - JSON API output
 */

describe('Plugin Routing', function () {

    test('defaults to Home plugin when no parameter provided', function () {
        $html = renderPage(pluginsIndexPath(), []);

        expect($html)->toContain('Home Page');
        expect($html)->toContain('plugin architecture');
    });

    test('routes to Home plugin', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home']);

        expect($html)->toContain('Home Page');
    });

    test('routes to About plugin', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'About']);

        expect($html)->toContain('About Page');
        expect($html)->toContain('plugin architecture');
    });

    test('routes to Contact plugin', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Contact']);

        expect($html)->toContain('Contact Page');
        expect($html)->toContain('email form');
    });

    test('shows error for invalid plugin', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'NonExistent']);

        expect($html)->toContain('Error: plugin not found');
    });

    test('plugin names are case-insensitive in PHP', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'home']);

        // PHP class names are case-insensitive
        expect($html)->toContain('Home Page');
    });

});

describe('Method Dispatch', function () {

    test('defaults to list method', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home']);

        expect($html)->toContain('Home Page');
    });

    test('explicit list method works', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home', 'm' => 'list']);

        expect($html)->toContain('Home Page');
    });

    test('create method returns not implemented', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home', 'm' => 'create']);

        expect($html)->toContain('Create: not implemented');
    });

    test('read method returns not implemented', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home', 'm' => 'read']);

        expect($html)->toContain('Read: not implemented');
    });

    test('update method returns not implemented', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home', 'm' => 'update']);

        expect($html)->toContain('Update: not implemented');
    });

    test('delete method returns not implemented', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home', 'm' => 'delete']);

        expect($html)->toContain('Delete: not implemented');
    });

    test('shows error for invalid method', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home', 'm' => 'invalid']);

        expect($html)->toContain('Error: method not found');
    });

});

describe('Input Sanitization', function () {

    test('trims whitespace from plugin parameter', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => '  About  ']);

        expect($html)->toContain('About Page');
    });

    test('trims whitespace from method parameter', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home', 'm' => '  list  ']);

        expect($html)->toContain('Home Page');
    });

    test('escapes HTML in plugin parameter', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => '<script>alert(1)</script>']);

        expect($html)->toContain('Error: plugin not found');
        expect($html)->not->toContain('<script>alert(1)</script>');
    });

    test('escapes HTML in method parameter', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home', 'm' => '<script>']);

        expect($html)->toContain('Error: method not found');
    });

});

describe('Navigation Generation', function () {

    test('generates navigation with all plugins', function () {
        $html = renderPage(pluginsIndexPath(), []);

        expect($html)->toContain('🏠 Home');
        expect($html)->toContain('📖 About');
        expect($html)->toContain('✉️ Contact');
    });

    test('navigation uses ?o= parameter', function () {
        $html = renderPage(pluginsIndexPath(), []);

        expect($html)->toContain('href="?o=Home"');
        expect($html)->toContain('href="?o=About"');
        expect($html)->toContain('href="?o=Contact"');
    });

    test('current plugin has active class', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'About']);

        expect($html)->toMatch('/<a href="\?o=About" class="active">/');
    });

    test('other plugins do not have active class', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'About']);

        expect($html)->toMatch('/<a href="\?o=Home">/');
        expect($html)->not->toMatch('/<a href="\?o=Home" class="active">/');
    });

});

describe('JSON API Output', function () {

    test('json output mode returns JSON', function () {
        $output = renderPage(pluginsIndexPath(), ['x' => 'json']);

        $data = json_decode($output, true);
        expect($data)->toBeArray();
    });

    test('json output contains doc key', function () {
        $output = renderPage(pluginsIndexPath(), ['x' => 'json']);

        $data = json_decode($output, true);
        expect($data)->toHaveKey('doc');
        expect($data['doc'])->toBe('SPE::03');
    });

    test('json output contains main content', function () {
        $output = renderPage(pluginsIndexPath(), ['o' => 'Home', 'x' => 'json']);

        $data = json_decode($output, true);
        expect($data)->toHaveKey('main');
        expect($data['main'])->toContain('Home Page');
    });

    test('json output for About plugin', function () {
        $output = renderPage(pluginsIndexPath(), ['o' => 'About', 'x' => 'json']);

        $data = json_decode($output, true);
        expect($data['main'])->toContain('About Page');
    });

});

describe('Dynamic Title', function () {

    test('title includes plugin name', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home']);

        expect($html)->toContain('<title>SPE::03 Home</title>');
    });

    test('About page has correct title', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'About']);

        expect($html)->toContain('<title>SPE::03 About</title>');
    });

    test('Contact page has correct title', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Contact']);

        expect($html)->toContain('<title>SPE::03 Contact</title>');
    });

});

describe('Contact Form', function () {

    test('contact page shows form', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Contact']);

        expect($html)->toContain('<form');
        expect($html)->toContain('</form>');
    });

    test('form has subject and message fields', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Contact']);

        expect($html)->toContain('id="subject"');
        expect($html)->toContain('id="message"');
    });

    test('form uses context email', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Contact']);

        expect($html)->toContain('mailto:mc@netserva.org');
    });

    test('other plugins do not show form', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home']);

        expect($html)->not->toContain('<form');
    });

});

describe('Toast Notifications', function () {

    test('Home page has toast buttons', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'Home']);

        expect($html)->toContain('btn-success');
        expect($html)->toContain('btn-danger');
        expect($html)->toContain('showToast');
    });

    test('About page does not have toast buttons', function () {
        $html = renderPage(pluginsIndexPath(), ['o' => 'About']);

        expect($html)->not->toContain('btn-success');
    });

});
