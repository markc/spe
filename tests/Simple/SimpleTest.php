<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

beforeEach(fn() => $this->c = chapter('01-Simple'));

test('home renders by default', function () {
    $r = $this->c->get();
    expect($r->status)->toBe(200)
        ->and($r->body)->toContain('<title>SPE::01</title>', '<h2>Home</h2>', '<a href="?o=home" class="active">Home</a>');
});

test('each page renders and marks itself active', function (string $page, string $label) {
    $r = $this->c->get("?o=$page");
    expect($r->status)->toBe(200)
        ->and($r->body)->toContain("<h2>$label</h2>", "<a href=\"?o=$page\" class=\"active\">$label</a>");
})->with([['home', 'Home'], ['about', 'About'], ['contact', 'Contact']]);

test('page names are trimmed and case-insensitive', function () {
    expect($this->c->get('?o=%20About%20')->body)->toContain('<h2>About</h2>');
});

test('unknown page is a 404 and the input is never echoed', function () {
    $r = $this->c->get('?o=%3Cscript%3Ealert(1)%3C/script%3E');
    expect($r->status)->toBe(404)
        ->and($r->body)->toContain('<h2>Not found</h2>')
        ->and($r->body)->not->toContain('<script>alert');
});

test('a non-string parameter falls back to home', function () {
    $r = $this->c->get('?o[]=x');
    expect($r->status)->toBe(200)->and($r->body)->toContain('<h2>Home</h2>');
});
