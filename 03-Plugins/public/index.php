<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

readonly class Ctx {
    public function __construct(
        public string $email = 'mc@netserva.org',
        public array $in = ['m' => 'list', 'o' => 'Home', 'x' => ''],
        public array $out = ['doc' => 'SPE::03', 'head' => 'Plugins PHP Example', 'main' => '', 'foot' => '© 2015-2025 Mark Constable (MIT License)'],
        public array $nav = [['🏠 Home', 'Home'], ['📖 About', 'About'], ['✉️ Contact', 'Contact']]
    ) {}
}

readonly class Init {
    private array $in;
    private array $out;

    public function __construct(private Ctx $ctx) {
        $this->in = array_map(fn($k, $v) => ($_REQUEST[$k] ?? $v) |> trim(...) |> htmlspecialchars(...), array_keys($ctx->in), $ctx->in)
            |> (fn($v) => array_combine(array_keys($ctx->in), $v));
        $this->out = [...$ctx->out, 'main' => $this->dispatch()];
    }

    private function dispatch(): string {
        [$o, $m] = [$this->in['o'], $this->in['m']];
        return !class_exists($o) ? 'Error: plugin not found!' : (!method_exists($o, $m) ? 'Error: method not found!' : (new $o($this->ctx))->$m());
    }

    public function __toString(): string {
        return match ($this->in['x']) {
            'json' => (header('Content-Type: application/json') ?: '') . json_encode($this->out),
            default => $this->html()
        };
    }

    private function html(): string {
        $nav = implode(' ', array_map(fn($n) => sprintf('<a href="?o=%s"%s>%s</a>', $n[1], $this->in['o'] === $n[1] ? ' class="active"' : '', $n[0]), $this->ctx->nav));
        ['doc' => $d, 'head' => $h, 'main' => $m, 'foot' => $f] = $this->out;
        return <<<HTML
        <!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="color-scheme" content="light dark"><title>$d</title><link rel="stylesheet" href="/spe.css"></head>
        <body><div class="container"><header><h1><a href="/">« $h</a></h1></header>
        <nav class="flex">$nav<span style="margin-left:auto"><button class="theme-toggle" id="theme-icon">🌙</button></span></nav>
        <main>$m</main><footer class="text-center mt-3"><small>$f</small></footer></div><script src="/spe.js"></script></body></html>
        HTML;
    }
}

abstract class Plugin {
    public function __construct(protected Ctx $ctx) {}
    public function create(): string { return 'Create: not implemented'; }
    public function read(): string { return 'Read: not implemented'; }
    public function update(): string { return 'Update: not implemented'; }
    public function delete(): string { return 'Delete: not implemented'; }
    public function list(): string { return 'List: not implemented'; }
}

final class Home extends Plugin {
    #[\Override] public function list(): string {
        return <<<'HTML'
        <div class="card"><h2>🏠 Home Page</h2>
        <p>Welcome to SPE::03 Plugins - demonstrating the plugin architecture pattern with CRUDL methods.</p>
        <div class="flex justify-center mt-2"><button class="btn btn-success" onclick="showToast('Success!','success')">Success</button>
        <button class="btn btn-danger" onclick="showToast('Error!','danger')">Danger</button></div></div>
        HTML;
    }
}

final class About extends Plugin {
    #[\Override] public function list(): string {
        return <<<HTML
        <div class="card"><h2>📖 About Page</h2><p>Modern PHP 8+ patterns demonstrated:</p>
        <ul><li><strong>PHP 8.2:</strong> Readonly classes</li><li><strong>PHP 8.3:</strong> #[Override] attribute</li>
        <li><strong>PHP 8.5:</strong> Pipe operator for data transformation</li></ul>
        <p>Contact: <a href="mailto:{$this->ctx->email}">{$this->ctx->email}</a></p></div>
        HTML;
    }
}

final class Contact extends Plugin {
    #[\Override] public function list(): string {
        return <<<HTML
        <div class="card"><h2>✉️ Contact Page</h2>
        <form onsubmit="return(location.href='mailto:{$this->ctx->email}?subject='+encodeURIComponent(this.subject.value)+'&body='+encodeURIComponent(this.message.value),showToast('Opening email...','success'),false)">
        <div class="form-group"><label for="subject">Subject</label><input type="text" id="subject" required></div>
        <div class="form-group"><label for="message">Message</label><textarea id="message" rows="4" required></textarea></div>
        <div class="text-right"><button type="submit" class="btn">Send</button></div></form></div>
        HTML;
    }
}

echo new Init(new Ctx);
