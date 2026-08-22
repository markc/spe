<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

final readonly class Ctx
{
    public array $in;

    public function __construct(
        public array $out = ['doc' => 'SPE::04', 'page' => '04 Views', 'main' => ''],
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
        [$model, $view] = ["{$o}Model", "{$o}View"];
        if (is_subclass_of($model, Plugin::class)) {
            $data = new $model($ctx)->$m();
        } else {
            http_response_code(404);
            $data = ['title' => 'Not found', 'body' => 'There is no such plugin.'];
        }
        $view = is_a($view, View::class, true) ? $view : View::class;
        $this->out = [...$ctx->out, ...$data, 'main' => new $view($ctx, $data)->$m()];
    }

    public function __toString(): string
    {
        if ($this->ctx->in['x'] === 'json') {
            header('Content-Type: application/json');
            return json_encode($this->out, JSON_THROW_ON_ERROR);
        }
        return new Theme($this->ctx, $this->out)->render();
    }
}

abstract class Plugin
{
    public function __construct(protected Ctx $ctx) {}

    /** @return array{title: string, body: string} */
    public function create(): array { return $this->todo('create'); }
    public function read(): array { return $this->todo('read'); }
    public function update(): array { return $this->todo('update'); }
    public function delete(): array { return $this->todo('delete'); }
    public function list(): array { return $this->todo('list'); }

    private function todo(string $m): array
    {
        return ['title' => ucfirst($m), 'body' => static::class . "::$m() is not implemented."];
    }
}

class View
{
    public function __construct(protected Ctx $ctx, protected array $data) {}

    public function create(): string { return $this->card(); }
    public function read(): string { return $this->card(); }
    public function update(): string { return $this->card(); }
    public function delete(): string { return $this->card(); }
    public function list(): string { return $this->card(); }

    protected function card(): string
    {
        return <<<HTML
<div class="card"><h2>{$this->e($this->data['title'])}</h2><p>{$this->e($this->data['body'])}</p></div>
HTML;
    }

    protected function e(string|int|float|null $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}

final class Theme
{
    public function __construct(private readonly Ctx $ctx, private readonly array $out) {}

    public function render(): string
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
    <h1><a class="brand" href="../"><span>{$this->out['page']}</span></a></h1>
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

final class HomeModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['title' => 'Home', 'body' => 'The page looks exactly like chapters 02 and 03. Each plugin is now two classes: a Model that returns data and a View that turns it into HTML. Every value a View prints goes through e(), so this string can contain <b>tags</b> and they will appear as text.'];
    }
}

final class HomeView extends View
{
    #[\Override]
    public function list(): string
    {
        return <<<HTML
<div class="card">
    <h2>{$this->e($this->data['title'])}</h2>
    <p>{$this->e($this->data['body'])}</p>
    <p>Try <a href="?o=Home&m=create">?o=Home&amp;m=create</a>, <a href="?o=Nope">?o=Nope</a> and <a href="?o=Home&x=json">?o=Home&amp;x=json</a>.</p>
    <p class="mt-4">
        <button class="btn btn-success" onclick="Base.toast('Saved.', 'success')">Success toast</button>
        <button class="btn btn-danger" onclick="Base.toast('Something went wrong.', 'danger')">Danger toast</button>
    </p>
</div>
HTML;
    }
}

final class AboutModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['title' => 'About', 'body' => 'About has a Model but no View, so the base View renders it as a card. Theme is the only class that knows what a whole HTML document looks like.'];
    }
}

final class ContactModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['title' => 'Contact', 'body' => 'This form opens your email client. A form the server actually receives arrives in chapter 06.', 'email' => $this->ctx->email];
    }
}

final class ContactView extends View
{
    #[\Override]
    public function list(): string
    {
        return <<<HTML
<div class="card">
    <h2>{$this->e($this->data['title'])}</h2>
    <p>{$this->e($this->data['body'])}</p>
    <p><a class="btn" href="mailto:{$this->e($this->data['email'])}">Email us</a></p>
</div>
HTML;
    }
}

echo new Init(new Ctx);
