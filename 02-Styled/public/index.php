<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

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

        $main = match($this->page) {
            'home' => $this->homeContent(),
            'contact' => $this->contactForm(),
            default => "<p>{$this->content}</p>"
        };

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>SPE::02 {$this->title}</title>
    <link rel="stylesheet" href="../base.css">
    <link rel="stylesheet" href="../site.css">
    <script>(function(){const t=localStorage.getItem("base-theme");document.documentElement.className=t||(matchMedia("(prefers-color-scheme:dark)").matches?"dark":"light")})();</script>
</head>
<body>
<div class="container">
    <header class="mt-4"><h1><a class="brand" href="../">‹ <span>Styled PHP Example</span></a></h1></header>
    <nav class="card flex">{$nav}<span class="ml-auto"><button class="theme-toggle" id="theme-icon">🌙</button></span></nav>
    <main class="mt-4 mb-4">
        <div class="card-hover">
            <h2>{$this->title}</h2>
            {$main}
        </div>
        <div class="flex justify-center mt-4">
            <button class="btn-hover btn-success" onclick="showToast('Success!', 'success')">Success</button>
            <button class="btn-hover btn-danger" onclick="showToast('Error!', 'danger')">Danger</button>
        </div>
    </main>
    <footer class="text-center"><small>© 2015-2026 Mark Constable (MIT License)</small></footer>
</div>
<script src="../base.js"></script>
</body>
</html>
HTML;
    }

    private function homeContent(): string {
        return <<<'HTML'
<p>Welcome to the <b>Styled</b> chapter. While this page looks similar to <a href="../01-Simple/">01-Simple</a>, several key improvements have been made.</p>

<h3 class="mt-4">What's New?</h3>
<ul class="mt-2" style="list-style:disc;padding-left:1.5rem">
    <li><b>External CSS</b> — Styles moved from inline <code>&lt;style&gt;</code> to <code>base.css</code> and <code>site.css</code> files</li>
    <li><b>External JavaScript</b> — Script moved from inline to <code>base.js</code> for theme toggle and toast notifications</li>
    <li><b>Dark Mode Toggle</b> — Click the 🌙 button to switch between light and dark themes (persists via localStorage)</li>
    <li><b>Toast Notifications</b> — Try the Success/Danger buttons below to see toast messages</li>
    <li><b>Card Hover Effects</b> — Cards lift on hover with smooth shadow transitions</li>
</ul>

<h3 class="mt-4">CSS Architecture</h3>
<p><code>base.css</code> provides the color-agnostic framework (layouts, components, utilities). <code>site.css</code> defines all colors and themes. This separation allows themes to be swapped by just changing <code>site.css</code>.</p>

<h3 class="mt-4">Same PHP Structure</h3>
<p>The PHP code remains a single-file anonymous class like 01-Simple. The key difference is the move to external assets, preparing for the component-based approach in later chapters.</p>
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
