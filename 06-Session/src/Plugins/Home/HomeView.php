<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\Home;

use SPE\Session\Core\View;

final readonly class HomeView extends View {
    #[\Override] public function list(): string {
        ['head' => $h, 'main' => $m, 'time_ago' => $t, 'visit_count' => $v] = $this->ctx->ary;
        return <<<HTML
        <div class="card"><h2>$h</h2><p>$m</p>
            <p><strong>First visit:</strong> $t</p>
            <p><strong>Page views this session:</strong> $v</p>
            <p class="text-center mt-2"><a class="btn" href="?o=Home&m=reset">🔄 Reset Session</a></p>
        </div>
        HTML;
    }

    public function reset(): string { return $this->list(); }
}
