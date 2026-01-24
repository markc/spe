<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Core;

final readonly class Init
{
    private const string NS = 'SPE\\Session\\';
    private array $out;

    public function __construct(private Ctx $ctx)
    {
        [$o, $m] = [$ctx->in['o'], $ctx->in['m']];
        $model = self::NS . "Plugins\\{$o}\\{$o}Model";
        $ary = class_exists($model) ? new $model($ctx)->$m() : [];
        $view = self::NS . "Plugins\\{$o}\\{$o}View";
        $main = class_exists($view) ? new $view($ctx, $ary)->$m() : new View($ctx, $ary)->$m();
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
