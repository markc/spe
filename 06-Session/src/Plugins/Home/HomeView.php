<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\Home;

use SPE\Session\Core\View;

final class HomeView extends View {
    #[\Override] public function list(): string {
        ['head' => $h, 'main' => $m, 'time_ago' => $t, 'visit_count' => $v, 'session_id' => $sid] = $this->ary;
        return <<<HTML
        <div class="card">
            <h2>{$h}</h2>
            <p>{$m}</p>
            <div class="mt-2">
                <p><strong>First visit:</strong> {$t}</p>
                <p><strong>Page views:</strong> {$v}</p>
                <p><strong>Session ID:</strong> <code>{$sid}</code></p>
            </div>
            <p class="text-muted mt-2">Navigate to other pages and back - your visit count increases. Change themes - your selection persists. Close the browser and return - session data remains.</p>
            <div class="flex justify-center mt-2">
                <a class="btn btn-danger" href="?m=reset">🔄 Reset Session</a>
            </div>
        </div>
        HTML;
    }

    public function reset(): string {
        return $this->list();
    }
}
