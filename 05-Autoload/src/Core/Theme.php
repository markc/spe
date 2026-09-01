<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Core;

/**
 * The HTML document around a view — the DCS (Dual Carousel Sidebars) app shell from
 * https://dcs.spa: a top bar, two off-canvas sidebars each holding a small carousel of
 * panels (two here, enough to show the pattern), light/dark themes and six colour schemes.
 * Everything visual lives in base.css / site.css / base.js, vendored verbatim from dcs.spa.
 */
final class Theme
{
    public function __construct(private readonly Ctx $ctx, private readonly array $out) {}

    public function render(): string
    {
        $nav = $this->ctx->nav
            |> (fn(array $items) => array_map(fn(array $n) => sprintf(
                '<a href="?o=%s"%s><i data-lucide="%s"></i> %s</a>', $n[2], $n[2] === $this->ctx->in['o'] ? ' class="active"' : '', $n[0], $n[1]
            ), $items))
            |> (static fn(array $links) => implode('', $links));

        $schemes = $this->ctx->schemes
            |> (static fn(array $items) => array_map(static fn(array $s) => sprintf(
                '<button class="scheme-item" data-scheme="%s"><span class="scheme-dot" style="background:%s"></span><span class="scheme-name">%s</span></button>', $s[2], $s[0], $s[1]
            ), $items))
            |> (static fn(array $buttons) => implode('', $buttons));

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$this->out['doc']} {$this->ctx->in['o']}</title>
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
    <h1><a class="brand" href="../"><span>« {$this->out['page']}</span></a></h1>
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
<main>{$this->out['main']}</main>
<script src="../base.js"></script>
</body>
</html>
HTML;
    }
}
