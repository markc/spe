<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

define('DBG', true);

// Dynamic writable global context/state properties
class Ctx
{
    public function __construct(
        public string $email = 'markc@renta.net',
        public string $buf = '',    // Global string buffer
        public array $ary = [],     // Plugin CRUDL return array
        public array $in = [        // Input URI variables
            'l' => '',              // Log (alert)
            'm' => 'read',          // Method (action)
            'o' => 'Home',          // Object (plugin)
            't' => 'TopNav',        // Theme (current)
            'x' => '',              // XHR (request)
        ],
        public array $out = [       // Theme Method partials
            'doc'   => 'SPE::04 Themes',
            'css'   => '',
            'log'   => '',
            'nav1'  => '',
            'nav2'  => '',
            'head'  => 'Themes PHP Example',
            'main'  => 'Error: missing plugin!',
            'foot'  => 'Copyright (C) 2015-2025 Mark Constable (AGPL-3.0)',
            'js'    => '',
        ],
        public array $nav1 = ['Pages',      [
            ['Home',        '?o=Home',      'bi bi-house-door'],
            ['About',       '?o=About',     'bi bi-question-octagon'],
            ['Contact',     '?o=Contact',   'bi bi-person-rolodex']
        ], 'bi bi-list'],
        public array $nav2 = ['Themes',     [
            ['Simple',      '?t=Simple',    'bi bi-gear'],
            ['TopNav',      '?t=TopNav',    'bi bi-gear'],
            ['Sidebar',     '?t=SideBar',   'bi bi-gear'],
        ], 'bi bi-list'],
    )
    {
        Util::elog(__METHOD__);
    }
}

readonly class Init
{
    public function __construct(
        private Ctx $ctx
    )
    {
        Util::elog(__METHOD__);

        // Process input parameters
        foreach ($this->ctx->in as $k => $v)
        {
            $this->ctx->in[$k] = $_REQUEST[$k] ?? $v;
            if (isset($_REQUEST[$k]))
            {
                $this->ctx->in[$k] = htmlentities(trim($_REQUEST[$k]));
            }
        }
        //Util::elog('this->ctx->in=' . var_export($this->ctx->in, true));
        extract($this->ctx->in, EXTR_SKIP);

        $pm = "{$o}Model";
        $t1 = "{$o}View";
        $t2 = "{$t}";
        //Util::elog("o={$o}, m={$m}, t={$t}, pm={$pm}, t1={$t1}, t2={$t2}");
        $this->ctx->ary = class_exists($pm) ? (new $pm($this->ctx))->$m() : [];
        //Util::elog('this->ctx->ary=' . var_export($this->ctx->ary, true));
        $theme1 = class_exists($t1) ? new $t1($this->ctx) : null;
        $theme2 = class_exists($t2) ? new $t2($this->ctx) : null;

        $render = fn(?object $theme, string $method) => ($theme && is_callable([$theme, $method]))
            ? $theme->$method() : null;

        $this->ctx->out['main'] = $render($theme1, $m)
            ?? $render($theme2, $m) ?? $this->ctx->out['main'];

        foreach ($this->ctx->out as $k => &$v)
            $v = $render($theme1, $k) ?? $render($theme2, $k) ?? $v;

        $this->ctx->buf = $render($theme1, 'html') ?? $render($theme2, 'html') ?? '';
        //Util::elog('this->ctx->buf=' . var_export($this->ctx->buf, true));
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        return $this->ctx->buf;
    }
}

// Base Plugin class with CRUDL methods
abstract class Plugin
{
    public function __construct(
        protected Ctx $ctx
    )
    {
        Util::elog(__METHOD__);
    }

    public function create(): array
    {
        Util::elog(__METHOD__);

        return [
            'head' => 'Create',
            'main' => "Plugin::create() not implemented yet!",
            'foot' => __METHOD__
        ];
    }

    public function read(): array
    {
        Util::elog(__METHOD__);

        return [
            'head' => 'Read',
            'main' => "Plugin::read() not implemented yet!",
            'foot' => __METHOD__
        ];
    }

    public function update(): array
    {
        Util::elog(__METHOD__);

        return [
            'head' => 'Update',
            'main' => "Plugin::update() not implemented yet!",
            'foot' => __METHOD__
        ];
    }

    public function delete(): array
    {
        Util::elog(__METHOD__);

        return [
            'head' => 'Delete',
            'main' => "Plugin::delete() not implemented yet!",
            'foot' => __METHOD__
        ];
    }

