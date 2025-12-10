<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\Home;

use SPE\Session\Core\Ctx;

final class HomeView {
    public function __construct(private Ctx $ctx) {}

    public function list(): string {
        $a = $this->ctx->ary;
        return <<<HTML
        <div class="card">
            <h2>{$a['head']}</h2>
            <p>{$a['main']}</p>
            <p><strong>First visit:</strong> {$a['time_ago']}</p>
            <p><strong>Page views this session:</strong> {$a['visit_count']}</p>
            <p class="text-center mt-2">
                <a class="btn" href="?o=Home&m=reset">🔄 Reset Session</a>
            </p>
        </div>
        HTML;
    }

    public function reset(): string {
        return $this->list();
    }
}
