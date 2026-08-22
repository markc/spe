<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

beforeEach(fn() => $this->c = chapter('04-Views'));

test('Home renders through HomeModel and HomeView', function () {
    $r = $this->c->get();
    expect($r->status)->toBe(200)->and($r->body)->toContain('<title>SPE::04 Home</title>', '<h2>Home</h2>', 'Success toast');
});

test('model data is escaped by the view', function () {
    expect($this->c->get()->body)->toContain('&lt;b&gt;tags&lt;/b&gt;')->not->toContain('<b>tags</b>');
});

test('a model without a view renders through the base View card', function () {
    expect($this->c->get('?o=About')->body)->toContain('<div class="card"><h2>About</h2><p>About has a Model but no View');
});

test('unimplemented methods name the model class', function () {
    expect($this->c->get('?o=Contact&m=update')->body)->toContain('ContactModel::update() is not implemented');
});

test('an unknown plugin is a 404 rendered by the base View', function () {
    $r = $this->c->get('?o=Nope');
    expect($r->status)->toBe(404)->and($r->body)->toContain('<h2>Not found</h2>');
});

test('core classes cannot be reached as plugins', function (string $o) {
    expect($this->c->get("?o=$o")->status)->toBe(404);
})->with(['Ctx', 'Init', 'Plugin', 'View', 'Theme']);

test('?x=json carries the model data', function () {
    $j = $this->c->get('?o=About&x=json')->json();
    expect($j)->toHaveKeys(['doc', 'page', 'main', 'title', 'body'])->and($j['title'])->toBe('About');
});
