<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

beforeEach(function () {
    $this->c = chapter('08-Auth');
    $this->c->forget();
});

function login(object $c, string $email, string $password, bool $remember = false): object
{
    $token = $c->get('?o=Auth&m=create')->csrf();
    $form = ['csrf' => $token, 'email' => $email, 'password' => $password];
    if ($remember) {
        $form['remember'] = '1';
    }
    return $c->post('?o=Auth&m=create', $form);
}

test('signed out, the login link shows and Users is hidden', function () {
    $r = $this->c->get();
    expect($r->body)->toContain('?o=Auth&m=create')->not->toContain('data-lucide="users"');
});

test('an anonymous visitor is redirected away from Users', function () {
    $r = $this->c->get('?o=Users');
    expect($r->status)->toBe(302)->and($r->header('Location'))->toBe('?o=Auth&m=create');
});

test('posts are readable but the write controls are hidden when signed out', function () {
    $r = $this->c->get('?o=Posts');
    expect($r->status)->toBe(200)->and($r->body)->toContain('Welcome to chapter 08')->not->toContain('New post');
});

test('valid credentials sign you in and rotate the session id', function () {
    $before = $this->c->get()->header('Set-Cookie');
    $r = login($this->c, 'admin@example.com', 'admin');
    expect($r->status)->toBe(302)->and($r->header('Location'))->toBe('?o=Home')
        ->and($r->header('Set-Cookie'))->toContain('PHPSESSID');
    expect($this->c->get()->body)->toContain('Admin', '?o=Users', 'Logout');
});

test('wrong credentials are rejected with a neutral message', function () {
    $r = login($this->c, 'admin@example.com', 'wrong');
    expect($r->status)->toBe(302);
    expect($this->c->get('?o=Auth&m=create')->body)->toContain('did not match');
});

test('an admin can open Users and see the seeded accounts', function () {
    login($this->c, 'admin@example.com', 'admin');
    $r = $this->c->get('?o=Users');
    expect($r->status)->toBe(200)->and($r->body)->toContain('admin@example.com', 'user@example.com');
});

test('a normal user is still refused Users', function () {
    login($this->c, 'user@example.com', 'user');
    $r = $this->c->get('?o=Users');
    expect($r->status)->toBe(302)->and($r->header('Location'))->toBe('?o=Auth&m=create');
    expect($this->c->get()->body)->not->toContain('data-lucide="users"');
});

test('a signed-in admin can write posts', function () {
    login($this->c, 'admin@example.com', 'admin');
    expect($this->c->get('?o=Posts')->body)->toContain('New post');
    $token = $this->c->get('?o=Posts&m=create')->csrf();
    $r = $this->c->post('?o=Posts&m=create', ['csrf' => $token, 'title' => 'Admin post', 'body' => 'x']);
    expect($r->status)->toBe(302);
});

test('logout clears the session', function () {
    login($this->c, 'admin@example.com', 'admin');
    $token = $this->c->get()->csrf();
    $r = $this->c->post('?o=Auth&m=delete', ['csrf' => $token]);
    expect($r->status)->toBe(302);
    expect($this->c->get()->body)->toContain('?o=Auth&m=create')->not->toContain('Logout');
});

test('remember-me signs you back in after the session cookie is gone', function () {
    $r = login($this->c, 'admin@example.com', 'admin', remember: true);
    expect($r->cookies())->toContain('remember=');

    // Drop the session cookie, keeping only remember-me, as a returning visitor would.
    $this->c->dropSession();
    expect($this->c->get()->body)->toContain('Admin', 'Logout');
});

test('the admin cannot delete their own account', function () {
    login($this->c, 'admin@example.com', 'admin');
    $token = $this->c->get('?o=Users')->csrf();
    $this->c->post('?o=Users&m=delete&i=1', ['csrf' => $token]);
    expect($this->c->get('?o=Users')->body)->toContain('admin@example.com');
});

test('?x=json exposes the user list to an admin', function () {
    login($this->c, 'admin@example.com', 'admin');
    expect($this->c->get('?o=Users&x=json')->json())->toHaveKey('items');
});
