<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

if (!class_exists('Ctx')) {
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
            $this->in = array_map(static fn($k, $v) => (isset($_GET[$k]) && is_string($_GET[$k]) ? $_GET[$k] : $v)
                |> trim(...)
                |> htmlspecialchars(...), array_keys($ctx->in), $ctx->in)
                |> (static fn($v) => array_combine(array_keys($ctx->in), $v));
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
                'json' => (header('Content-Type: application/json') ? '' : '') . json_encode($this->out),
                default => $this->html()
            };
        }

        private function html(): string {
            $nav = $this->ctx->nav
                |> (fn($n) => array_map(fn($p) => sprintf(
                    '<a href="?o=%s"%s>%s</a>',
                    $p[1], $this->in['o'] === $p[1] ? ' class="active"' : '', $p[0]
                ), $n))
                |> (static fn($a) => implode(' ', $a));

            return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>SPE::03 {$this->in['o']}</title>
    <link rel="stylesheet" href="../base.css">
    <link rel="stylesheet" href="../site.css">
    <script>(function(){const t=localStorage.getItem("base-theme");document.documentElement.className=t||(matchMedia("(prefers-color-scheme:dark)").matches?"dark":"light")})();</script>
</head>
<body>
<div class="container">
    <header class="mt-4"><h1><a class="brand" href="../">‹ <span>Plugins PHP Example</span></a></h1></header>
    <nav class="card flex">{$nav}<span class="ml-auto"><button class="theme-toggle" id="theme-icon">🌙</button></span></nav>
    <main class="mt-4 mb-4">{$this->out['main']}</main>
    <footer class="text-center"><small>© 2015-2026 Mark Constable (MIT License)</small></footer>
</div>
<script src="../base.js"></script>
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
                <h2>Plugin Architecture</h2>
                <p>While this page looks similar to <a href="../02-Styled/">02-Styled</a>, the underlying PHP structure has been completely reorganized to use a <b>plugin-based architecture</b>.</p>

                <h3 class="mt-4">What Changed?</h3>
                <p>In 02-Styled, page content was stored in a simple array within an anonymous class. Here, each page is now a separate <b>Plugin class</b> that extends an abstract base class with standardized methods.</p>

                <h3 class="mt-4">Core Classes</h3>
                <ul class="mt-2" style="list-style:disc;padding-left:1.5rem">
                    <li><b>Ctx</b> — Context class holding configuration: email, input parameters, output array, and navigation items</li>
                    <li><b>Init</b> — Handles URL routing, plugin dispatch, and HTML/JSON rendering</li>
                    <li><b>Plugin</b> — Abstract base class defining the CRUDL interface (Create, Read, Update, Delete, List)</li>
                    <li><b>Home, About, Contact</b> — Concrete plugin classes that override the <code>list()</code> method</li>
                </ul>

                <h3 class="mt-4">CRUDL Pattern</h3>
                <p>Each plugin inherits five methods: <code>create()</code>, <code>read()</code>, <code>update()</code>, <code>delete()</code>, and <code>list()</code>. By default, these return "not implemented" — plugins override only what they need. Try: <a href="?o=Home&m=create">?o=Home&m=create</a></p>

                <h3 class="mt-4">URL Routing</h3>
                <p>The URL parameter <code>?o=</code> selects the plugin (object), and <code>?m=</code> selects the method. Compare this to 02-Styled's simple <code>?m=page</code> approach.</p>

                <h3 class="mt-4">JSON API</h3>
                <p>Add <code>?x=json</code> to any URL to get JSON output instead of HTML. Try: <a href="?o=Home&x=json">?o=Home&x=json</a></p>

                <h3 class="mt-4">Adding a New Plugin</h3>
                <p>To add a new page: 1) Create a class extending <code>Plugin</code>, 2) Override the methods you need, 3) Add a nav entry in <code>Ctx::$nav</code>. That's it — no template files, no routing configuration.</p>
            </div>
            <div class="flex justify-center mt-4">
                <button class="btn-hover btn-success" onclick="showToast('Success!', 'success')">Success</button>
                <button class="btn-hover btn-danger" onclick="showToast('Error!', 'danger')">Danger</button>
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
}

echo new Init(new Ctx);
