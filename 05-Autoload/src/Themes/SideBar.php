<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Themes;

use SPE\Autoload\Core\Theme;

final class SideBar extends Theme
{
    #[\Override]
    public function render(): string
    {
        ['o' => $o, 't' => $t] = $this->ctx->in;
        $n1 = $this->ctx->nav
            |> (static fn($n) => array_map(static fn($p) => sprintf(
                '<a href="?o=%s&t=%s"%s title="%s" data-icon="%s"><i data-lucide="%s"></i> %s</a>',
                $p[2], $t, $o === $p[2] ? ' class="active"' : '', $p[1], $p[0], $p[0], $p[1],
            ), $n))
            |> (static fn($a) => implode('', $a));
        $n2 = $this->ctx->themes
            |> (static fn($n) => array_map(static fn($p) => sprintf(
                '<a href="?o=%s&t=%s"%s title="%s" data-icon="%s"><i data-lucide="%s"></i> %s</a>',
                $o, $p[2], $t === $p[2] ? ' class="active"' : '', $p[1], $p[0], $p[0], $p[1],
            ), $n))
            |> (static fn($a) => implode('', $a));
        $body = <<<HTML
        <nav class="topnav">
            <button class="menu-toggle"><i data-lucide="menu"></i></button>
            <h1><a class="brand" href="/"><i data-lucide="chevron-left"></i> <span>Autoload PHP Example</span></a></h1>
            <button class="theme-toggle" id="theme-icon"><i data-lucide="moon"></i></button>
        </nav>
        <div class="sidebar-layout">
            <aside class="sidebar">
                <div class="sidebar-group">
                    <div class="sidebar-group-title" data-icon="file-text"><i data-lucide="file-text"></i> Pages</div>
                    <nav>$n1</nav>
                </div>
                <div class="sidebar-group">
                    <div class="sidebar-group-title" data-icon="layout-grid"><i data-lucide="layout-grid"></i> Layout</div>
                    <nav>$n2</nav>
                </div>
                <div class="sidebar-group">
                    <div class="sidebar-group-title" data-icon="swatch-book"><i data-lucide="swatch-book"></i> Colors</div>
                    <nav>
                        <a href="#" data-scheme="default" title="Stone" data-icon="circle"><i data-lucide="circle"></i> Stone</a>
                        <a href="#" data-scheme="ocean" title="Ocean" data-icon="waves"><i data-lucide="waves"></i> Ocean</a>
                        <a href="#" data-scheme="forest" title="Forest" data-icon="trees"><i data-lucide="trees"></i> Forest</a>
                        <a href="#" data-scheme="sunset" title="Sunset" data-icon="sunset"><i data-lucide="sunset"></i> Sunset</a>
                    </nav>
                </div>
                <button class="sidebar-toggle" aria-label="Toggle sidebar"></button>
            </aside>
            <div class="sidebar-main">
                <main class="mt-4 mb-4">{$this->out['main']}</main>
                <footer class="text-center"><small>© 2015-2026 Mark Constable (MIT License)</small></footer>
            </div>
        </div>
        HTML;
        return $this->html('SideBar', $body);
    }
}
