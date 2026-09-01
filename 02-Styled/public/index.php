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
        'default' => ['oklch(50% 0.12 220)', 'Ocean'],
        'crimson' => ['oklch(47% 0.2 25)', 'Crimson'],
        'stone'   => ['oklch(45% 0.05 60)', 'Stone'],
        'forest'  => ['oklch(49% 0.12 150)', 'Forest'],
        'sunset'  => ['oklch(52% 0.16 45)', 'Sunset'],
        'mono'    => ['oklch(50% 0 0)', 'Mono'],
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
                '<button class="scheme-item" data-scheme="%s"><span class="scheme-dot" style="background:%s"></span><span class="scheme-name">%s</span></button>', $k, $s[0], $s[1]
            ), array_keys($schemes), $schemes))
            |> (static fn(array $buttons) => implode('', $buttons));

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
    <link rel="stylesheet" href="../spe.css">
    <script src="https://unpkg.com/lucide@1.33.0/dist/umd/lucide.min.js"></script>
    <script>(function(){var s=JSON.parse(localStorage.getItem('base-state')||'{}'),t=s.theme,c=s.scheme,h=document.documentElement;h.className='preload '+(t||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light'))+(c&&c!=='default'?' scheme-'+c:'')+(s.width==='wide'?' wide':(s.width==='narrow'?' narrow':''));})()</script>
</head>
<body>
<button class="menu-toggle" data-sidebar="left"><i data-lucide="menu"></i></button>
<button class="menu-toggle" data-sidebar="right"><i data-lucide="menu"></i></button>
<nav class="topnav">
    <h1><a class="brand" href="../"><span>« 02 Styled</span></a></h1>
</nav>
<aside class="sidebar sidebar-left">
    <div class="carousel-header">
        <div class="carousel-nav">
            <button class="carousel-chevron" data-sidebar="left" data-dir="prev"><i data-lucide="chevron-left"></i></button>
            <div class="carousel-dots">
                <button class="carousel-dot active" data-sidebar="left" data-panel="0"></button>
                <button class="carousel-dot" data-sidebar="left" data-panel="1"></button>
            </div>
            <button class="carousel-chevron" data-sidebar="left" data-dir="next"><i data-lucide="chevron-right"></i></button>
        </div>
        <button class="pin-toggle" data-sidebar="left" title="Pin sidebar"><i data-lucide="pin"></i></button>
    </div>
    <div class="panel-viewport">
        <div class="panel-track">
            <div class="panel">
                <div class="panel-title">Navigation</div>
                <div class="panel-content"><nav>{$nav}</nav></div>
            </div>
            <div class="panel">
                <div class="panel-title">About</div>
                <div class="panel-content"><nav>
                    <a href="../"><i data-lucide="layout-grid"></i> All chapters</a>
                    <a href="../docs/"><i data-lucide="book-open"></i> Documentation</a>
                    <div class="sidebar-divider"></div>
                    <a href="https://github.com/markc/spe"><i data-lucide="code"></i> SPE on GitHub</a>
                    <a href="https://www.youtube.com/playlist?list=PLM0Did14jsitwKl7RYaVrUWnG1GkRBO4B"><i data-lucide="video"></i> Video tutorials</a>
                    <a href="https://dcs.spa"><i data-lucide="panels-left-right"></i> DCS — this interface</a>
                </nav></div>
            </div>
        </div>
    </div>
</aside>
<aside class="sidebar sidebar-right">
    <div class="carousel-header">
        <button class="pin-toggle" data-sidebar="right" title="Pin sidebar"><i data-lucide="pin"></i></button>
        <div class="carousel-nav">
            <button class="carousel-chevron" data-sidebar="right" data-dir="prev"><i data-lucide="chevron-left"></i></button>
            <div class="carousel-dots">
                <button class="carousel-dot active" data-sidebar="right" data-panel="0"></button>
                <button class="carousel-dot" data-sidebar="right" data-panel="1"></button>
            </div>
            <button class="carousel-chevron" data-sidebar="right" data-dir="next"><i data-lucide="chevron-right"></i></button>
        </div>
    </div>
    <div class="panel-viewport">
        <div class="panel-track">
            <div class="panel">
                <div class="panel-title">Appearance</div>
                <div class="panel-content">
                    <div class="appearance-section">
                        <div class="toggle-group">
                            <button class="toggle-btn" data-theme="light">Light</button>
                            <button class="toggle-btn" data-theme="dark">Dark</button>
                        </div>
                        <div class="toggle-group">
                            <button class="toggle-btn" data-carousel="slide">Slide</button>
                            <button class="toggle-btn" data-carousel="fade">Fade</button>
                        </div>
                        <div class="toggle-group">
                            <button class="toggle-btn" data-width="narrow">Narrow</button>
                            <button class="toggle-btn" data-width="normal">Normal</button>
                            <button class="toggle-btn" data-width="wide">Wide</button>
                        </div>
                        <div class="sidebar-width-controls">
                            <div class="sidebar-width-control">
                                <label for="sidebar-width-left-input">Left %</label>
                                <input id="sidebar-width-left-input" type="number" class="sidebar-width-spinner" data-side="left" min="10" max="100" value="15" step="5">
                            </div>
                            <div class="sidebar-width-control">
                                <label for="sidebar-width-right-input">Right %</label>
                                <input id="sidebar-width-right-input" type="number" class="sidebar-width-spinner" data-side="right" min="10" max="100" value="15" step="5">
                            </div>
                        </div>
                        <div class="scheme-list">{$schemes}</div>
                    </div>
                </div>
            </div>
            <div class="panel">
                <div class="panel-title">Settings</div>
                <div class="panel-content"><nav>
                    <a href="#" class="theme-toggle"><i id="theme-icon" data-lucide="moon"></i> Toggle theme</a>
                </nav></div>
            </div>
        </div>
    </div>
</aside>
<main>{$this->main}</main>
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
    <p>The same three pages as chapter 01, now wearing the <b>app shell</b>: a top bar, a carousel of panels in each sidebar, light and dark themes and six colour schemes. Everything visual lives in <code>base.css</code>, <code>site.css</code> and <code>base.js</code>, shared by every later chapter.</p>
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
    <p>A plain <code>mailto:</code> link — no JavaScript. A form the server actually receives arrives in chapter 06.</p>
    <p><a class="btn" href="mailto:mc@netserva.org">Email us</a></p>
</div>
HTML;
    }
};
