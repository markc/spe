<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

beforeEach(function () {
    $this->c = chapter('09-Blog');
    $this->c->forget();
});

function admin(object $c): void
{
    $token = $c->get('?o=Auth&m=create')->csrf();
    $c->post('?o=Auth&m=create', ['csrf' => $token, 'email' => 'admin@example.com', 'password' => 'admin']);
}

test('the blog list renders seeded posts and paginates', function () {
    $r = $this->c->get('?o=Blog');
    expect($r->status)->toBe(200)
        ->and($r->body)->toContain('<title>SPE::09 Blog</title>', 'Sixth post', 'Page 1 of 2');
});

test('a post renders its Markdown as HTML, not as text', function () {
    $r = $this->c->get('?o=Blog&m=read&i=1');
    expect($r->body)->toContain('<strong>Markdown</strong>', '<em>emphasis</em>', '<code>inline code</code>', '<li>one</li>')
        ->and($r->body)->toContain('href="https://www.php.net"');
});

test('markdown cannot inject script or javascript links', function () {
    admin($this->c);
    $token = $this->c->get('?o=Blog&m=create')->csrf();
    $this->c->post('?o=Blog&m=create', [
        'csrf' => $token,
        'title' => 'XSS attempt',
        'body' => "<script>alert(1)</script> and [click](javascript:alert(2))",
        'tags' => '',
    ]);
    $r = $this->c->get('?o=Blog&m=read&i=1'); // find the new one via list instead
    $list = $this->c->get('?o=Blog')->body;
    expect($list)->not->toContain('<script>alert(1)</script>');
    // The javascript: link is dropped, leaving the link text only.
    preg_match('/i=(\d+)"[^>]*>XSS attempt/', $list, $m);
    $read = $this->c->get('?o=Blog&m=read&i=' . ($m[1] ?? 0))->body;
    expect($read)->toContain('&lt;script&gt;')->not->toContain('href="javascript:');
});

test('docs are the same engine with type=doc', function () {
    $r = $this->c->get('?o=Docs');
    expect($r->status)->toBe(200)->and($r->body)->toContain('<title>SPE::09 Docs</title>', 'Reading the docs');
    // A post id is not reachable through the Docs plugin.
    expect($this->c->get('?o=Docs&m=read&i=1')->body)->toContain('There is no such entry');
});

test('tag filtering narrows the list', function () {
    $r = $this->c->get('?o=Blog&tag=architecture');
    expect($r->body)->toContain('One table, two types')->not->toContain('Hello, Markdown');
});

test('writing is admin-only', function () {
    expect($this->c->get('?o=Blog')->body)->not->toContain('data-lucide="plus"'); // no New button when anon
    $r = $this->c->get('?o=Blog&m=create');
    expect($r->status)->toBe(302)->and($r->header('Location'))->toBe('?o=Auth&m=create');
});

test('an admin can create a post that then appears', function () {
    admin($this->c);
    $token = $this->c->get('?o=Blog&m=create')->csrf();
    $r = $this->c->post('?o=Blog&m=create', ['csrf' => $token, 'title' => 'Brand new', 'body' => 'Hello **world**', 'tags' => 'PHP 8.5, Fresh']);
    expect($r->status)->toBe(302);
    expect($this->c->get('?o=Blog')->body)->toContain('Brand new');
});

test('the Tags admin page lists tags with counts', function () {
    admin($this->c);
    expect($this->c->get('?o=Tags')->body)->toContain('Markdown', 'Architecture');
});

test('read exposes prev/next navigation', function () {
    $r = $this->c->get('?o=Blog&m=read&i=3');
    expect($r->body)->toContain('Older', 'Newer', 'class="pagination"');
});

test('?x=json returns items for the blog list', function () {
    expect($this->c->get('?o=Blog&x=json')->json())->toHaveKeys(['items', 'page', 'pages']);
});
