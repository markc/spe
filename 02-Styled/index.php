<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250206
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

echo new class {

    private $email = 'markc@renta.net';

    private $in = [
        'm' => 'home',
    ];

    private $out = [
        'doc'   => 'SPE::02',
        'css'   => '',
        'nav'   => '',
        'head'  => '« Styled PHP Example',
        'main'  => 'Error: missing page!',
        'foot'  => 'Copyright © 2025 Mark Constable (AGPL-3.0)',
        'js'    => ''
    ];

    private $nav = [
        ['Home', 'home'],
        ['About', 'about'],
        ['Contact', 'contact']
    ];

    public function __construct()
    {
        foreach ($this->in as $key => $default)
        {
            $this->in[$key] = filter_var(
                trim($_REQUEST[$key] ?? $default, '/'),
                FILTER_SANITIZE_URL
            ) ?: $default;
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
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Simple PHP Example">
        <meta name="author" content="Mark Constable">
        <link rel="icon" href="favicon.ico">
        <title>' . $doc . '</title>' . $css . '
    </head>
    <body>' . $head . $main . $foot . $js . '
    </body>
</html>
';
    }

    private function css(): string
    {
        return '
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">';
    }

    private function nav(): string
    {
        $a = join('', array_map(function ($n)
        {
            $url = str_starts_with($n[1], 'http') ? $n[1] : "?m=$n[1]";
            $c = $this->in['m'] === $n[1] ? ' active" aria-current="page"' : '"';
            return '
                        <li class="nav-item">
                            <a class="nav-link' . $c . ' href="' . $url . '">' . $n[0] . '</a>
                        </li>';
        }, $this->nav));

        return '
        <nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
            <div class="container">
                <a class="navbar-brand" href="/">' . $this->out['head'] . '</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">' . $a . '
                    </ul>
                </div>
            </div>
        </nav>';
    }

    private function head(): string
    {
        return $this->out['nav'];
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
        <footer class="container-fluid bg-light text-center py-3 mt-auto">
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
            <div class="px-4 py-5 text-center bg-light rounded-3 border">
                <h1 class="display-4 fw-bold">Home Page</h1>
                <div class="col-lg-6 mx-auto">
                    <p class="lead mb-4">
This is an ultra simple single-file PHP8 plus Bootstrap 5 framework
and template system example. Comments and pull requests are most welcome
via the Issue Tracker link.
                    </p>
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                        <a class="btn btn-primary btn-lg px-4 gap-3" href="https://github.com/markc/spe" role="button">Project Page &raquo;</a>
                        <a class="btn btn-primary btn-lg px-4 gap-3" href="https://github.com/markc/spe/issues" role="button">Issue Tracker &raquo;</a>
                    </div>
                </div>
            </div>';
    }

    private function about(): string
    {
        return '
            <div class="px-4 py-5 bg-light rounded-3 border">
                <div class="container-fluid py-3">
                    <h1 class="display-5 fw-bold">About Page</h1>
                    <p class="col-md-8 lead">
This is an example of a simple PHP8 "framework" to provide the core
structure for further experimental development with both the framework
design and some of the new features of PHP8.
                    </p>
                </div>
            </div>';
    }

    private function contact(): string
    {
        return '
            <div class="px-4 py-5 bg-light rounded-3 border">
                <div class="container-fluid py-3">
                    <h1 class="display-5 fw-bold mb-4">Contact Page</h1>
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <form method="post" onsubmit="return mailform(this);">
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control form-control-lg" id="subject" required>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control form-control-lg" id="message" rows="4" required></textarea>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Submit</button>
                                </div>
                            </form>
                        </div>
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
