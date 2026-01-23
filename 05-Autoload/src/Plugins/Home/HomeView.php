<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Plugins\Home;

use SPE\Autoload\Core\View;

final class HomeView extends View
{
    #[\Override]
    public function list(): string
    {
        return <<<HTML
<div class="card">
    <h2>{$this->ary['head']}</h2>
    {$this->ary['main']}

    <h3>Why This Chapter Exists</h3>
    <p>Chapter 04-Themes demonstrated the app shell pattern in a <b>single 244-line file</b>. While this works for small projects, real-world applications require better organization. This chapter introduces <b>PSR-4 autoloading</b> to split code into logical, maintainable units.</p>

    <h3>What Changed from 04-Themes</h3>
    <table class="data-table mt-2">
        <thead><tr><th>Aspect</th><th>04-Themes</th><th>05-Autoload</th></tr></thead>
        <tbody>
            <tr><td>File Structure</td><td>Single <code>index.php</code> (244 lines)</td><td>10 files across <code>src/</code> (259 lines)</td></tr>
            <tr><td>Class Loading</td><td>All classes in one file</td><td>PSR-4 autoloading via Composer</td></tr>
            <tr><td>Namespaces</td><td>Global namespace</td><td><code>SPE\Autoload\*</code></td></tr>
            <tr><td>Dependencies</td><td>None</td><td>Composer for autoloading</td></tr>
            <tr><td>Entry Point</td><td><code>index.php</code> (244 lines)</td><td><code>index.php</code> (8 lines)</td></tr>
        </tbody>
    </table>

    <h3>PSR-4 Autoloading</h3>
    <p>PSR-4 is a PHP standard that maps <b>namespaces to directories</b>. When you reference a class like <code>SPE\Autoload\Core\Init</code>, Composer automatically loads <code>src/Core/Init.php</code>. No manual <code>require</code> statements needed.</p>
<pre>// composer.json autoload configuration
"psr-4": {
    "SPE\\Autoload\\": "05-Autoload/src"
}

// Usage - Composer finds and loads the file automatically
use SPE\Autoload\Core\{Init, Ctx};
echo new Init(new Ctx);</pre>

    <h3>Directory Structure</h3>
<pre>05-Autoload/
├── public/
│   └── index.php       # 8 lines - just requires autoloader and boots
└── src/
    ├── Core/           # Framework classes (144 lines total)
    │   ├── Ctx.php     # Configuration and input handling
    │   ├── Init.php    # Bootstrap: Model → View → Theme pipeline
    │   ├── Plugin.php  # Abstract base for all models
    │   ├── Theme.php   # App shell layout (topnav, sidebars, main)
    │   └── View.php    # Base view with default card template
    └── Plugins/        # Feature modules (115 lines total)
        ├── Home/       # HomeModel.php, HomeView.php
        ├── About/      # AboutModel.php (uses base View)
        └── Contact/    # ContactModel.php, ContactView.php</pre>

    <h3>The Plugin Pattern</h3>
    <p>Each plugin follows a <b>Model + View</b> convention:</p>
    <ul>
        <li><b>Model</b> — Returns data array with <code>head</code> and <code>main</code> keys</li>
        <li><b>View</b> — Transforms data into HTML (optional, falls back to base View)</li>
    </ul>
<pre>// Init.php dispatches to plugins dynamically
\$model = "SPE\\Autoload\\Plugins\\{\$o}\\{\$o}Model";
\$ary = new \$model(\$ctx)->list();  // e.g., HomeModel->list()

\$view = "SPE\\Autoload\\Plugins\\{\$o}\\{\$o}View";
\$main = new \$view(\$ctx, \$ary)->list();  // e.g., HomeView->list()</pre>

    <h3>Benefits of This Structure</h3>
    <ul>
        <li><b>Separation of Concerns</b> — Each class has one responsibility</li>
        <li><b>Testability</b> — Individual classes can be unit tested</li>
        <li><b>Scalability</b> — Add plugins without modifying core files</li>
        <li><b>IDE Support</b> — Autocompletion, refactoring, go-to-definition</li>
        <li><b>Dependency Management</b> — Composer handles third-party packages</li>
        <li><b>Modern PHP</b> — Follows PSR standards used by Laravel, Symfony, etc.</li>
    </ul>

    <h3>Running This Chapter</h3>
<pre># Install Composer dependencies (from project root)
composer install

# Start PHP development server
cd 05-Autoload/public && php -S localhost:8080

# Or run from project root (serves all chapters)
php -S localhost:8000 index.php</pre>
</div>
<div class="flex justify-center mt-4">
    <button class="btn btn-success" onclick="showToast('Success!', 'success')">Success</button>
    <button class="btn btn-danger" onclick="showToast('Error!', 'danger')">Danger</button>
</div>
HTML;
    }
}
