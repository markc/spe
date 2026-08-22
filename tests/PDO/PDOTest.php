<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

beforeEach(fn() => $this->c = chapter('07-PDO'));

test('the posts list renders the seeded rows', function () {
    $r = $this->c->get('?o=Posts');
    expect($r->status)->toBe(200)
        ->and($r->body)->toContain('<title>SPE::07 Posts</title>', 'Welcome to chapter 07', 'class="data-table"');
});

test('a single post reads by id', function () {
    expect($this->c->get('?o=Posts&m=read&i=1')->body)->toContain('Welcome to chapter 07', '<code>welcome</code>');
});

test('an unknown id is reported, not fatal', function () {
    expect($this->c->get('?o=Posts&m=read&i=9999')->body)->toContain('There is no such post');
});

test('create then read round-trips through the database', function () {
    $token = $this->c->get('?o=Posts&m=create')->csrf();
    $r = $this->c->post('?o=Posts&m=create', ['csrf' => $token, 'title' => 'Pipe operator', 'body' => 'A body about |>']);
    expect($r->status)->toBe(302)->and($r->header('Location'))->toMatch('#\?o=Posts&m=read&i=\d+#');

    preg_match('/i=(\d+)/', (string) $r->header('Location'), $m);
    $read = $this->c->get("?o=Posts&m=read&i={$m[1]}");
    expect($read->body)->toContain('Pipe operator', 'A body about |&gt;');
});

test('update changes a row', function () {
    $token = $this->c->get('?o=Posts&m=update&i=2')->csrf();
    $this->c->post('?o=Posts&m=update&i=2', ['csrf' => $token, 'title' => 'The Db class, revised', 'body' => 'New body']);
    expect($this->c->get('?o=Posts&m=read&i=2')->body)->toContain('The Db class, revised', 'New body');
});

test('delete removes a row and is POST-only', function () {
    $token = $this->c->get('?o=Posts&m=create')->csrf();
    $loc = $this->c->post('?o=Posts&m=create', ['csrf' => $token, 'title' => 'Temporary', 'body' => 'x'])->header('Location');
    preg_match('/i=(\d+)/', (string) $loc, $m);
    $id = $m[1];

    // A GET does not delete.
    $this->c->get("?o=Posts&m=delete&i=$id");
    expect($this->c->get("?o=Posts&m=read&i=$id")->body)->toContain('Temporary');

    // A POST with the token does.
    $this->c->post("?o=Posts&m=delete&i=$id", ['csrf' => $token]);
    expect($this->c->get("?o=Posts&m=read&i=$id")->body)->toContain('There is no such post');
});

test('a write without a CSRF token is rejected', function () {
    $before = substr_count($this->c->get('?o=Posts')->body, '<tr>');
    $this->c->post('?o=Posts&m=create', ['title' => 'No token', 'body' => 'x']);
    $after = substr_count($this->c->get('?o=Posts')->body, '<tr>');
    expect($after)->toBe($before);
});

test('?x=json returns the item list', function () {
    $j = $this->c->get('?o=Posts&x=json')->json();
    expect($j)->toHaveKey('items')->and($j['items'])->toBeArray();
});
