<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

if (!class_exists('Ctx')) {
readonly class Ctx {
    public array $in;
    public function __construct(
        public string $email = 'mc@netserva.org',
        array $in = ['o' => 'Home', 'm' => 'list', 't' => 'Simple', 'x' => ''],
        public array $out = ['doc' => 'SPE::04', 'head' => '', 'main' => '', 'foot' => ''],
        public array $nav = [['🏠 Home', 'Home'], ['📖 About', 'About'], ['✉️ Contact', 'Contact']],
        public array $themes = [['🎨 Simple', 'Simple'], ['🎨 TopNav', 'TopNav'], ['🎨 SideBar', 'SideBar']]
    ) {
        $this->in = array_map(static fn($k, $v) => (isset($_GET[$k]) && is_string($_GET[$k]) ? $_GET[$k] : $v)
            |> trim(...)
            |> htmlspecialchars(...), array_keys($in), $in)
            |> (static fn($v) => array_combine(array_keys($in), $v));
    }
}

readonly class Init {
    private array $out;

    public function __construct(private Ctx $ctx) {
        [$o, $m, $t] = [$ctx->in['o'], $ctx->in['m'], $ctx->in['t']];
        $model = "{$o}Model";
        $ary = class_exists($model) ? (new $model($ctx))->$m() : [];
        $view = "{$o}View";
        $main = class_exists($view) ? (new $view($ctx, $ary))->$m() : "<p>{$ary['main']}</p>";
        $this->out = [...$ctx->out, ...$ary, 'main' => $main];
    }

    public function __toString(): string {
        return match ($this->ctx->in['x']) {
            'json' => (header('Content-Type: application/json') ? '' : '') . json_encode($this->out),
            default => (new Theme($this->ctx, $this->out))->{$this->ctx->in['t']}()
        };
    }
}

abstract class Plugin {
    public function __construct(protected Ctx $ctx) {}
    public function create(): array { return ['head' => 'Create', 'main' => 'Not implemented']; }
    public function read(): array { return ['head' => 'Read', 'main' => 'Not implemented']; }
    public function update(): array { return ['head' => 'Update', 'main' => 'Not implemented']; }
    public function delete(): array { return ['head' => 'Delete', 'main' => 'Not implemented']; }
    public function list(): array { return ['head' => 'List', 'main' => 'Not implemented']; }
}

final class HomeModel extends Plugin {
    #[\Override] public function list(): array {
        return ['head' => 'Theme Layouts', 'main' => 'This chapter introduces <b>Model/View separation</b> and three switchable theme layouts.'];
    }
}

final class AboutModel extends Plugin {
    #[\Override] public function list(): array {
        return ['head' => 'About Page', 'main' => 'This chapter adds <b>Model/View separation</b> and switchable theme layouts.'];
    }
}

final class ContactModel extends Plugin {
    #[\Override] public function list(): array {
        return ['head' => 'Contact Page', 'main' => 'Get in touch using the <b>email form</b> below.'];
    }
}

class View {
    public function __construct(protected Ctx $ctx, protected array $ary) {}

    public function list(): string {
        return <<<HTML
        <div class="card">
            <h2>{$this->ary['head']}</h2>
            <p>{$this->ary['main']}</p>
        </div>
        HTML;
    }
}

final class HomeView extends View {
    #[\Override] public function list(): string {
        return <<<HTML
        <div class="card">
            <h2>{$this->ary['head']}</h2>
            <p>{$this->ary['main']}</p>

            <h3 class="mt-4">🎨 Simple</h3>
            <p>A clean, centered layout with the navigation in a card below the header. Best for simple sites with minimal navigation. The header, nav card, content, and footer stack vertically in a single column container.</p>

            <h3 class="mt-4">🎨 TopNav</h3>
            <p>A fixed navigation bar at the top of the viewport with centered navigation links and a theme dropdown. The content area has extra top margin to account for the fixed navbar. Ideal for sites that need persistent navigation while scrolling.</p>

            <h3 class="mt-4">🎨 SideBar</h3>
            <p>A two-column layout with a collapsible sidebar. Click the « toggle at the bottom to collapse to icons-only mode with hover tooltips (state persists via localStorage). Groups can be collapsed by clicking their titles. On mobile, the ☰ button toggles sidebar visibility. Best for admin dashboards and applications with many navigation items.</p>

            <h3 class="mt-4">What's New in This Chapter?</h3>
            <ul class="mt-2" style="list-style:disc;padding-left:1.5rem">
                <li><b>Model/View Separation</b> — Models return data arrays, Views render HTML from that data</li>
                <li><b>Theme Class</b> — A single Theme class with methods for each layout (Simple, TopNav, SideBar)</li>
                <li><b>URL Parameter</b> — Use <code>?t=</code> to switch themes: <a href="?t=Simple">Simple</a>, <a href="?t=TopNav">TopNav</a>, <a href="?t=SideBar">SideBar</a></li>
                <li><b>Dropdown Component</b> — Reusable dropdown menu for theme selection</li>
            </ul>
        </div>
        <div class="flex justify-center mt-4">
            <button class="btn-hover btn-success" onclick="showToast('Success!', 'success')">Success</button>
            <button class="btn-hover btn-danger" onclick="showToast('Error!', 'danger')">Danger</button>
        </div>
        HTML;
    }
}

