<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

beforeEach(fn() => $this->c = chapter('02-Styled'));

test('home renders inside the app shell', function () {
    $r = $this->c->get();
    expect($r->status)->toBe(200)
        ->and($r->body)->toContain('<title>SPE::02 Home</title>', 'href="../base.css"', 'href="../site.css"', 'src="../base.js"')
        ->and($r->body)->toContain('class="sidebar sidebar-left"', 'class="sidebar sidebar-right"', 'data-scheme="ocean"', 'class="theme-toggle"')
        ->and($r->body)->toContain('<a href="?o=home" class="active"><i data-lucide="home"></i> Home</a>');
});

test('each page renders with its title', function (string $page, string $label) {
    $r = $this->c->get("?o=$page");
    expect($r->status)->toBe(200)->and($r->body)->toContain("<title>SPE::02 $label</title>", "<h2>$label</h2>");
})->with([['home', 'Home'], ['about', 'About'], ['contact', 'Contact']]);

test('contact page has the mailto form', function () {
    expect($this->c->get('?o=contact')->body)->toContain('<form', 'mailto:', 'name="subject"', 'name="message"');
});

test('unknown page is a 404 inside the shell', function () {
    $r = $this->c->get('?o=nope');
    expect($r->status)->toBe(404)->and($r->body)->toContain('<h2>Not found</h2>', 'class="topnav"');
});
