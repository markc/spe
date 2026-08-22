<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

beforeEach(fn() => $this->c = chapter('03-Plugins'));

test('Home is the default plugin and list the default method', function () {
    $r = $this->c->get();
    expect($r->status)->toBe(200)
        ->and($r->body)->toContain('<title>SPE::03 Home</title>', '<h2>Home</h2>', '<a href="?o=Home" class="active">');
});

test('each plugin renders', function (string $o) {
    expect($this->c->get("?o=$o")->body)->toContain("<h2>$o</h2>");
})->with(['Home', 'About', 'Contact']);

test('CRUDL methods a plugin does not override say so', function (string $m) {
    $r = $this->c->get("?o=About&m=$m");
    expect($r->status)->toBe(200)->and($r->body)->toContain("About::$m() is not implemented");
})->with(['create', 'read', 'update', 'delete']);

test('an unknown plugin is a 404', function () {
    $r = $this->c->get('?o=Nope');
    expect($r->status)->toBe(404)->and($r->body)->toContain('There is no such plugin');
});

test('core classes cannot be reached as plugins', function (string $o) {
    expect($this->c->get("?o=$o")->status)->toBe(404);
})->with(['Ctx', 'Init', 'Plugin']);

test('invalid parameter values fall back to their defaults', function () {
    expect($this->c->get('?o=home&m=hack&x=xml')->body)->toContain('<h2>Home</h2>', '<a href="?o=Home" class="active">');
});

test('?x=json returns the output array', function () {
    $r = $this->c->get('?o=About&x=json');
    expect($r->header('Content-Type'))->toBe('application/json')
        ->and($r->json())->toHaveKeys(['doc', 'page', 'main'])
        ->and($r->json()['main'])->toContain('<h2>About</h2>');
});
