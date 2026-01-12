<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Home;

use SPE\Blog\Core\Plugin;

final class HomeModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return [
            'head' => 'Home Page',
            'main' => <<<HTML
            <p>This is an ultra simple single-file PHP8 plus custom CSS framework implementing the <strong>Method Template</strong> design pattern.</p>
            <div class="card mt-2">
                <p>The Method Template Pattern in PHP provides a framework for defining a rendering system while allowing specific steps to be deferred to subclasses. At its core, it establishes a base template method that controls the overall structure and flow of content generation, while individual methods handle specific rendering tasks.</p>
                <p>What makes this pattern particularly powerful is its return-based nature, where each method returns content rather than directly outputting it. This allows rendered content to be collected, transformed, and manipulated before final output.</p>
            </div>
            <div class="flex mt-2" style="gap:1rem;justify-content:center">
                <button class="btn" onclick="showToast('Everything is working great!', 'success');">Success Message</button>
                <button class="btn btn-danger" onclick="showToast('Something went wrong!', 'danger');">Danger Message</button>
            </div>
            HTML,
            'foot' => __METHOD__ . ' (action)<br>Using the ' . $this->ctx->in['t'] . ' theme',
        ];
    }
}
