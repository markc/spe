<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Core;

use SPE\App\QueryType;

final readonly class Init
{
    private const string NS = 'SPE\\PDO\\';
    private array $out;

    public function __construct(private Ctx $ctx)
    {
        [$o, $m] = [$ctx->in['o'], $ctx->in['m']];

        // &edit flag without explicit method implies list
        if (isset($_GET['edit']) && !isset($_REQUEST['m'])) {
            $m = 'list';
        }

        // Blog plugin or page from database
        if ($o === 'Blog') {
            $model = self::NS . "Plugins\\Blog\\BlogModel";
            $ary = class_exists($model) ? new $model($ctx)->$m() : [];
            $view = self::NS . "Plugins\\Blog\\BlogView";
            $main = class_exists($view) ? new $view($ctx, $ary)->$m() : '';
        } else {
            // Load page from database
            $ary = $ctx->db->read('posts', '*', "slug=:s AND type='page'", ['s' => strtolower($o)], QueryType::One)
                ?: [];
            $view = self::NS . "Plugins\\Blog\\BlogView";
            $main = $ary ? new $view($ctx, $ary)->page() : '<div class="card"><p>Page not found.</p></div>';
        }

        $this->out = [...$ctx->out, ...$ary, 'main' => $main];
    }

    public function __toString(): string
    {
        return match ($this->ctx->in['x']) {
            'json' => (header('Content-Type: application/json') ?: '') . json_encode($this->out),
            default => new Theme($this->ctx, $this->out)->render(),
        };
    }
}
