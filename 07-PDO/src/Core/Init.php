<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Core;

final readonly class Init {
    private const string NS = 'SPE\\PDO\\';

    public function __construct(private Ctx $ctx) {
        foreach ($this->ctx->in as $k => $v)
            $this->ctx->in[$k] = ($_REQUEST[$k] ?? $v) |> trim(...) |> htmlspecialchars(...);

        ['o' => $o, 'm' => $m, 't' => $t] = $this->ctx->in;

        $model = self::NS . "Plugins\\{$o}\\{$o}Model";
        $this->ctx->ary = class_exists($model) ? (new $model($this->ctx))->$m() : [];

        $view = self::NS . "Plugins\\{$o}\\{$o}View";
        $theme = self::NS . "Themes\\{$t}";
        $render = fn(?object $obj, string $method) =>
            ($obj && method_exists($obj, $method)) ? $obj->$method() : null;

        $v1 = class_exists($view) ? new $view($this->ctx) : null;
        $v2 = class_exists($theme) ? new $theme($this->ctx) : null;

        $this->ctx->out['main'] = $render($v1, $m) ?? $render($v2, $m) ?? $this->ctx->out['main'];
        $this->ctx->buf = $render($v1, 'html') ?? $render($v2, 'html') ?? '';
    }

    public function __toString(): string {
        return match ($this->ctx->in['x']) {
            'json' => (header('Content-Type: application/json') ?: '') . json_encode($this->ctx->out),
            default => $this->ctx->buf
        };
    }
}
