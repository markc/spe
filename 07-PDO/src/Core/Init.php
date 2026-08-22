<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Core;

final readonly class Init
{
    private const string PLUGINS = 'SPE\\PDO\\Plugins\\';

    private array $out;

    public function __construct(private Ctx $ctx)
    {
        [$o, $m] = [$ctx->in['o'], $ctx->in['m']];
        [$model, $view] = [self::PLUGINS . "$o\\{$o}Model", self::PLUGINS . "$o\\{$o}View"];
        if (is_subclass_of($model, Plugin::class)) {
            $data = new $model($ctx)->$m();
        } else {
            http_response_code(404);
            $data = ['title' => 'Not found', 'body' => 'There is no such plugin.'];
        }
        $view = is_a($view, View::class, true) ? $view : View::class;
        $this->out = [...$ctx->out, ...$data, 'main' => new $view($ctx, $data)->$m()];
    }

    public function __toString(): string
    {
        if ($this->ctx->in['x'] === 'json') {
            header('Content-Type: application/json');
            return json_encode($this->out, JSON_THROW_ON_ERROR);
        }
        return new Theme($this->ctx, $this->out)->render();
    }
}
