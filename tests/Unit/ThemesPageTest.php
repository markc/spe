<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

/**
 * Unit Tests for 04-Themes/public/index.php
 *
 * Tests individual components and logic in isolation:
 * - Plugin routing with Model/View separation
 * - Theme switching
 * - Navigation and dropdown generation
 * - Input sanitization
 * - JSON API output
 *
 * NOTE: 03-Plugins and 04-Themes share class names (Ctx, Init, Plugin).
 * Tests may be skipped if conflicting classes are already loaded.
 */

// Skip all tests if 03-Plugins classes are loaded (they conflict)
beforeEach(function () {
    if (class_exists('Ctx') && !isChapterLoaded(4)) {
        $this->markTestSkipped('04-Themes classes conflict with already-loaded 03-Plugins classes');
    }
});

describe('Plugin Routing', function () {

    test('defaults to Home plugin with Simple theme', function () {
        $html = renderPage(themesIndexPath(), []);

        expect($html)->toContain('Home Page');
        expect($html)->toContain('[Simple]');
    });

    test('routes to Home plugin', function () {
        $html = renderPage(themesIndexPath(), ['o' => 'Home']);

        expect($html)->toContain('Home Page');
    });

    test('routes to About plugin', function () {
        $html = renderPage(themesIndexPath(), ['o' => 'About']);

        expect($html)->toContain('About Page');
    });

    test('routes to Contact plugin', function () {
        $html = renderPage(themesIndexPath(), ['o' => 'Contact']);

        expect($html)->toContain('Contact Page');
    });

});

describe('Theme Switching', function () {

    test('defaults to Simple theme', function () {
        $html = renderPage(themesIndexPath(), []);

        expect($html)->toContain('[Simple]');
        expect($html)->toContain('<div class="container">');
    });

    test('switches to TopNav theme', function () {
        $html = renderPage(themesIndexPath(), ['t' => 'TopNav']);

        expect($html)->toContain('[TopNav]');
        expect($html)->toContain('<nav class="topnav">');
        expect($html)->toContain('topnav-links');
    });

    test('switches to SideBar theme', function () {
        $html = renderPage(themesIndexPath(), ['t' => 'SideBar']);

        expect($html)->toContain('[SideBar]');
        expect($html)->toContain('sidebar-layout');
        expect($html)->toContain('<aside class="sidebar">');
    });

    test('theme persists across plugin navigation', function () {
        $html = renderPage(themesIndexPath(), ['o' => 'About', 't' => 'TopNav']);

        expect($html)->toContain('About Page');
        expect($html)->toContain('[TopNav]');
    });

});

describe('Model/View Separation', function () {

    test('model returns data array', function () {
        $html = renderPage(themesIndexPath(), ['o' => 'Home']);

        expect($html)->toContain('Home Page');
        expect($html)->toContain('Themes');
    });

    test('view renders model data', function () {
        $html = renderPage(themesIndexPath(), ['o' => 'About']);

        expect($html)->toContain('<h2>About Page</h2>');
        expect($html)->toContain('Model/View separation');
    });

    test('HomeView includes toast buttons', function () {
        $html = renderPage(themesIndexPath(), ['o' => 'Home']);

        expect($html)->toContain('btn-success');
        expect($html)->toContain('btn-danger');
    });

    test('ContactView includes form', function () {
        $html = renderPage(themesIndexPath(), ['o' => 'Contact']);

        expect($html)->toContain('<form');
        expect($html)->toContain('Send Message');
    });

});

describe('Navigation Generation', function () {

    test('generates navigation links', function () {
        $html = renderPage(themesIndexPath(), []);

        expect($html)->toContain('🏠 Home');
        expect($html)->toContain('📖 About');
        expect($html)->toContain('✉️ Contact');
    });

    test('navigation includes theme parameter', function () {
        $html = renderPage(themesIndexPath(), ['t' => 'TopNav']);

        expect($html)->toContain('href="?o=Home&t=TopNav"');
        expect($html)->toContain('href="?o=About&t=TopNav"');
    });

    test('current plugin has active class', function () {
        $html = renderPage(themesIndexPath(), ['o' => 'About', 't' => 'Simple']);

        expect($html)->toMatch('/<a href="\?o=About&t=Simple" class="active">/');
    });

});

describe('Theme Dropdown', function () {

    test('Simple theme shows dropdown menu', function () {
        $html = renderPage(themesIndexPath(), ['t' => 'Simple']);

        expect($html)->toContain('class="dropdown"');
        expect($html)->toContain('🎨 Themes');
        expect($html)->toContain('dropdown-menu');
    });

    test('TopNav theme shows dropdown menu', function () {
        $html = renderPage(themesIndexPath(), ['t' => 'TopNav']);

        expect($html)->toContain('class="dropdown"');
        expect($html)->toContain('🎨 Themes');
    });

    test('dropdown contains all theme options', function () {
        $html = renderPage(themesIndexPath(), []);

        expect($html)->toContain('🎨 Simple');
        expect($html)->toContain('🎨 TopNav');
        expect($html)->toContain('🎨 SideBar');
    });

    test('current theme has active class in dropdown', function () {
        $html = renderPage(themesIndexPath(), ['t' => 'TopNav']);

        expect($html)->toMatch('/href="\?o=Home&t=TopNav" class="active".*🎨 TopNav/s');
    });

    test('SideBar theme shows themes in sidebar', function () {
        $html = renderPage(themesIndexPath(), ['t' => 'SideBar']);

        expect($html)->toContain('sidebar-group-title');
        expect($html)->toContain('Themes');
    });

});

describe('Input Sanitization', function () {

    test('trims whitespace from plugin parameter', function () {
        $html = renderPage(themesIndexPath(), ['o' => '  About  ']);

        expect($html)->toContain('About Page');
    });

    test('escapes HTML in plugin parameter', function () {
        $html = renderPage(themesIndexPath(), ['o' => '<script>alert(1)</script>']);

        expect($html)->not->toContain('<script>alert(1)</script>');
    });

    // Note: Invalid theme parameters cause PHP errors (method not found)
    // Theme names must match existing methods on Theme class

});

describe('JSON API Output', function () {

    test('json output mode returns valid JSON', function () {
        $output = renderPage(themesIndexPath(), ['x' => 'json']);

        $data = json_decode($output, true);
        expect(json_last_error())->toBe(JSON_ERROR_NONE);
    });

    test('json output contains doc and main', function () {
        $output = renderPage(themesIndexPath(), ['x' => 'json']);

        $data = json_decode($output, true);
        expect($data)->toHaveKey('doc');
        expect($data)->toHaveKey('main');
        expect($data['doc'])->toBe('SPE::04');
    });

    test('json output contains head from model', function () {
        $output = renderPage(themesIndexPath(), ['o' => 'About', 'x' => 'json']);

        $data = json_decode($output, true);
        expect($data)->toHaveKey('head');
        expect($data['head'])->toBe('About Page');
    });

});

describe('Dynamic Title', function () {

    test('title includes chapter and theme name', function () {
        $html = renderPage(themesIndexPath(), ['t' => 'Simple']);

        expect($html)->toContain('<title>SPE::04 [Simple]</title>');
    });

    test('TopNav theme shows in title', function () {
        $html = renderPage(themesIndexPath(), ['t' => 'TopNav']);

        expect($html)->toContain('<title>SPE::04 [TopNav]</title>');
    });

    test('SideBar theme shows in title', function () {
        $html = renderPage(themesIndexPath(), ['t' => 'SideBar']);

        expect($html)->toContain('<title>SPE::04 [SideBar]</title>');
    });

});
