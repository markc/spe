<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

echo new class {
    private const string DEFAULT = 'home';
    private array $pages = [
        'home'    => ['🏠 Home', 'Home Page', 'Welcome to the <b>Styled</b> example with external CSS and JavaScript.'],
        'about'   => ['📋 About', 'About Page', 'This chapter adds <b>dark mode</b> theming and <b>toast</b> notifications.'],
        'contact' => ['✉️ Contact', 'Contact Page', 'Get in touch using the <b>email form</b> below.'],
    ];
    public private(set) string $page;
    public private(set) string $title;
    public private(set) string $content;

    public function __construct() {
        $this->page = (isset($_GET['m']) && is_string($_GET['m']) ? $_GET['m'] : '')
            |> trim(...)
            |> htmlspecialchars(...)
            |> (fn($p) => $p && isset($this->pages[$p]) ? $p : self::DEFAULT);
        $this->title = $this->pages[$this->page][1];
        $this->content = $this->pages[$this->page][2];
    }

    public function __toString(): string {
        $nav = $this->pages
            |> array_keys(...)
            |> (fn($k) => array_map(fn($p) => sprintf(
                '<a href="?m=%s"%s>%s</a>',
                $p, $p === $this->page ? ' class="active"' : '', $this->pages[$p][0]
            ), $k))
            |> (static fn($a) => implode(' ', $a));

        $main = $this->page === 'contact' ? $this->contactForm() : "<p>{$this->content}</p>";

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>SPE::02 {$this->title}</title>
    <link rel="stylesheet" href="/base.css">
    <link rel="stylesheet" href="/site.css">
    <script>(function(){const t=localStorage.getItem("base-theme");document.documentElement.className=t||(matchMedia("(prefers-color-scheme:dark)").matches?"dark":"light")})();</script>
</head>
<body>
<div class="container">
    <header><h1><a class="brand" href="/">🐘 Styled PHP Example</a></h1></header>
    <nav class="card flex">{$nav}<span class="ml-auto"><button class="theme-toggle" id="theme-icon">🌙</button></span></nav>
    <main>
        <div class="card">
            <h2>{$this->title}</h2>
            {$main}
        </div>
        <div class="flex justify-center mt-2">
            <button class="btn btn-success" onclick="showToast('Success!', 'success')">Success</button>
            <button class="btn btn-danger" onclick="showToast('Error!', 'danger')">Danger</button>
        </div>
    </main>
    <footer class="text-center mt-3"><small>© 2015-2025 Mark Constable (MIT License)</small></footer>
</div>
<script src="/base.js"></script>
</body>
</html>
HTML;
    }

    private function contactForm(): string {
        return <<<HTML
        <p>{$this->content}</p>
        <form class="mt-2" onsubmit="return handleContact(this)">
            <div class="form-group"><label for="subject">Subject</label><input type="text" id="subject" name="subject" required></div>
            <div class="form-group"><label for="message">Message</label><textarea id="message" name="message" rows="4" required></textarea></div>
            <div class="text-right"><button type="submit" class="btn">Send Message</button></div>
        </form>
        <script>
        function handleContact(form) {
            location.href = 'mailto:mc@netserva.org?subject=' + encodeURIComponent(form.subject.value) + '&body=' + encodeURIComponent(form.message.value);
            showToast('Opening email client...', 'success');
            return false;
        }
        </script>
        HTML;
    }
};
