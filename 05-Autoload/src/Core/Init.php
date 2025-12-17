<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Core;

final readonly class Init {
    private const string NS = 'SPE\\Autoload\\';

    public function __construct(private Ctx $ctx) {
        ['o' => $o, 'm' => $m, 't' => $t] = $this->ctx->in;

        $model = self::NS . "Plugins\\{$o}\\{$o}Model";
        $this->ctx->ary = class_exists($model) ? new $model($this->ctx)->$m() : [];

        $view = self::NS . "Plugins\\{$o}\\{$o}View";
        $theme = self::NS . "Themes\\{$t}";

        $v = class_exists($view) ? new $view($this->ctx) : null;
        $th = class_exists($theme) ? new $theme($this->ctx) : null;

        $this->ctx->out['main'] = $v?->$m() ?? $th?->$m() ?? $this->ctx->out['main'];
        $this->ctx->buf = $th?->html() ?? '';
    }

    public function __toString(): string {
        return match ($this->ctx->in['x']) {
            'json' => (header('Content-Type: application/json') ?: '') . json_encode($this->ctx->out),
            default => $this->ctx->buf
        };
    }
}