final class AboutView extends View {}

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

    private function nav(): string {
        ['o' => $o, 't' => $t] = $this->ctx->in;
        return $this->ctx->nav
            |> (static fn($n) => array_map(static fn($p) => sprintf(
                '<a href="?o=%s&t=%s"%s>%s</a>',
                $p[1], $t, $o === $p[1] ? ' class="active"' : '', $p[0]
            ), $n))
            |> (static fn($a) => implode(' ', $a));
    }

    private function dropdown(): string {
        ['o' => $o, 't' => $t] = $this->ctx->in;
        $links = $this->ctx->themes
            |> (static fn($n) => array_map(static fn($p) => sprintf(
                '<a href="?o=%s&t=%s"%s>%s</a>',
                $o, $p[1], $t === $p[1] ? ' class="active"' : '', $p[0]
            ), $n))
            |> (static fn($a) => implode('', $a));
        return "<div class=\"dropdown\"><span class=\"dropdown-toggle\">🎨 Themes</span><div class=\"dropdown-menu\">{$links}</div></div>";
    }

    private function html(string $theme, string $body): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$this->out['doc']} [{$theme}]</title>
    <link rel="stylesheet" href="/base.css">
    <link rel="stylesheet" href="/site.css">
    <script>(function(){const t=localStorage.getItem("base-theme");document.documentElement.className=t||(matchMedia("(prefers-color-scheme:dark)").matches?"dark":"light")})();</script>
</head>
<body>
{$body}
<script src="/base.js"></script>
</body>
</html>
HTML;
    }

    public function Simple(): string {
        $nav = $this->nav();
        $dd = $this->dropdown();
        $body = <<<HTML
<div class="container">
    <header><h1><a class="brand" href="/">🐘 Themes PHP Example</a></h1></header>
    <nav class="card flex">
        {$nav} {$dd}
        <span class="ml-auto"><button class="theme-toggle" id="theme-icon">🌙</button></span>
    </nav>
    <main class="mt-4 mb-4">{$this->out['main']}</main>
    <footer class="text-center"><small>© 2015-2026 Mark Constable (MIT License)</small></footer>
</div>
HTML;
        return $this->html('Simple', $body);
    }

    public function TopNav(): string {
        $nav = $this->nav();
        $dd = $this->dropdown();
        $body = <<<HTML
<nav class="topnav">
    <h1><a class="brand" href="/">🐘 Themes PHP Example</a></h1>
    <div class="topnav-links">{$nav} {$dd}</div>
    <button class="theme-toggle" id="theme-icon">🌙</button>
    <button class="menu-toggle">☰</button>
</nav>
<div class="container">
    <main class="mt-4 mb-4">{$this->out['main']}</main>
    <footer class="text-center"><small>© 2015-2026 Mark Constable (MIT License)</small></footer>
</div>
HTML;
        return $this->html('TopNav', $body);
    }

    public function SideBar(): string {
        ['o' => $o, 't' => $t] = $this->ctx->in;
        $n1 = $this->ctx->nav
            |> (static fn($n) => array_map(static function($p) use ($o, $t) {
                [$icon, $label] = explode(' ', $p[0], 2);
                return sprintf(
                    '<a href="?o=%s&t=%s"%s data-icon="%s" data-label="%s">%s</a>',
                    $p[1], $t, $o === $p[1] ? ' class="active"' : '', $icon, $label, $p[0]
                );
            }, $n))
            |> (static fn($a) => implode('', $a));
        $n2 = $this->ctx->themes
            |> (static fn($n) => array_map(static function($p) use ($o, $t) {
                [$icon, $label] = explode(' ', $p[0], 2);
                return sprintf(
                    '<a href="?o=%s&t=%s"%s data-icon="%s" data-label="%s">%s</a>',
                    $o, $p[1], $t === $p[1] ? ' class="active"' : '', $icon, $label, $p[0]
                );
            }, $n))
            |> (static fn($a) => implode('', $a));
        $body = <<<HTML
<nav class="topnav">
    <button class="menu-toggle">☰</button>
    <h1><a class="brand" href="/">🐘 Themes PHP Example</a></h1>
    <button class="theme-toggle" id="theme-icon">🌙</button>
</nav>
<div class="sidebar-layout">
    <aside class="sidebar">
        <div class="sidebar-group">
            <div class="sidebar-group-title" data-icon="📄">Pages</div>
            <nav>{$n1}</nav>
        </div>
        <div class="sidebar-group">
            <div class="sidebar-group-title" data-icon="🎨">Themes</div>
            <nav>{$n2}</nav>
        </div>
        <button class="sidebar-toggle" aria-label="Toggle sidebar"></button>
    </aside>
    <div class="sidebar-main">
        <main class="mt-4 mb-4">{$this->out['main']}</main>
        <footer class="text-center"><small>© 2015-2026 Mark Constable (MIT License)</small></footer>
    </div>
</div>
HTML;
        return $this->html('SideBar', $body);
    }
}
}

echo new Init(new Ctx);
