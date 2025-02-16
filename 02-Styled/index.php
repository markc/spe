<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250214
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

echo new class {

    private $email = 'markc@renta.net';

    private $in = [
        'm' => 'home',
    ];

    private $out = [
        'doc'   => 'SPE::02 Styled',
        'css'   => '',
        'nav'   => '',
        'head'  => '« Styled PHP Example',
        'main'  => 'Error: missing page!',
        'foot'  => 'Copyright © 2025 Mark Constable (AGPL-3.0)',
        'js'    => ''
    ];

    private $nav = [
        ['Home', 'home', 'bi bi-house-door'],
        ['About', 'about', 'bi bi-question-octagon'],
        ['Contact', 'contact', 'bi bi-person-rolodex']
    ];

    public function __construct()
    {
        foreach ($this->in as $k => $v)
        {
            $this->in[$k] = filter_var(
                trim($_REQUEST[$k] ?? $v, '/'),
                FILTER_SANITIZE_URL
            ) ?: $v;
        }

        if (method_exists($this, $this->in['m']))
        {
            $this->out['main'] = $this->{$this->in['m']}();
        }

        foreach ($this->out as $k => $v)
        {
            $this->out[$k] = method_exists($this, $k) ? $this->{$k}() : $v;
        }
    }

    public function __toString(): string
    {
        return $this->html();
    }

    private function html(): string
    {
        extract($this->out, EXTR_SKIP);

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

    public function nav(): string
    {
        $o = '?m=' . $this->in['m'];

        return join('', array_map(function ($n) use ($o)
        {
            $url = is_string($n[1]) ? "?m=$n[1]" : '';
            $c = $o === $url ? ' active' : '';
            $icon = isset($n[2]) ? '<i class="' . $n[2] . ' me-1"></i>' : '';
            return '
                        <li class="nav-item">
                            <a class="nav-link' . $c . '" href="' . $url . '"' . ($c ? ' aria-current="page"' : '') . '>' . $icon . $n[0] . '</a>
                        </li>';
        }, $this->nav));
    }

    private function head(): string
    {
        return '
        <nav class="navbar navbar-expand-md bg-body-secondary fixed-top border-bottom shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="/">' . $this->out['head'] . '</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="toggleTheme(); return false;">
                                <i id="theme-icon" class="bi bi-sun-fill"></i>
                            </a>
                        </li>' . $this->out['nav'] . '
                    </ul>
                </div>
            </div>
        </nav>';
    }

    private function main(): string
    {
        return '

        <main class="container py-5 mt-5">' . $this->out['main'] . '
        </main>';
    }

    private function foot(): string
    {
        return '

        <footer class="container-fluid text-center py-3 mt-auto bg-body-secondary border-top shadow-sm">
            <div class="container">
                <p class="text-muted mb-0"><small>' . $this->out['foot'] . '</small></p>
            </div>
        </footer>';
    }

    private function js(): string
    {
        return '
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>';
    }

    private function home(): string
    {
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
                </div>
                <footer class="mb-4 text-center">' . __METHOD__ . '</footer>
            </div>
        </div>
    </div>';
    }

    private function about(): string
    {
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
<a href="mailto:' . $this->email . '">' . $this->email . '</a> or via the
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

    private function contact(): string
    {
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
    location.href = "mailto:' . $this->email . '"
        + "?subject=" + encodeURIComponent(form.subject.value)
        + "&body=" + encodeURIComponent(form.message.value);
    form.subject.value = "";
    form.message.value = "";
    alert("Thank you for your message. We will get back to you as soon as possible.");
    return false;
}
            </script>';
    }
};
