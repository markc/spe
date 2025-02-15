<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250215
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

define('DBG', true);

class Ctx
{
    public function __construct(
        public string $email = 'markc@renta.net',
        public array $in = [
            'l' => '',      // Log (message)
            'm' => 'read',  // Method (action)
            'o' => 'home',  // Object (plugin)
            'x' => '',      // XHR (request)
        ],
        public array $out = [
            'doc'   => 'SPE::03 Plugins',
            'css'   => '',
            'log'   => '',
            'nav1'  => '',
            'head'  => 'Plugins PHP Example',
            'main'  => 'Error: missing plugin!',
            'foot'  => 'Copyright (C) 2015-2025 Mark Constable (AGPL-3.0)',
            'js'    => '',
        ],
        public array $nav1 = [
            ['Home', 'home', 'bi bi-house-door'],
            ['About', 'about', 'bi bi-question-octagon'],
            ['Contact', 'contact', 'bi bi-person-rolodex']
        ]
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

        // Handle plugin execution (o=plugin object/class, m=action method)
        $object = $this->ctx->in['o'];
        $method = $this->ctx->in['m'];
        $this->ctx->out['main'] = match (true)
        {
            !class_exists($object) => "Error: no plugin object!",
            !method_exists($object, $method) => "Error: no plugin method!",
            default => (new $object($this->ctx))->$method()
        };

        // Process output components
        foreach ($this->ctx->out as $k => $v)
        {
            if (method_exists($this, $k))
            {
                $this->ctx->out[$k] = $this->$k();
            }
        }
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        if ($this->ctx->in['x'])
        {
            $xhr = $this->ctx->out[$this->ctx->in['x']] ?? '';
            if ($xhr) return $xhr;
            header('Content-Type: application/json');
            return json_encode($this->ctx->out, JSON_PRETTY_PRINT);
        }
        return $this->html();
    }

    private function html(): string
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

    private function css(): string
    {
        Util::elog(__METHOD__);

        return '
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/fonts/bootstrap-icons.woff2" as="font" type="font/woff2" crossorigin>
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

    private function js(): string
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

    private function log(): string
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

    private function nav1(): string
    {
        Util::elog(__METHOD__);

        $o = '?o=' . $this->ctx->in['o'];
        return join('', array_map(function ($n) use ($o)
        {
            $url = is_string($n[1]) ? "?o=$n[1]" : '';
            $c = $o === $url ? ' active' : '';
            $icon = isset($n[2]) ? '<i class="' . $n[2] . ' me-1"></i>' : '';
            return '
                        <li class="nav-item">
                            <a class="nav-link' . $c . '" href="' . $url . '"' . ($c ? ' aria-current="page"' : '') . '>' . $icon . $n[0] . '</a>
                        </li>';
        }, $this->ctx->nav1));
    }

    private function head(): string
    {
        Util::elog(__METHOD__);

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
                        </li>' . $this->ctx->out['nav1'] . '
                    </ul>
                </div>
            </div>
        </nav>';
    }

    private function main(): string
    {
        Util::elog(__METHOD__);

        return '

        <main class="container py-5 mt-5">' . $this->ctx->out['main'] . '
        </main>';
    }

    private function foot(): string
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

abstract class Plugin
{
    protected string $buf = '';

    public function __construct(
        protected readonly Ctx $ctx
    )
    {
        Util::elog(__METHOD__);
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        return $this->buf;
    }

    // abstract public function create(): string;
    // abstract public function read(): string;
    // abstract public function update(): string;
    // abstract public function delete(): string;
    // abstract public function list(): string;

    public function create(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__ . " not implemented yet!";
    }


    public function read(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__ . " not implemented yet!";
    }

    public function update(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__ . " not implemented yet!";
    }

    public function delete(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__ . " not implemented yet!";
    }

    public function list(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__ . " not implemented yet!";
    }
}

final class Home extends Plugin
{
    public function read(): string
    {
        Util::elog(__METHOD__);

        return '
    <div class="px-4 py-5 rounded-3 border bg-body-tertiary">
        <div class="row d-flex justify-content-center">
            <div class="col-lg-8 col-md-10 col-sm-12">
                <h1 class="display-5 fw-bold text-center"><i class="bi bi-gear"></i> Home Page</h1>
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
                </div>
                <footer class="mb-4 text-center">' . __METHOD__ . '</footer>
            </div>
        </div>
    </div>';
    }


    public function read2(): string
    {
        Util::elog(__METHOD__);

        return '
            <div class="px-4 py-5 bg-light rounded-3 border">
                <div class="row d-flex justify-content-center">
                <div class="col-lg-8 col-md-10 col-sm-12">
                    <h1 class="display-5 fw-bold text-center">' . $this->ctx->out['head'] . '</h1>
                    <p class="lead mb-4">
This is an example of a simple PHP8.4 "framework" to provide the core
structure for further experimental development with both the framework
design and some of the new features of PHP8.4.
                    </p>
                    <form method="post">
                        <div class="d-flex flex-column flex-sm-row gap-2 mb-4">
                            <button type="button" class="btn btn-success flex-fill" onclick="showToast(\'Everything is working great!\', \'success\');">Success Message</button>
                            <button type="button" class="btn btn-danger flex-fill" onclick="showToast(\'Something went wrong!\', \'danger\');">Danger Message</button>
                        </div>
                    </form>
                    <pre id="dbg" class="text-start overflow-auto"></pre>
                </div>
                </div>
            </div>';
    }
}

final class About extends Plugin
{
    public function read(): string
    {
        Util::elog(__METHOD__);

        return '
        <div class="px-4 py-5 rounded-3 border bg-body-tertiary">
            <div class="row d-flex justify-content-center">
                <div class="col-lg-8 col-md-10 col-sm-12">
                    <h1 class="display-5 fw-bold text-center"><i class="bi bi-gear"></i> About Page</h1>
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
                    </div>
                    <footer class="mb-4 text-center">' . __METHOD__ . '</footer>
                </div>
            </div>
        </div>';
    }
}

final class Contact extends Plugin
{
    public function read(): string
    {
        Util::elog(__METHOD__);

        return '
        <div class="px-4 py-5 rounded-3 border bg-body-tertiary">
            <div class="row d-flex justify-content-center">
                <div class="col-lg-8 col-md-10 col-sm-12">
                    <h1 class="display-5 fw-bold text-center"><i class="bi bi-gear"></i> Contact Page</h1>
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
                    </div>
                    <footer class="mb-4 text-center">' . __METHOD__ . '</footer>
                </div>
            </div>
        </div>
        <script>
function mailform(form) {
location.href = "mailto:' . $this->ctx->email . '"
    + "?subject=" + encodeURIComponent(form.subject.value)
    + "&body=" + encodeURIComponent(form.message.value);
form.subject.value = "";
form.message.value = "";
alert("Thank you for your message. We will get back to you as soon as possible.");
return false;
}
        </script>';
    }
}

final class Util
{
    public static function elog(string $content): void
    {
        if (defined('DBG') && DBG)
        {
            error_log($content);
        }
    }
}

// Bootstrap the application
echo new Init(new Ctx());
