<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

echo new class {
    private const string DEFAULT = 'home';

    private const array PAGES = [
        'home'    => ['home', 'Home'],
        'about'   => ['book-open', 'About'],
        'contact' => ['mail', 'Contact'],
    ];

    private const array SCHEMES = [
        'default' => ['circle', 'Stone'],
        'ocean'   => ['waves', 'Ocean'],
        'forest'  => ['trees', 'Forest'],
        'sunset'  => ['sunset', 'Sunset'],
    ];

    public private(set) string $page;
    public private(set) string $main;

    public function __construct()
    {
        $o = $_GET['o'] ?? '';
        $this->page = (is_string($o) ? $o : '')
            |> trim(...)
            |> strtolower(...)
            |> (static fn(string $p) => $p === '' ? self::DEFAULT : $p);

        if (!isset(self::PAGES[$this->page])) {
            http_response_code(404);
        }
        $this->main = match ($this->page) {
            'home' => $this->home(),
            'about' => $this->about(),
            'contact' => $this->contact(),
            default => '<div class="card"><h2>Not found</h2><p>There is no such page.</p></div>',
        };
    }

    public function __toString(): string
    {
        $nav = self::PAGES
            |> (fn(array $pages) => array_map(fn(string $k, array $p) => sprintf(
                '<a href="?o=%s"%s><i data-lucide="%s"></i> %s</a>', $k, $k === $this->page ? ' class="active"' : '', $p[0], $p[1]
            ), array_keys($pages), $pages))
            |> (static fn(array $links) => implode('', $links));

        $schemes = self::SCHEMES
            |> (static fn(array $schemes) => array_map(static fn(string $k, array $s) => sprintf(
                '<a href="#" data-scheme="%s"><i data-lucide="%s"></i> %s</a>', $k, $s[0], $s[1]
            ), array_keys($schemes), $schemes))
            |> (static fn(array $links) => implode('', $links));

        $title = self::PAGES[$this->page][1] ?? 'Not found';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SPE::02 {$title}</title>
    <link rel="stylesheet" href="../base.css">
    <link rel="stylesheet" href="../site.css">
    <script src="https://unpkg.com/lucide@1.33.0/dist/umd/lucide.min.js"></script>
    <script>(function(){var s=JSON.parse(localStorage.getItem('base-state')||'{}'),t=s.theme,c=s.scheme,h=document.documentElement;h.className='preload '+(t||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light'))+(c&&c!=='default'?' scheme-'+c:'');})()</script>
</head>
<body>
<nav class="topnav">
    <button class="menu-toggle" data-sidebar="left"><i data-lucide="menu"></i></button>
    <h1><a class="brand" href="../"><span>02 Styled</span></a></h1>
    <button class="menu-toggle" data-sidebar="right"><i data-lucide="menu"></i></button>
</nav>
<aside class="sidebar sidebar-left">
    <div class="sidebar-header"><span><i data-lucide="compass"></i> Navigation</span><button class="pin-toggle" data-sidebar="left" title="Pin sidebar"><i data-lucide="pin"></i></button></div>
    <nav>{$nav}</nav>
</aside>
<aside class="sidebar sidebar-right">
    <div class="sidebar-header"><span><i data-lucide="sliders-horizontal"></i> Settings</span><button class="pin-toggle" data-sidebar="right" title="Pin sidebar"><i data-lucide="pin"></i></button></div>
    <nav>{$schemes}<div class="sidebar-divider"></div><a href="#" class="theme-toggle"><i data-lucide="moon"></i> Toggle theme</a></nav>
</aside>
<main>{$this->main}</main>
<div class="overlay"></div>
<script src="../base.js"></script>
</body>
</html>
HTML;
    }

    private function home(): string
    {
        return <<<'HTML'
<div class="card">
    <h2>Home</h2>
    <p>The same three pages as chapter 01, now wearing the <b>app shell</b>: a top bar, a navigation sidebar on the left, a settings sidebar on the right, light and dark themes and four colour schemes. Everything visual lives in <code>base.css</code>, <code>site.css</code> and <code>base.js</code>, shared by every later chapter.</p>
    <p class="mt-4">
        <button class="btn btn-success" onclick="Base.toast('Saved.', 'success')">Success toast</button>
        <button class="btn btn-danger" onclick="Base.toast('Something went wrong.', 'danger')">Danger toast</button>
    </p>
</div>
HTML;
    }

    private function about(): string
    {
        return <<<'HTML'
<div class="card">
    <h2>About</h2>
    <p>This chapter adds presentation and nothing else. The PHP is still one anonymous class; only its <code>__toString()</code> grew, because the HTML it returns grew.</p>
</div>
HTML;
    }

    private function contact(): string
    {
        return <<<'HTML'
<div class="card">
    <h2>Contact</h2>
    <p>This form opens your email client. A form the server actually receives arrives in chapter 06.</p>
    <form class="mt-2" onsubmit="location.href='mailto:mc@netserva.org?subject='+encodeURIComponent(this.subject.value)+'&body='+encodeURIComponent(this.message.value);return false">
        <div class="form-group"><label for="subject">Subject</label><input type="text" id="subject" name="subject" required></div>
        <div class="form-group"><label for="message">Message</label><textarea id="message" name="message" rows="4" required></textarea></div>
        <div class="text-right"><button type="submit" class="btn">Send</button></div>
    </form>
</div>
HTML;
    }
};
