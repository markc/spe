<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

beforeEach(fn() => $this->c = chapter('06-Session'));

test('pages render and the session cookie is set', function () {
    $r = $this->c->get();
    expect($r->status)->toBe(200)
        ->and($r->body)->toContain('<title>SPE::06 Home</title>', '<h2>Home</h2>')
        ->and($r->header('Set-Cookie'))->toContain('PHPSESSID')
        ->and($r->header('Set-Cookie'))->toContain('HttpOnly', 'SameSite=Lax');
});

test('the contact form carries a CSRF token', function () {
    expect($this->c->get('?o=Contact')->body)->toMatch('/name="csrf" value="[0-9a-f]{32}"/');
});

test('a valid POST is accepted, redirects, and flashes success', function () {
    $token = $this->c->get('?o=Contact')->csrf();
    $r = $this->c->post('?o=Contact&m=create', ['csrf' => $token, 'subject' => 'Hello', 'message' => 'Hi']);
    expect($r->status)->toBe(302)->and($r->header('Location'))->toBe('?o=Contact');

    $after = $this->c->get('?o=Contact');
    expect($after->body)->toContain('Base.toast', 'Hello')->and($after->body)->toContain('success');
});

test('a flash is shown once and then cleared', function () {
    $token = $this->c->get('?o=Contact')->csrf();
    $this->c->post('?o=Contact&m=create', ['csrf' => $token, 'subject' => 'Once', 'message' => '']);
    expect($this->c->get('?o=Contact')->body)->toContain('Base.toast');
    expect($this->c->get('?o=Contact')->body)->not->toContain('Base.toast');
});

test('a POST without a token is rejected and writes nothing', function () {
    $r = $this->c->post('?o=Contact&m=create', ['subject' => 'Evil', 'message' => 'x']);
    expect($r->status)->toBe(302);
    $after = $this->c->get('?o=Contact');
    expect($after->body)->toContain('expired')->not->toContain('Evil');
});

test('a POST with a wrong token is rejected', function () {
    $this->c->get('?o=Contact');
    $r = $this->c->post('?o=Contact&m=create', ['csrf' => 'deadbeef', 'subject' => 'Nope', 'message' => 'x']);
    expect($r->status)->toBe(302)->and($this->c->get('?o=Contact')->body)->not->toContain('Nope');
});

test('?x=json still works', function () {
    expect($this->c->get('?o=About&x=json')->json())->toHaveKeys(['doc', 'page', 'main', 'title', 'body']);
});
