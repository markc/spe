<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

beforeEach(fn() => $this->c = chapter('05-Autoload'));

test('the split into files changes nothing visible', function (string $o) {
    $r = $this->c->get("?o=$o");
    expect($r->status)->toBe(200)->and($r->body)->toContain("<title>SPE::05 $o</title>", "<h2>$o</h2>");
})->with(['Home', 'About', 'Contact']);

test('plugins are resolved by namespace', function () {
    expect($this->c->get('?o=Home&m=delete')->body)->toContain('SPE\Autoload\Plugins\Home\HomeModel::delete() is not implemented');
});

test('only classes under Plugins are reachable', function (string $o) {
    expect($this->c->get("?o=$o")->status)->toBe(404);
})->with(['Nope', 'Core', 'Ctx', 'View']);

test('?x=json carries the model data', function () {
    $j = $this->c->get('?o=Contact&x=json')->json();
    expect($j)->toHaveKeys(['doc', 'page', 'main', 'title', 'body', 'email']);
});
