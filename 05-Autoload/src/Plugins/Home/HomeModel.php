<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Plugins\Home;

use SPE\Autoload\Core\Plugin;

final class HomeModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return [
            'head' => 'Home Page',
            'main' => '<p>Welcome to the <b>Autoload</b> chapter demonstrating PSR-4 autoloading via Composer.</p>
<h3>What\'s New</h3>
<ul>
    <li><b>PSR-4 Autoloading</b> — Classes organized into <code>src/</code> with namespace <code>SPE\Autoload</code></li>
    <li><b>Lucide Icons</b> — SVG icon library replaces emoji icons for a polished look</li>
    <li><b>Directory Structure</b> — Separated into <code>Core/</code>, <code>Plugins/</code>, and <code>Themes/</code></li>
    <li><b>Composer Integration</b> — Dependency management and class autoloading</li>
</ul>
<h3>Directory Layout</h3>
<pre>05-Autoload/
├── composer.json
├── public/index.php
└── src/
    ├── Core/       # Ctx, Init, Plugin, Theme, View
    ├── Plugins/    # Home/, About/, Contact/
    └── Themes/     # Simple, TopNav, SideBar</pre>',
        ];
    }
}
