<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

final readonly class Ctx
{
    public array $in;

    public function __construct(
        public array $out = ['doc' => 'SPE::04', 'page' => '04 Views', 'main' => ''],
        public array $nav = [['home', 'Home', 'Home'], ['book-open', 'About', 'About'], ['mail', 'Contact', 'Contact']],
        public array $schemes = [['oklch(50% 0.12 220)', 'Ocean', 'default'], ['oklch(47% 0.2 25)', 'Crimson', 'crimson'], ['oklch(45% 0.05 60)', 'Stone', 'stone'], ['oklch(49% 0.12 150)', 'Forest', 'forest'], ['oklch(52% 0.16 45)', 'Sunset', 'sunset'], ['oklch(50% 0 0)', 'Mono', 'mono']],
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
                '<button class="scheme-item" data-scheme="%s"><span class="scheme-dot" style="background:%s"></span><span class="scheme-name">%s</span></button>', $s[2], $s[0], $s[1]
            ), $items))
            |> (static fn(array $buttons) => implode('', $buttons));

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$this->out['doc']} {$this->ctx->in['o']}</title>
    <link rel="stylesheet" href="../base.css">
    <link rel="stylesheet" href="../site.css">
    <link rel="stylesheet" href="../spe.css">
    <script src="https://unpkg.com/lucide@1.33.0/dist/umd/lucide.min.js"></script>
    <script>(function(){var s=JSON.parse(localStorage.getItem('base-state')||'{}'),t=s.theme,c=s.scheme,h=document.documentElement;h.className='preload '+(t||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light'))+(c&&c!=='default'?' scheme-'+c:'')+(s.width==='wide'?' wide':(s.width==='narrow'?' narrow':''));})()</script>
</head>
<body>
<button class="menu-toggle" data-sidebar="left"><i data-lucide="menu"></i></button>
<button class="menu-toggle" data-sidebar="right"><i data-lucide="menu"></i></button>
<nav class="topnav">
    <h1><a class="brand" href="../"><span>« {$this->out['page']}</span></a></h1>
</nav>
<aside class="sidebar sidebar-left">
    <div class="carousel-header">
        <div class="carousel-nav">
            <button class="carousel-chevron" data-sidebar="left" data-dir="prev"><i data-lucide="chevron-left"></i></button>
            <div class="carousel-dots">
                <button class="carousel-dot active" data-sidebar="left" data-panel="0"></button>
                <button class="carousel-dot" data-sidebar="left" data-panel="1"></button>
            </div>
            <button class="carousel-chevron" data-sidebar="left" data-dir="next"><i data-lucide="chevron-right"></i></button>
        </div>
        <button class="pin-toggle" data-sidebar="left" title="Pin sidebar"><i data-lucide="pin"></i></button>
    </div>
    <div class="panel-viewport">
        <div class="panel-track">
            <div class="panel">
                <div class="panel-title">Navigation</div>
                <div class="panel-content"><nav>{$nav}</nav></div>
            </div>
            <div class="panel">
                <div class="panel-title">About</div>
                <div class="panel-content"><nav>
                    <a href="../"><i data-lucide="layout-grid"></i> All chapters</a>
                    <a href="../docs/"><i data-lucide="book-open"></i> Documentation</a>
                    <div class="sidebar-divider"></div>
                    <a href="https://github.com/markc/spe"><i data-lucide="code"></i> SPE on GitHub</a>
                    <a href="https://www.youtube.com/playlist?list=PLM0Did14jsitwKl7RYaVrUWnG1GkRBO4B"><i data-lucide="video"></i> Video tutorials</a>
                    <a href="https://dcs.spa"><i data-lucide="panels-left-right"></i> DCS — this interface</a>
                </nav></div>
            </div>
        </div>
    </div>
</aside>
<aside class="sidebar sidebar-right">
    <div class="carousel-header">
        <button class="pin-toggle" data-sidebar="right" title="Pin sidebar"><i data-lucide="pin"></i></button>
        <div class="carousel-nav">
            <button class="carousel-chevron" data-sidebar="right" data-dir="prev"><i data-lucide="chevron-left"></i></button>
            <div class="carousel-dots">
                <button class="carousel-dot active" data-sidebar="right" data-panel="0"></button>
                <button class="carousel-dot" data-sidebar="right" data-panel="1"></button>
            </div>
            <button class="carousel-chevron" data-sidebar="right" data-dir="next"><i data-lucide="chevron-right"></i></button>
        </div>
    </div>
    <div class="panel-viewport">
        <div class="panel-track">
            <div class="panel">
                <div class="panel-title">Appearance</div>
                <div class="panel-content">
                    <div class="appearance-section">
                        <div class="toggle-group">
                            <button class="toggle-btn" data-theme="light">Light</button>
                            <button class="toggle-btn" data-theme="dark">Dark</button>
                        </div>
                        <div class="toggle-group">
                            <button class="toggle-btn" data-carousel="slide">Slide</button>
                            <button class="toggle-btn" data-carousel="fade">Fade</button>
                        </div>
                        <div class="toggle-group">
                            <button class="toggle-btn" data-width="narrow">Narrow</button>
                            <button class="toggle-btn" data-width="normal">Normal</button>
                            <button class="toggle-btn" data-width="wide">Wide</button>
                        </div>
                        <div class="sidebar-width-controls">
                            <div class="sidebar-width-control">
                                <label for="sidebar-width-left-input">Left %</label>
                                <input id="sidebar-width-left-input" type="number" class="sidebar-width-spinner" data-side="left" min="10" max="100" value="15" step="5">
                            </div>
                            <div class="sidebar-width-control">
                                <label for="sidebar-width-right-input">Right %</label>
                                <input id="sidebar-width-right-input" type="number" class="sidebar-width-spinner" data-side="right" min="10" max="100" value="15" step="5">
                            </div>
                        </div>
                        <div class="scheme-list">{$schemes}</div>
                    </div>
                </div>
            </div>
            <div class="panel">
                <div class="panel-title">Settings</div>
                <div class="panel-content"><nav>
                    <a href="#" class="theme-toggle"><i id="theme-icon" data-lucide="moon"></i> Toggle theme</a>
                </nav></div>
            </div>
        </div>
    </div>
</aside>
<main>{$this->out['main']}</main>
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
