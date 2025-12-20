<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

readonly class Ctx {
    public function __construct(
        public string $email = 'mc@netserva.org',
        public array $in = ['o' => 'Home', 'm' => 'list', 'x' => ''],
        public array $out = ['doc' => 'SPE::03', 'nav' => '', 'head' => '', 'main' => '', 'foot' => ''],
        public array $nav = [['🏠 Home', 'Home'], ['📖 About', 'About'], ['✉️ Contact', 'Contact']]
    ) {}
}

readonly class Init {
    private array $in;
    private array $out;

    public function __construct(private Ctx $ctx) {
        $this->in = array_map(fn($k, $v) => ($_REQUEST[$k] ?? $v)
            |> trim(...)
            |> htmlspecialchars(...), array_keys($ctx->in), $ctx->in)
            |> (fn($v) => array_combine(array_keys($ctx->in), $v));
        $this->out = [...$ctx->out, 'main' => $this->dispatch()];
    }

    private function dispatch(): string {
        [$o, $m] = [$this->in['o'], $this->in['m']];
        return match (true) {
            !class_exists($o) => '<p>Error: plugin not found</p>',
            !method_exists($o, $m) => '<p>Error: method not found</p>',
            default => (new $o($this->ctx))->$m()
        };
    }

    public function __toString(): string {
        return match ($this->in['x']) {
            'json' => (header('Content-Type: application/json') ?: '') . json_encode($this->out),
            default => $this->html()
        };
    }

    private function html(): string {
        $nav = $this->ctx->nav
            |> (fn($n) => array_map(fn($p) => sprintf(
                '<a href="?o=%s"%s>%s</a>',
                $p[1], $this->in['o'] === $p[1] ? ' class="active"' : '', $p[0]
            ), $n))
            |> (fn($a) => implode(' ', $a));

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>SPE::03 {$this->in['o']}</title>
    <link rel="stylesheet" href="/spe.css">
</head>
<body>
<div class="container">
    <header><h1><a class="brand" href="/">🐘 Plugins PHP Example</a></h1></header>
    <nav class="card flex">$nav<span class="ml-auto"><button class="theme-toggle" id="theme-icon">🌙</button></span></nav>
    <main>{$this->out['main']}</main>
    <footer class="text-center mt-3"><small>© 2015-2025 Mark Constable (MIT License)</small></footer>
</div>
<script src="/spe.js"></script>
</body>
</html>
HTML;
    }
}

abstract class Plugin {
    public function __construct(protected Ctx $ctx) {}
    public function create(): string { return '<p>Create: not implemented</p>'; }
    public function read(): string { return '<p>Read: not implemented</p>'; }
    public function update(): string { return '<p>Update: not implemented</p>'; }
    public function delete(): string { return '<p>Delete: not implemented</p>'; }
    public function list(): string { return '<p>List: not implemented</p>'; }
}

final class Home extends Plugin {
    #[\Override] public function list(): string {
        return <<<'HTML'
        <div class="card">
            <h2>Home Page</h2>
            <p>Welcome to the <b>Plugins</b> example demonstrating the plugin architecture with CRUDL methods.</p>
        </div>
        <div class="flex justify-center mt-2">
            <button class="btn btn-success" onclick="showToast('Success!', 'success')">Success</button>
            <button class="btn btn-danger" onclick="showToast('Error!', 'danger')">Danger</button>
        </div>
        HTML;
    }
}

final class About extends Plugin {
    #[\Override] public function list(): string {
        return <<<HTML
        <div class="card">
            <h2>About Page</h2>
            <p>This chapter adds the <b>plugin architecture</b> with CRUDL methods and JSON API output.</p>
        </div>
        HTML;
    }
}

final class Contact extends Plugin {
    #[\Override] public function list(): string {
        return <<<HTML
        <div class="card">
            <h2>Contact Page</h2>
            <p>Get in touch using the <b>email form</b> below.</p>
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

echo new Init(new Ctx);
