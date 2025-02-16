<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Autoload\Plugins\About;

use SPE\Autoload\Core\{Plugin, Util};

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
