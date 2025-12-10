<?php declare(strict_types=1);
// Created: 20150101 - Updated: 20251209
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

class Ctx {                                                                     // Global context/state
    public function __construct(
        public private(set) string $email = 'mc@netserva.org',                  // PHP 8.4 asymmetric visibility
        public array $in = ['l' => '', 'm' => 'list', 'o' => 'Home', 'x' => ''],
        public array $out = [
            'doc' => 'SPE::03', 'head' => 'Plugins PHP Example',
            'main' => 'Error: missing plugin!',
            'foot' => '© 2015-2025 Mark Constable (MIT License)'
        ],
        public array $nav = [
            ['🏠 Home', 'Home'], ['📖 About', 'About'], ['✉️ Contact', 'Contact']
        ]
    ) {}
}

readonly class Init {                                                           // PHP 8.2 readonly class
    public function __construct(private Ctx $ctx) {
        foreach ($this->ctx->in as $k => $v)                                    // Process input
            $this->ctx->in[$k] = ($_REQUEST[$k] ?? $v) |> trim(...) |> htmlspecialchars(...);

        ['o' => $o, 'm' => $m] = $this->ctx->in;                               // PHP 8.5 pipe + destructure
        $this->ctx->out['main'] = match (true) {
            !class_exists($o) => 'Error: plugin not found!',
            !method_exists($o, $m) => 'Error: method not found!',
            default => (new $o($this->ctx))->$m()
        };
    }

    public function __toString(): string {
        return match ($this->ctx->in['x']) {
            'json' => json_encode($this->ctx->out, JSON_PRETTY_PRINT)
                |> (fn($j) => (header('Content-Type: application/json') ?: '') . $j),
            default => $this->html()
        };
    }

    private function html(): string {
        $nav = $this->ctx->nav
            |> (fn($items) => array_map(fn($n) => sprintf(
                '<a href="?o=%s"%s>%s</a>',
                $n[1], $this->ctx->in['o'] === $n[1] ? ' class="active"' : '', $n[0]
            ), $items))
            |> (fn($links) => implode(' ', $links));

        extract($this->ctx->out);
        return <<<HTML
        <!DOCTYPE html><html lang="en"><head>
            <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
            <meta name="color-scheme" content="light dark">
            <title>$doc</title><link rel="stylesheet" href="/spe.css">
        </head><body><div class="container">
            <header><h1><a href="../">« $head</a></h1></header>
            <nav class="flex">$nav<span style="margin-left:auto"><button class="theme-toggle" id="theme-icon">🌙</button></span></nav>
            <main>$main</main>
            <footer class="text-center mt-3"><small>$foot</small></footer>
        </div><script src="/spe.js"></script></body></html>
        HTML;
    }
}

abstract class Plugin {                                                         // Base CRUDL plugin
    public function __construct(protected Ctx $ctx) {}
    public function create(): string { return 'Create not implemented'; }
    public function read(): string { return 'Read not implemented'; }
    public function update(): string { return 'Update not implemented'; }
    public function delete(): string { return 'Delete not implemented'; }
    public function list(): string { return 'List not implemented'; }
}

final class Home extends Plugin {
    #[\Override] public function list(): string {                               // PHP 8.3 Override attribute
        return <<<HTML
        <div class="card">
            <h2>🏠 Home Page</h2>
            <p>Welcome to SPE::03 Plugins - demonstrating the plugin architecture pattern.</p>
            <p>This example introduces separate Plugin classes with CRUDL methods (Create, Read, Update, Delete, List).</p>
            <div class="flex justify-center mt-2">
                <button class="btn btn-success" onclick="showToast('Success!','success')">Success</button>
                <button class="btn btn-danger" onclick="showToast('Error!','danger')">Danger</button>
            </div>
        </div>
        HTML;
    }
}

final class About extends Plugin {
    #[\Override] public function list(): string {
        return <<<HTML
        <div class="card">
            <h2>📖 About Page</h2>
            <p>This framework demonstrates modern PHP 8.5 patterns:</p>
            <ul>
                <li><strong>PHP 8.3:</strong> #[Override] attribute, typed constants</li>
                <li><strong>PHP 8.4:</strong> Asymmetric visibility (public private(set))</li>
                <li><strong>PHP 8.5:</strong> Pipe operator for data transformation</li>
            </ul>
            <p>Contact: <a href="mailto:{$this->ctx->email}">{$this->ctx->email}</a></p>
        </div>
        HTML;
    }
}

final class Contact extends Plugin {
    #[\Override] public function list(): string {
        return <<<HTML
        <div class="card">
            <h2>✉️ Contact Page</h2>
            <form onsubmit="return handleContact(this)">
                <div class="form-group"><label for="subject">Subject</label>
                    <input type="text" id="subject" required></div>
                <div class="form-group"><label for="message">Message</label>
                    <textarea id="message" rows="4" required></textarea></div>
                <div class="text-right"><button type="submit" class="btn">Send</button></div>
            </form>
        </div>
        <script>
        function handleContact(f) {
            location.href='mailto:{$this->ctx->email}?subject='+encodeURIComponent(f.subject.value)+'&body='+encodeURIComponent(f.message.value);
            showToast('Opening email...','success'); return false;
        }
        </script>
        HTML;
    }
}

echo new Init(new Ctx);                                                         // PHP 8.4 new without parens
