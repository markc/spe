<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// 04-Themes: App Shell with dual sidebars (LHS navigation, RHS settings)

if (!class_exists('Ctx')) {
readonly class Ctx {
    public array $in;
    public function __construct(
        public string $email = 'mc@netserva.org',
        array $in = ['o' => 'Home', 'm' => 'list', 'x' => ''],
        public array $out = ['doc' => 'SPE::04', 'page' => '← 04 Themes', 'head' => '', 'main' => '', 'foot' => ''],
        public array $nav = [
            ['home',     'Home',    'Home'],
            ['book-open','About',   'About'],
            ['mail',     'Contact', 'Contact'],
        ],
        public array $colors = [
            ['circle', 'Stone',  'default'],
            ['waves',  'Ocean',  'ocean'],
            ['trees',  'Forest', 'forest'],
            ['sunset', 'Sunset', 'sunset'],
        ]
    ) {
        $this->in = array_map(static fn($k, $v) => $_GET[$k] ?? $v
            |> trim(...) |> htmlspecialchars(...), array_keys($in), $in)
            |> (static fn($v) => array_combine(array_keys($in), $v));
    }
}

readonly class Init {
    private array $out;
    public function __construct(private Ctx $ctx) {
        [$o, $m] = [$ctx->in['o'], $ctx->in['m']];
        $model = "{$o}Model";
        $ary = class_exists($model) ? (new $model($ctx))->$m() : [];
        $view = "{$o}View";
        $main = class_exists($view) ? (new $view($ctx, $ary))->$m() : "<p>{$ary['main']}</p>";
        $this->out = [...$ctx->out, ...$ary, 'main' => $main];
    }
    public function __toString(): string {
        return match ($this->ctx->in['x']) {
            'json' => (header('Content-Type: application/json') ? '' : '') . json_encode($this->out),
            default => (new Theme($this->ctx, $this->out))->render()
        };
    }
}

abstract class Plugin {
    public function __construct(protected Ctx $ctx) {}
    public function create(): array { return ['head' => 'Create', 'main' => 'Not implemented']; }
    public function read(): array   { return ['head' => 'Read', 'main' => 'Not implemented']; }
    public function update(): array { return ['head' => 'Update', 'main' => 'Not implemented']; }
    public function delete(): array { return ['head' => 'Delete', 'main' => 'Not implemented']; }
    public function list(): array   { return ['head' => 'List', 'main' => 'Not implemented']; }
}

final class HomeModel extends Plugin {
    #[\Override] public function list(): array {
        return ['head' => 'Theme System', 'main' => 'A modern CSS architecture using <b>OKLCH color space</b> and <b>mobile-first</b> responsive design.'];
    }
}
final class AboutModel extends Plugin {
    #[\Override] public function list(): array {
        return ['head' => 'App Shell', 'main' => 'Dual sidebar interface with <b>LHS</b> and <b>RHS</b> navigation panels.'];
    }
}
final class ContactModel extends Plugin {
    #[\Override] public function list(): array {
        return ['head' => 'Contact', 'main' => 'Get in touch using the <b>email form</b> below.'];
    }
}

class View {
    public function __construct(protected Ctx $ctx, protected array $ary) {}
    public function list(): string {
        return "<div class=\"card\"><h2>{$this->ary['head']}</h2><p>{$this->ary['main']}</p></div>";
    }
}

final class HomeView extends View {
    #[\Override] public function list(): string {
        return <<<HTML
<div class="card">
    <h2>{$this->ary['head']}</h2>
    <p>{$this->ary['main']}</p>
    <h3>CSS Architecture</h3>
    <ul style="list-style:disc;padding-left:1.5rem;margin-top:0.5rem">
        <li><b>base.css</b> - Color-agnostic framework (layout, components, utilities)</li>
        <li><b>site.css</b> - All color definitions using OKLCH color space</li>
    </ul>
    <h3>OKLCH Color Space</h3>
    <ul style="list-style:disc;padding-left:1.5rem;margin-top:0.5rem">
        <li><b>Perceptually uniform</b> - Equal changes look equal visually</li>
        <li><b>L</b> (Lightness) - Consistent across all hues (0-100%)</li>
        <li><b>C</b> (Chroma) - Color intensity (0 = gray, 0.4 = vivid)</li>
        <li><b>H</b> (Hue) - Color angle (0-360°)</li>
    </ul>
    <h3>Color Schemes</h3>
    <ul style="list-style:disc;padding-left:1.5rem;margin-top:0.5rem">
        <li><b>Stone</b> (H=60) - Warm neutral, default theme</li>
        <li><b>Ocean</b> (H=220) - Cool cyan-blue tones</li>
        <li><b>Forest</b> (H=150) - Natural green palette</li>
        <li><b>Sunset</b> (H=45) - Warm orange-amber</li>
    </ul>
    <h3>Mobile-First Breakpoints</h3>
    <ul style="list-style:disc;padding-left:1.5rem;margin-top:0.5rem">
        <li><b>Mobile</b> (0-767px) - Base styles, full width</li>
        <li><b>Tablet</b> (768px+) - Cards gain borders/shadows</li>
        <li><b>Desktop</b> (1280px+) - Sidebar pinning enabled</li>
    </ul>
</div>
HTML;
    }
}