    public function list(): array
    {
        Util::elog(__METHOD__);

        return [
            'head' => 'List',
            'main' => "Plugin::list() not implemented yet!",
            'foot' => __METHOD__
        ];
    }
}

class Simple
{
    public function __construct(private Ctx $ctx)
    {
        Util::elog(__METHOD__);
    }

    // Plugin Actions Views

    public function create(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function read(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function update(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function delete(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function list(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    // HTML Partial Views

    public function html(): string
    {
        Util::elog(__METHOD__);

        extract($this->ctx->out, EXTR_SKIP);

        return '<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>' . $doc . '</title>' . $css . '
    </head>
    <body>
        <a href="?o=Home">Home</a>' . $head . $log . $main . $foot . $js . '
    </body>
</html>
';
    }

    public function doc(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function css(): string
    {
        Util::elog(__METHOD__);

        return '
        <style>
            body {
                text-align: center;
                width: 60rem;
                margin-left: auto;
                margin-right: auto;
            }
            
            nav, header, main, footer, pre, div {
                border: dashed 1px red;
                margin: 1rem;
                padding: 1rem;
            }
            
            @media screen and (max-width: 768px) {
                body {
                    width: 100%;
                    margin: 0;
                }
                
                nav, header, main, footer, pre, div {
                    width: auto;
                    margin: 1rem;
                }
            }
        </style>';
    }

    public function log(): string
    {
        Util::elog(__METHOD__);

        return '

        <div>
            ' . basename(__FILE__) . '::' . __METHOD__ . ' (Alerts area)
        </div>';
    }

    public function nav1(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function nav2(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function nav3(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function head(): string
    {
        Util::elog(__METHOD__);

        return '
        <header>
            ' . __METHOD__ . '
            <nav>
                ' . $this->nav1() . '<br>
                ' . $this->nav2() . '<br>
                ' . $this->nav3() . '
            </nav>
        </header>';
    }

    public function main(): string
    {
        Util::elog(__METHOD__);

        return '

        <main>
            <h1>' . $this->ctx->ary['head'] . '</h1>
            <p>' . $this->ctx->ary['main'] . '</p>
            <p>' . $this->ctx->ary['foot'] . '</p>
        </main>';
    }

    public function foot(): string
    {
        Util::elog(__METHOD__);

        return '

        <footer>
            ' . __METHOD__ . '
        </footer>';
    }

    public function js(): string
    {
        Util::elog(__METHOD__);

        return '

        <script>
            document.write("<div>' . addslashes(__METHOD__) . '</div>")
        </script>';
    }
}

final class HomeModel extends Plugin
{
    public function read(): array
    {
        Util::elog(__METHOD__);

        return [
            'head' => 'Home Page',
            'main' => '
                <p class="lead mb-4 text-center">
This is an ultra simple single-file PHP8 plus Bootstrap 5 framework implementing
the <strong>Method Template</strong> design pattern...
                </p>
                <div class="card mt-4 mb-4 bg-body-secondary">
                    <div class="card-body px-4">
                        <p>
The Method Template Pattern in PHP provides a framework for defining a
rendering system while allowing specific steps to be deferred to 
subclasses. At its core, it establishes a base template method that controls the 
overall structure and flow of content generation, while individual methods 
handle specific rendering tasks. This approach enables a clean separation 
between the structural aspects of content generation and the actual 
implementation details.
                        </p>
                        <p>
What makes this pattern particularly powerful is its return-based nature, where 
each method returns content rather than directly outputting it. This fundamental 
characteristic allows rendered content to be collected, transformed, and 
manipulated before final output. Methods can be called from anywhere in the 
codebase without concern for output ordering, and the resulting content can be 
buffered, cached, or modified as needed. This flexibility, combined with PHP 
8.4\'s enhanced type system, creates a robust and maintainable approach to 
content rendering that naturally supports component-based architecture while 
enabling sophisticated content transformation pipelines.
                        </p>
                    </div>
                </div>
                <div class="container my-4">
                    <div class="d-flex flex-column flex-md-row gap-4 justify-content-center">
                        <button class="btn btn-primary d-flex align-items-center justify-content-center gap-2 w-100 w-md-auto">
                            <i class="bi bi-github"></i>
                            SPE Project Page
                        </button>
                        <button class="btn btn-primary d-flex align-items-center justify-content-center gap-2 w-100 w-md-auto">
                            <i class="bi bi-git"></i>
                            SPE Issue Tracker
                        </button>
                    </div>
                    <form method="post">
                        <div class="d-flex flex-column flex-sm-row gap-4 justify-content-center my-4">
                            <button type="button" class="btn btn-success flex-fill" onclick="showToast(\'Everything is working great!\', \'success\');">Success Message</button>
                            <button type="button" class="btn btn-danger flex-fill" onclick="showToast(\'Something went wrong!\', \'danger\');">Danger Message</button>
                        </div>
                    </form>
                    <pre id="dbg" class="text-start overflow-auto"></pre>
                </div>',
            'foot' => __METHOD__ . ' (action)<br>Using the ' . $this->ctx->in['t'] . ' theme'
        ];
    }
}

final class HomeView
{
    public function __construct(
        private Ctx $ctx
    )
    {
        Util::elog(__METHOD__);
    }

    public function read(): string
    {
        Util::elog(__METHOD__);

        return '
    <div class="px-4 py-5 rounded-3 border bg-body-tertiary">
        <div class="row d-flex justify-content-center">
            <h1 class="display-5 fw-bold text-center"><i class="bi bi-gear"></i> ' . $this->ctx->ary['head'] . '</h1>
            <div class="col-lg-8 col-md-10 col-sm-12">' . $this->ctx->ary['main'] . '
                <footer class="mb-4 text-center">' . $this->ctx->ary['foot'] . '</footer>
            </div>
        </div>
    </div>';
    }
}

final class AboutModel extends Plugin
{
    public function read(): array
    {
        Util::elog(__METHOD__);

        return [
            'head' => 'About Page',
            'main' => '
                    <p class="lead mb-4">
This is an experimental PHP8 framework intended to provide a minimal, yet
functional, structure for exploring framework design principles and the new
features of PHP8.  The aim is to create a learn-by-doing environment for
developers interested in understanding how frameworks are built and how
they can benefit from features like union types, match expressions, and
constructor property promotion. Key components include a simple routing
mechanism, a basic dependency injection system, and an event dispatcher.
                    </p>
                        <div class="card mt-4 mb-4 bg-body-secondary">
                            <div class="card-body px-4">
                    <p class="text-center fw-semi-bold fst-italic">
The code is available on <a href="https://github.com/markc/spe">GitHub</a>,
and contributions are most welcome. Feel free to contact me at
<a href="mailto:' . $this->ctx->email . '">' . $this->ctx->email . '</a> or via the
Issue Tracker below with any questions or suggestions.
                    </p>
                    </div>
                    </div>
                    <div class="container my-4">
                        <div class="d-flex flex-column flex-md-row gap-4 justify-content-center">
                            <button class="btn btn-primary d-flex align-items-center justify-content-center gap-2 w-100 w-md-auto">
                                <i class="bi bi-github"></i>
                                SPE Project Page
                            </button>
                            <button class="btn btn-primary d-flex align-items-center justify-content-center gap-2 w-100 w-md-auto">
                                <i class="bi bi-git"></i>
                                SPE Issue Tracker
                            </button>
                        </div>
                    </div>',
            'foot' => __METHOD__ . ' (action)<br>Using the ' . $this->ctx->in['t'] . ' theme'
        ];
    }
}

final class AboutView
{
    public function __construct(
        private Ctx $ctx
    )
    {
        Util::elog(__METHOD__);
    }

    public function read(): string
    {
        Util::elog(__METHOD__);

        return '
    <div class="px-4 py-5 rounded-3 border bg-body-tertiary">
        <div class="row d-flex justify-content-center">
            <h1 class="display-5 fw-bold text-center"><i class="bi bi-gear"></i> ' . $this->ctx->ary['head'] . '</h1>
            <div class="col-lg-8 col-md-10 col-sm-12">' . $this->ctx->ary['main'] . '
                <footer class="mb-4 text-center">' . $this->ctx->ary['foot'] . '</footer>
            </div>
        </div>
    </div>';
    }
}

final class ContactModel extends Plugin
{
    public function read(): array
    {
        Util::elog(__METHOD__);

        return [
            'head' => 'Contact Page',
            'main' => '
                    <p class="lead mb-4">
This is an ultra simple single-file PHP8 plus Bootstrap 5 framework and
template system example. Comments and pull requests are most welcome via the
Issue Tracker link.
                    </p>
                    <div class="card mt-4 mb-4 bg-body-secondary">
                        <div class="card-body px-4">
                            <form method="post" onsubmit="return mailform(this);">
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control form-control-lg" id="subject" required>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control form-control-lg" id="message" rows="4" required></textarea>
                                </div>
                                <div class="mb-3 text-end">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="container my-4">
                        <div class="d-flex flex-column flex-md-row gap-4 justify-content-center">
                            <button class="btn btn-primary d-flex align-items-center justify-content-center gap-2 w-100 w-md-auto">
                                <i class="bi bi-github"></i>
                                SPE Project Page
                            </button>
                            <button class="btn btn-primary d-flex align-items-center justify-content-center gap-2 w-100 w-md-auto">
                                <i class="bi bi-git"></i>
                                SPE Issue Tracker
                            </button>
                        </div>
                    </div>',
            'foot' => __METHOD__ . ' (action)<br>Using the ' . $this->ctx->in['t'] . ' theme'
        ];
    }
}
final class ContactView
{
    public function __construct(
        private Ctx $ctx
    )
    {
        Util::elog(__METHOD__);
    }

    public function read(): string
    {
        Util::elog(__METHOD__);

        return '
    <div class="px-4 py-5 rounded-3 border bg-body-tertiary">
        <div class="row d-flex justify-content-center">
            <h1 class="display-5 fw-bold text-center"><i class="bi bi-gear"></i> ' . $this->ctx->ary['head'] . '</h1>
            <div class="col-lg-8 col-md-10 col-sm-12">' . $this->ctx->ary['main'] . '
                <footer class="mb-4 text-center">' . $this->ctx->ary['foot'] . '</footer>
            </div>
        </div>
    </div>';
    }
}

class Theme
{
    public function __construct(private Ctx $ctx)
    {
        Util::elog(__METHOD__);
    }

    public function __call($m, $a): string
    {
        Util::elog(__METHOD__ . " method: " . $m . ", class: " . get_class($this));

        return __METHOD__ . ' m=' . $m;
    }
}

class TopNav extends Theme
{
    public function __construct(
        private Ctx $ctx
    )
    {
        Util::elog(__METHOD__);
        //parent::__construct($ctx);
    }

    public function html(): string
    {
        Util::elog(__METHOD__);

        extract($this->ctx->out, EXTR_SKIP);

        return '<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="dark light">
        <meta name="description" content="Simple PHP Example">
        <meta name="author" content="Mark Constable">
        <link rel="icon" href="favicon.ico">
        <title>' . $doc . '</title>' . $css . '
    </head>
    <body class="d-flex flex-column min-vh-100">' . $head . $main . $foot . $js . '
    </body>
</html>
';
    }

    //<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/fonts/bootstrap-icons.woff2" as="font" type="font/woff2" crossorigin>
    public function css(): string
    {
        Util::elog(__METHOD__);

        return '
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
            <script>
            function setTheme(theme) {
                const htmlElement = document.documentElement;
                htmlElement.setAttribute("data-bs-theme", theme);
                localStorage.setItem("theme", theme);
                updateThemeIcon(theme);
            }
            function toggleTheme() {
                const currentTheme = document.documentElement.getAttribute("data-bs-theme");
                setTheme(currentTheme === "dark" ? "light" : "dark");
            }
            function updateThemeIcon(theme) {
                const icon = document.getElementById("theme-icon");
                if (icon) {
                    icon.className = theme === "dark" ? "bi bi-moon-fill" : "bi bi-sun-fill";
                }
            }
            const storedTheme = localStorage.getItem("theme");
            if (storedTheme) {
                setTheme(storedTheme);
            } else {
                const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
                setTheme(prefersDark ? "dark" : "light");
            }
        </script>';
    }

    public function js(): string
    {
        Util::elog(__METHOD__);

        return '
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const toastElList = document.querySelectorAll(".toast");
                    toastElList.forEach(function(toastEl) {
                        const toast = new bootstrap.Toast(toastEl, {
                            autohide: true,
                            delay: 3000
                        });
                        toast.show();
                    });
                });
                function showToast(message, type) {
                    const toastContainer = document.createElement("div");
                    toastContainer.setAttribute("aria-live", "polite");
                    toastContainer.setAttribute("aria-atomic", "true");
                    toastContainer.style.position = "fixed";
                    toastContainer.style.top = "20px";
                    toastContainer.style.right = "20px";
                    toastContainer.style.zIndex = "1050";

                    toastContainer.innerHTML = 
                        \'<div class="toast align-items-center text-white bg-\' + type + \' border-0" role="alert" aria-live="assertive" aria-atomic="true">\' +
                            \'<div class="d-flex">\' +
                                \'<div class="toast-body">\' + message + \'</div>\' +
                                \'<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>\' +
                            \'</div>\' +
                        \'</div>\';

                    document.body.appendChild(toastContainer);
                    const toastElement = toastContainer.querySelector(".toast");
                    const toast = new bootstrap.Toast(toastElement, {
                        autohide: true,
                        delay: 3000
                    });
                    toast.show();

                    toastElement.addEventListener("hidden.bs.toast", () => {
                        toastContainer.remove();
                    });
                }    
            </script>';
    }

    public function log(): string
    {
        Util::elog(__METHOD__);
        //Util::elog(__METHOD__ . ' ' . var_export($this->ctx, true));

        if ($this->ctx->in['l'])
        {
            [$lvl, $msg] = explode(':', $this->ctx->in['l']);
            $bgClass = $lvl === 'success' ? 'bg-success' : 'bg-danger';
            return '
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1500">
            <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header ' . $bgClass . ' text-white">
                    <strong class="me-auto">Notification</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">' . $msg . '</div>
            </div>
        </div>';
        }
        return '';
    }

    public function head(): string
    {
        Util::elog(__METHOD__);

        $nav = new NavRenderer();
        return '
        <nav class="navbar navbar-expand-md bg-body-secondary fixed-top border-bottom shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="/">« ' . $this->ctx->out['head'] . '</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="toggleTheme(); return false;">
                                <i id="theme-icon" class="bi bi-sun-fill"></i>
                            </a>
                        </li>'
            . $nav->navRender($this->ctx->nav1)
            . $nav->navRender($this->ctx->nav2) . '
                    </ul>
                </div>
            </div>
        </nav>';
    }

    public function main(): string
    {
        Util::elog(__METHOD__);

        return '

        <main class="container py-5 mt-5">' . $this->ctx->out['main'] . '
        </main>';
    }

    public function foot(): string
    {
        Util::elog(__METHOD__);

        return '

        <footer class="container-fluid text-center py-3 mt-auto bg-body-secondary border-top shadow-sm">
            <div class="container">
                <p class="text-muted mb-0"><small>' . $this->ctx->out['foot'] . '</small></p>
            </div>
        </footer>';
    }
}

class SideBar extends Theme
{
    public function __construct(private Ctx $ctx)
    {
        Util::elog(__METHOD__);
    }

    public function foot(): string
    {
        Util::elog(__METHOD__);

        return '

        <footer class="bg-light text-center py-3 mt-auto">
            <div class="container">
                <p class="text-muted mb-0"><small>[SideBar] ' . $this->ctx->out['foot'] . '</small></p>
            </div>
        </footer>';
    }
}

class NavRenderer
{
    public function navRender(array $nav): string
    {
        return match (true)
        {
            isset($nav[0][0]) && is_array($nav[0]) => implode('', array_map(fn($item) => $this->renderNavItem($item), $nav)),
            is_array($nav[1] ?? null) => $this->renderDropdown($nav),
            default => $this->renderNavItem($nav),
        };
    }

    private function renderDropdown(array $nav): string
    {
        $nid = 'nav' . md5(serialize($nav));
        $icon = $nav[2] ?? '';

        return '
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="' . $nid . 'Dropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="' . $icon . '"></i> ' . $nav[0] . '
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="' . $nid . 'Dropdown">' . $this->renderDropdownItems($nav[1]) . '
                            </ul>
                        </li>';
    }

    private function renderDropdownItems(array $items): string
    {
        return implode('', array_map(fn($item) => '
                                <li>
                                    <a class="dropdown-item" href="' . $item[1] . '">
                                        <i class="' . $item[2] . '"></i> ' . $item[0] . '
                                    </a>
                                </li>', $items));
    }

    private function renderNavItem(array $nav): string
    {
        return '
                                <li class="nav-item">
                                    <a class="nav-link" href="' . $nav[1] . '">
                                        <i class="' . $nav[2] . '"></i> ' . $nav[0] . '
                                    </a>
                                </li>';
    }
}

final class Util
{
    public static function elog(string $msg): void
    {
        if (defined('DBG') && DBG)
        {
            error_log($msg);
        }
    }

    public static function esc(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

// Bootstrap the application
echo new Init(new Ctx());
