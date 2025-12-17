<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Users\Plugins\About;

use SPE\Users\Core\Plugin;

final class AboutModel extends Plugin {
    #[\Override]
    public function list(): array {
        return [
            'head' => 'About Page',
            'main' => <<<HTML
                <p>This is an experimental PHP8 framework intended to provide a minimal, yet functional, structure for exploring framework design principles and the new features of PHP8. The aim is to create a learn-by-doing environment for developers interested in understanding how frameworks are built.</p>
                <div class="card mt-2">
                    <p class="text-center"><em>The code is available on <a href="https://github.com/markc/spe">GitHub</a>, and contributions are most welcome. Feel free to contact me at <a href="mailto:{$this->ctx->email}">{$this->ctx->email}</a> or via the Issue Tracker with any questions or suggestions.</em></p>
                </div>
                <div class="flex mt-2" style="gap:1rem;justify-content:center">
                    <a href="https://github.com/markc/spe" class="btn">SPE Project Page</a>
                    <a href="https://github.com/markc/spe/issues" class="btn">SPE Issue Tracker</a>
                </div>
                HTML,
            'foot' => __METHOD__ . ' (action)<br>Using the ' . $this->ctx->in['t'] . ' theme'
        ];
    }
}
