<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Session\Plugins\Home;

use SPE\Session\Core\{Plugin, Util};

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
