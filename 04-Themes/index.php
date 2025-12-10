<?php declare(strict_types=1);
// Created: 20150101 - Updated: 20251209
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

class Ctx {
    public function __construct(
        public private(set) string $email = 'mc@netserva.org',
        public string $buf = '',
        public array $ary = [],
        public array $in = ['l' => '', 'm' => 'list', 'o' => 'Home', 't' => 'Simple', 'x' => ''],
        public array $out = [
            'doc' => 'SPE::04', 'head' => 'Themes PHP Example',
            'main' => 'Error: missing plugin!',
            'foot' => '© 2015-2025 Mark Constable (MIT License)'
        ],
        public array $nav1 = [['🏠 Home', 'Home'], ['📖 About', 'About'], ['✉️ Contact', 'Contact']],
        public array $nav2 = [['🎨 Simple', 'Simple'], ['🎨 TopNav', 'TopNav'], ['🎨 SideBar', 'SideBar']]
    ) {}
}

readonly class Init {
    public function __construct(private Ctx $ctx) {
        foreach ($this->ctx->in as $k => $v)
            $this->ctx->in[$k] = ($_REQUEST[$k] ?? $v) |> trim(...) |> htmlspecialchars(...);

        ['o' => $o, 'm' => $m, 't' => $t] = $this->ctx->in;

        $model = "{$o}Model";
        $this->ctx->ary = class_exists($model) ? (new $model($this->ctx))->$m() : [];

        $view = "{$o}View";
        $theme = $t;
        $render = fn(?object $obj, string $method) =>
            ($obj && method_exists($obj, $method)) ? $obj->$method() : null;

        $v1 = class_exists($view) ? new $view($this->ctx) : null;
        $v2 = class_exists($theme) ? new $theme($this->ctx) : null;

        $this->ctx->out['main'] = $render($v1, $m) ?? $render($v2, $m) ?? $this->ctx->out['main'];
        $this->ctx->buf = $render($v1, 'html') ?? $render($v2, 'html') ?? '';
    }

    public function __toString(): string {
        return match ($this->ctx->in['x']) {
            'json' => (header('Content-Type: application/json') ?: '') . json_encode($this->ctx->out),
            default => $this->ctx->buf
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
        return ['head' => '🏠 Home', 'main' => 'Welcome to SPE::04 Themes with multiple layout options.'];
    }
}
final class AboutModel extends Plugin {
    #[\Override] public function list(): array {
        return ['head' => '📖 About', 'main' => "PHP 8.5 theming system. Contact: {$this->ctx->email}"];
    }
}
final class ContactModel extends Plugin {
    #[\Override] public function list(): array {
        return ['head' => '✉️ Contact', 'main' => 'form'];
    }
}

class HomeView {
    public function __construct(private Ctx $ctx) {}
    public function list(): string {
        return "<div class=\"card\"><h2>{$this->ctx->ary['head']}</h2><p>{$this->ctx->ary['main']}</p></div>";
    }
}
class AboutView extends HomeView {}
class ContactView {
    public function __construct(private Ctx $ctx) {}
    public function list(): string {
        return <<<HTML
        <div class="card"><h2>{$this->ctx->ary['head']}</h2>
        <form onsubmit="return handleContact(this)">
            <div class="form-group"><label>Subject</label><input type="text" id="subject" required></div>
            <div class="form-group"><label>Message</label><textarea id="message" rows="4" required></textarea></div>
            <div class="text-right"><button class="btn">Send</button></div>
        </form></div>
        <script>function handleContact(f){location.href='mailto:{$this->ctx->email}?subject='+encodeURIComponent(f.subject.value)+'&body='+encodeURIComponent(f.message.value);showToast('Opening...','success');return false;}</script>
        HTML;
    }
}

class Theme {
    public function __construct(protected Ctx $ctx) {}
    protected function nav(array $items, string $param = 'o'): string {
        $t = $this->ctx->in['t'];
        $o = $this->ctx->in['o'];
        return $items |> (fn($a) => array_map(fn($n) => sprintf(
            '<a href="?o=%s&t=%s"%s>%s</a>',
            $param === 'o' ? $n[1] : $o,
            $param === 't' ? $n[1] : $t,
            $this->ctx->in[$param] === $n[1] ? ' class="active"' : '', $n[0]
        ), $a)) |> (fn($l) => implode(' ', $l));
    }
}

final class Simple extends Theme {
    public function html(): string {
        extract($this->ctx->out);
        $nav1 = $this->nav($this->ctx->nav1);
        $nav2 = $this->nav($this->ctx->nav2, 't');
        return <<<HTML
        <!DOCTYPE html><html lang="en"><head>
            <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
            <title>$doc [Simple]</title><link rel="stylesheet" href="/spe.css">
        </head><body><div class="container">
            <header><h1><a href="../">« $head</a></h1></header>
            <nav class="flex">$nav1 | $nav2<span style="margin-left:auto"><button class="theme-toggle" id="theme-icon">🌙</button></span></nav>
            <main>$main</main>
            <footer class="text-center mt-3"><small>$foot</small></footer>
        </div><script src="/spe.js"></script></body></html>
        HTML;
    }
}

final class TopNav extends Theme {
    public function html(): string {
        extract($this->ctx->out);
        $nav1 = $this->nav($this->ctx->nav1);
        $nav2 = $this->nav($this->ctx->nav2, 't');
        return <<<HTML
        <!DOCTYPE html><html lang="en"><head>
            <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
            <title>$doc [TopNav]</title><link rel="stylesheet" href="/spe.css">
        </head><body>
            <nav class="topnav"><a class="brand" href="../">« $head</a>
                <div class="topnav-links">$nav1 | $nav2</div>
                <button class="theme-toggle" id="theme-icon">🌙</button>
                <button class="menu-toggle">☰</button>
            </nav>
            <main class="container mt-3">$main</main>
            <footer class="container text-center mt-3"><small>$foot</small></footer>
        <script src="/spe.js"></script></body></html>
        HTML;
    }
}

final class SideBar extends Theme {
    public function html(): string {
        extract($this->ctx->out);
        $t = $this->ctx->in['t'];
        $o = $this->ctx->in['o'];
        $nav1 = $this->ctx->nav1 |> (fn($a) => array_map(fn($n) => sprintf(
            '<a href="?o=%s&t=%s">%s</a>', $n[1], $t, $n[0]), $a)) |> (fn($l) => implode('', $l));
        $nav2 = $this->ctx->nav2 |> (fn($a) => array_map(fn($n) => sprintf(
            '<a href="?o=%s&t=%s">%s</a>', $o, $n[1], $n[0]), $a)) |> (fn($l) => implode('', $l));
        return <<<HTML
        <!DOCTYPE html><html lang="en"><head>
            <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
            <title>$doc [SideBar]</title><link rel="stylesheet" href="/spe.css">
        </head><body>
            <nav class="topnav"><button class="menu-toggle">☰</button><a class="brand" href="../">« $head</a>
                <button class="theme-toggle" id="theme-icon">🌙</button></nav>
            <div class="sidebar-layout">
                <aside class="sidebar">
                    <div class="sidebar-group"><div class="sidebar-group-title">Pages</div><nav>$nav1</nav></div>
                    <div class="sidebar-group"><div class="sidebar-group-title">Themes</div><nav>$nav2</nav></div>
                </aside>
                <div class="sidebar-main"><main>$main</main>
                    <footer class="text-center mt-3"><small>$foot</small></footer>
                </div>
            </div>
        <script src="/spe.js"></script></body></html>
        HTML;
    }
}

echo new Init(new Ctx);
