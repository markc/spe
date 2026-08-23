<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

final readonly class Ctx
{
    public array $in;

    public function __construct(
        public array $out = ['doc' => 'SPE::03', 'page' => '03 Plugins', 'main' => ''],
        public array $nav = [['home', 'Home', 'Home'], ['book-open', 'About', 'About'], ['mail', 'Contact', 'Contact']],
        public array $schemes = [['circle', 'Stone', 'default'], ['waves', 'Ocean', 'ocean'], ['trees', 'Forest', 'forest'], ['sunset', 'Sunset', 'sunset']],
        public string $email = 'mc@netserva.org',
    ) {
        $this->in = [
            'o' => self::get('o', 'Home', '/^[A-Z][A-Za-z]{0,31}$/'),
            'm' => self::get('m', 'list', '/^(create|read|update|delete|list)$/'),
            'x' => self::get('x', '', '/^json$/'),
        ];
    }

    private static function get(string $key, string $default, string $pattern): string
    {
        $v = $_GET[$key] ?? '';
        return is_string($v) && preg_match($pattern, $v) ? $v : $default;
    }
}

final readonly class Init
{
    private array $out;

    public function __construct(private Ctx $ctx)
    {
        [$o, $m] = [$ctx->in['o'], $ctx->in['m']];
        if (is_subclass_of($o, Plugin::class)) {
            $main = new $o($ctx)->$m();
        } else {
            http_response_code(404);
            $main = '<div class="card"><h2>Not found</h2><p>There is no such plugin.</p></div>';
        }
        $this->out = [...$ctx->out, 'main' => $main];
    }

    public function __toString(): string
    {
        if ($this->ctx->in['x'] === 'json') {
            header('Content-Type: application/json');
            return json_encode($this->out, JSON_THROW_ON_ERROR);
        }
        return $this->html();
    }

    private function html(): string
    {
        $nav = $this->ctx->nav
            |> (fn(array $items) => array_map(fn(array $n) => sprintf(
                '<a href="?o=%s"%s><i data-lucide="%s"></i> %s</a>', $n[2], $n[2] === $this->ctx->in['o'] ? ' class="active"' : '', $n[0], $n[1]
            ), $items))
            |> (static fn(array $links) => implode('', $links));

        $schemes = $this->ctx->schemes
            |> (static fn(array $items) => array_map(static fn(array $s) => sprintf(
                '<a href="#" data-scheme="%s"><i data-lucide="%s"></i> %s</a>', $s[2], $s[0], $s[1]
            ), $items))
            |> (static fn(array $links) => implode('', $links));

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$this->out['doc']} {$this->ctx->in['o']}</title>
    <link rel="stylesheet" href="../base.css">
    <link rel="stylesheet" href="../site.css">
    <script src="https://unpkg.com/lucide@1.33.0/dist/umd/lucide.min.js"></script>
    <script>(function(){var s=JSON.parse(localStorage.getItem('base-state')||'{}'),t=s.theme,c=s.scheme,h=document.documentElement;h.className='preload '+(t||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light'))+(c&&c!=='default'?' scheme-'+c:'');})()</script>
</head>
<body>
<nav class="topnav">
    <button class="menu-toggle" data-sidebar="left"><i data-lucide="menu"></i></button>
    <h1><a class="brand" href="../"><span>« {$this->out['page']}</span></a></h1>
    <button class="menu-toggle" data-sidebar="right"><i data-lucide="menu"></i></button>
</nav>
<aside class="sidebar sidebar-left">
    <div class="sidebar-header"><span><i data-lucide="compass"></i> Navigation</span><button class="pin-toggle" data-sidebar="left" title="Pin sidebar"><i data-lucide="pin"></i></button></div>
    <nav>{$nav}</nav>
</aside>
<aside class="sidebar sidebar-right">
    <div class="sidebar-header"><span><i data-lucide="sliders-horizontal"></i> Settings</span><button class="pin-toggle" data-sidebar="right" title="Pin sidebar"><i data-lucide="pin"></i></button></div>
    <nav>{$schemes}<div class="sidebar-divider"></div><a href="#" class="theme-toggle"><i data-lucide="moon"></i> Toggle theme</a></nav>
</aside>
<main>{$this->out['main']}</main>
<div class="overlay"></div>
<script src="../base.js"></script>
</body>
</html>
HTML;
    }
}

abstract class Plugin
{
    public function __construct(protected Ctx $ctx) {}

    public function create(): string { return $this->todo('create'); }
    public function read(): string { return $this->todo('read'); }
    public function update(): string { return $this->todo('update'); }
    public function delete(): string { return $this->todo('delete'); }
    public function list(): string { return $this->todo('list'); }

    private function todo(string $m): string
    {
        return sprintf('<div class="card"><h2>%s</h2><p>%s::%s() is not implemented.</p></div>', ucfirst($m), static::class, $m);
    }
}

final class Home extends Plugin
{
    #[\Override]
    public function list(): string
    {
        return <<<'HTML'
<div class="card">
    <h2>Home</h2>
    <p>The page looks exactly like chapter 02. Underneath, each page is now a <b>plugin</b>: a class with the five CRUDL methods, chosen by <code>?o=</code> and called by <code>?m=</code>. Try <a href="?o=Home&m=create">?o=Home&amp;m=create</a>, <a href="?o=Nope">?o=Nope</a> and <a href="?o=Home&x=json">?o=Home&amp;x=json</a>.</p>
    <p class="mt-4">
        <button class="btn btn-success" onclick="Base.toast('Saved.', 'success')">Success toast</button>
        <button class="btn btn-danger" onclick="Base.toast('Something went wrong.', 'danger')">Danger toast</button>
    </p>
</div>
HTML;
    }
}

final class About extends Plugin
{
    #[\Override]
    public function list(): string
    {
        return <<<'HTML'
<div class="card">
    <h2>About</h2>
    <p>Three classes do the work: <code>Ctx</code> reads and validates the request, <code>Init</code> picks the plugin and renders the page, and <code>Plugin</code> is what every page extends.</p>
</div>
HTML;
    }
}

final class Contact extends Plugin
{
    #[\Override]
    public function list(): string
    {
        return <<<HTML
<div class="card">
    <h2>Contact</h2>
    <p>A plain <code>mailto:</code> link — no JavaScript. A form the server actually receives arrives in chapter 06.</p>
    <p><a class="btn" href="mailto:{$this->ctx->email}">Email us</a></p>
</div>
HTML;
    }
}

echo new Init(new Ctx);