final class AboutView extends View {
    #[\Override] public function list(): string {
        return <<<HTML
<div class="card">
    <h2>{$this->ary['head']}</h2>
    <p>{$this->ary['main']}</p>
    <h3>Features</h3>
    <ul style="list-style:disc;padding-left:1.5rem;margin-top:0.5rem">
        <li><b>LHS Panel</b> - Primary navigation (pages, posts)</li>
        <li><b>RHS Panel</b> - Secondary navigation, suited to settings</li>
        <li><b>Flyover</b> - Sidebars overlay content by default</li>
        <li><b>Pin</b> - Lock sidebars open on desktop (1280px+)</li>
    </ul>
    <h3>Breakpoints</h3>
    <ul style="list-style:disc;padding-left:1.5rem;margin-top:0.5rem">
        <li><b>Mobile</b> (0-767px) - Full width content</li>
        <li><b>Tablet</b> (768-1279px) - 1rem side padding</li>
        <li><b>Desktop</b> (1280px+) - 2rem padding, pin enabled</li>
    </ul>
</div>
HTML;
    }
}

final class ContactView extends View {
    #[\Override] public function list(): string {
        return <<<HTML
<div class="card">
    <h2>{$this->ary['head']}</h2>
    <p>{$this->ary['main']}</p>
    <form class="mt-2" onsubmit="return handleContact(this)">
        <div class="form-group"><label for="subject">Subject</label><input type="text" id="subject" name="subject" required></div>
        <div class="form-group"><label for="message">Message</label><textarea id="message" name="message" rows="4" required></textarea></div>
        <div class="text-right"><button type="submit" class="btn">Send Message</button></div>
    </form>
</div>
<script>
function handleContact(form) {
    location.href = 'mailto:{$this->ctx->email}?subject=' + encodeURIComponent(form.subject.value) + '&body=' + encodeURIComponent(form.message.value);
    showToast('Opening email client...', 'success');
    return false;
}
</script>
HTML;
    }
}

final class Theme {
    public function __construct(private Ctx $ctx, private array $out) {}

    public function render(): string {
        $body = $this->topnav() . $this->sidebar('left') . $this->sidebar('right') . $this->main();
        return $this->html($body);
    }

    private function links(array $items, string $param, string $current): string {
        $o = $this->ctx->in['o'];
        return implode('', array_map(fn($p) => sprintf(
            '<a href="?o=%s"%s data-icon="%s"><i data-lucide="%s"></i> %s</a>',
            $param === 'o' ? $p[2] : $o, $current === $p[2] ? ' class="active"' : '', $p[0], $p[0], $p[1]
        ), $items));
    }

    private function colorLinks(): string {
        return implode('', array_map(fn($p) => sprintf(
            '<a href="#" data-scheme="%s" data-icon="%s"><i data-lucide="%s"></i> %s</a>',
            $p[2], $p[0], $p[0], $p[1]
        ), $this->ctx->colors));
    }

    private function topnav(): string {
        return <<<HTML
<nav class="topnav">
    <button class="menu-toggle" data-sidebar="left"><i data-lucide="menu"></i></button>
    <h1><a class="brand" href="/"><span>{$this->out['page']}</span></a></h1>
    <button class="menu-toggle" data-sidebar="right"><i data-lucide="menu"></i></button>
</nav>
HTML;
    }

    private function sidebar(string $side): string {
        $nav = $side === 'left'
            ? $this->links($this->ctx->nav, 'o', $this->ctx->in['o'])
            : $this->colorLinks()
              . '<div class="sidebar-divider"></div>'
              . '<a href="#" onclick="Base.toggleTheme();return false" data-icon="moon"><i data-lucide="moon"></i> Toggle Theme</a>';
        $title = $side === 'left' ? 'Navigation' : 'Settings';
        $icon = $side === 'left' ? 'compass' : 'sliders-horizontal';
        return <<<HTML
<aside class="sidebar sidebar-{$side}">
    <div class="sidebar-header">
        <span><i data-lucide="{$icon}"></i> {$title}</span>
        <button class="pin-toggle" data-sidebar="{$side}" title="Pin sidebar"><i data-lucide="pin"></i></button>
    </div>
    <nav>{$nav}</nav>
</aside>
HTML;
    }

    private function main(): string {
        return "<main>{$this->out['main']}</main>";
    }

    private function html(string $body): string {
        $doc = $this->out['doc'];
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$doc}</title>
    <link rel="stylesheet" href="/base.css">
    <link rel="stylesheet" href="/site.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>(function(){var s=JSON.parse(localStorage.getItem('base-state')||'{}'),t=s.theme,c=s.scheme,h=document.documentElement;h.className='preload '+(t||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light'))+(c&&c!=='default'?' scheme-'+c:'');})()</script>
</head>
<body>
{$body}
<div class="overlay"></div>
<script src="/base.js"></script>
</body>
</html>
HTML;
    }
}
}

echo new Init(new Ctx);
