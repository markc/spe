<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

readonly class Ctx {
    public array $in;
    public function __construct(
        public string $email = 'mc@netserva.org',
        public string $buf = '',
        public array $ary = [],
        array $in = ['l' => '', 'm' => 'list', 'o' => 'Home', 't' => 'Simple', 'x' => ''],
        public array $out = ['doc' => 'SPE::04', 'head' => 'Themes PHP Example', 'main' => 'Error: missing plugin!', 'foot' => '© 2015-2025 Mark Constable (MIT License)'],
        public array $nav1 = [['🏠 Home', 'Home'], ['📖 About', 'About'], ['✉️ Contact', 'Contact']],
        public array $nav2 = [['🎨 Simple', 'Simple'], ['🎨 TopNav', 'TopNav'], ['🎨 SideBar', 'SideBar']]
    ) {
        $this->in = array_combine(array_keys($in), array_map(fn($k, $v) => ($_REQUEST[$k] ?? $v) |> trim(...) |> htmlspecialchars(...), array_keys($in), $in));
    }
}

readonly class Init {
    private array $out;
    public function __construct(private Ctx $ctx) {
        ['o' => $o, 'm' => $m, 't' => $t] = $ctx->in;
        $model = "{$o}Model";
        $ary = class_exists($model) ? (new $model($ctx))->$m() : [];

        $render = fn(?object $obj, string $method) => ($obj && method_exists($obj, $method)) ? $obj->$method() : null;
        $v1 = class_exists($view = "{$o}View") ? new $view($ctx, $ary) : null;
        $main = $render($v1, $m) ?? $ary['main'] ?? '';
        $v2 = class_exists($t) ? new $t($ctx, [...$ary, 'main' => $main]) : null;

        $this->out = [...$ctx->out, 'main' => $main, 'buf' => $render($v1, 'html') ?? $render($v2, 'html') ?? ''];
    }

    public function __toString(): string {
        return match ($this->ctx->in['x']) {
            'json' => (header('Content-Type: application/json') ?: '') . json_encode($this->out),
            default => $this->out['buf']
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
    #[\Override] public function list(): array { return ['head' => '🏠 Home', 'main' => 'Welcome to SPE::04 Themes with multiple layout options.']; }
}
final class AboutModel extends Plugin {
    #[\Override] public function list(): array { return ['head' => '📖 About', 'main' => "PHP 8.5 theming system. Contact: {$this->ctx->email}"]; }
}
final class ContactModel extends Plugin {
    #[\Override] public function list(): array { return ['head' => '✉️ Contact', 'main' => 'form']; }
}

class View {
    public function __construct(protected Ctx $ctx, protected array $ary) {}
    public function list(): string { return "<div class=\"card\"><h2>{$this->ary['head']}</h2><p>{$this->ary['main']}</p></div>"; }
}
class HomeView extends View {}
class AboutView extends View {}
class ContactView extends View {
    #[\Override] public function list(): string {
        return <<<HTML
        <div class="card"><h2>{$this->ary['head']}</h2>
        <form onsubmit="return(location.href='mailto:{$this->ctx->email}?subject='+encodeURIComponent(this.subject.value)+'&body='+encodeURIComponent(this.message.value),showToast('Opening...','success'),false)">
        <div class="form-group"><label>Subject</label><input type="text" id="subject" required></div>
        <div class="form-group"><label>Message</label><textarea id="message" rows="4" required></textarea></div>
        <div class="text-right"><button class="btn">Send</button></div></form></div>
        HTML;
    }
}

class Theme extends View {
    protected function nav(string $p = 'o'): string {
        [$t, $o, $items] = [$this->ctx->in['t'], $this->ctx->in['o'], $p === 'o' ? $this->ctx->nav1 : $this->ctx->nav2];
        return implode(' ', array_map(fn($n) => sprintf('<a href="?o=%s&t=%s"%s>%s</a>',
            $p === 'o' ? $n[1] : $o, $p === 't' ? $n[1] : $t, $this->ctx->in[$p] === $n[1] ? ' class="active"' : '', $n[0]), $items));
    }
    protected function head(string $title): string {
        return "<!DOCTYPE html><html lang=\"en\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>{$this->ctx->out['doc']} [$title]</title><link rel=\"stylesheet\" href=\"/spe.css\"></head>";
    }
}

final class Simple extends Theme {
    public function html(): string {
        ['head' => $h, 'foot' => $f] = $this->ctx->out;
        $m = $this->ary['main'];
        return $this->head('Simple') . <<<HTML
        <body><div class="container"><header><h1><a href="/">« $h</a></h1></header>
        <nav class="flex">{$this->nav()} | {$this->nav('t')}<span style="margin-left:auto"><button class="theme-toggle" id="theme-icon">🌙</button></span></nav>
        <main>$m</main><footer class="text-center mt-3"><small>$f</small></footer></div><script src="/spe.js"></script></body></html>
        HTML;
    }
}

final class TopNav extends Theme {
    public function html(): string {
        ['head' => $h, 'foot' => $f] = $this->ctx->out;
        $m = $this->ary['main'];
        return $this->head('TopNav') . <<<HTML
        <body><nav class="topnav"><a class="brand" href="/">« $h</a><div class="topnav-links">{$this->nav()} | {$this->nav('t')}</div>
        <button class="theme-toggle" id="theme-icon">🌙</button><button class="menu-toggle">☰</button></nav>
        <main class="container mt-3">$m</main><footer class="container text-center mt-3"><small>$f</small></footer><script src="/spe.js"></script></body></html>
        HTML;
    }
}

final class SideBar extends Theme {
    public function html(): string {
        ['head' => $h, 'foot' => $f] = $this->ctx->out;
        [$m, $t, $o] = [$this->ary['main'], $this->ctx->in['t'], $this->ctx->in['o']];
        $n1 = implode('', array_map(fn($n) => '<a href="?o=' . $n[1] . '&t=' . $t . '"' . ($n[1] === $o ? ' class="active"' : '') . '>' . $n[0] . '</a>', $this->ctx->nav1));
        $n2 = implode('', array_map(fn($n) => '<a href="?o=' . $o . '&t=' . $n[1] . '"' . ($n[1] === $t ? ' class="active"' : '') . '>' . $n[0] . '</a>', $this->ctx->nav2));
        return $this->head('SideBar') . <<<HTML
        <body><nav class="topnav"><button class="menu-toggle">☰</button><a class="brand" href="/">« $h</a><button class="theme-toggle" id="theme-icon">🌙</button></nav>
        <div class="sidebar-layout"><aside class="sidebar"><div class="sidebar-group"><div class="sidebar-group-title">Pages</div><nav>$n1</nav></div>
        <div class="sidebar-group"><div class="sidebar-group-title">Themes</div><nav>$n2</nav></div></aside>
        <div class="sidebar-main"><main>$m</main><footer class="text-center mt-3"><small>$f</small></footer></div></div><script src="/spe.js"></script></body></html>
        HTML;
    }
}

echo new Init(new Ctx);
