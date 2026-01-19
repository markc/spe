<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Plugins\Home;

use SPE\Autoload\Core\View;

final class HomeView extends View
{
    #[\Override]
    public function list(): string
    {
        return <<<HTML
        <div class="card">
            <h2>{$this->ary['head']}</h2>
            {$this->ary['main']}
        </div>
        <div class="flex justify-center mt-4">
            <button class="btn-hover btn-success" onclick="showToast('Success!', 'success')">Success</button>
            <button class="btn-hover btn-danger" onclick="showToast('Error!', 'danger')">Danger</button>
        </div>
        HTML;
    }
}
