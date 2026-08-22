<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

final readonly class Init
{
    private const string PLUGINS = 'SPE\\Blog\\Plugins\\';

    private array $out;

    public function __construct(private Ctx $ctx)
    {
        [$o, $m] = [$ctx->in['o'], $ctx->in['m']];
        $model = self::PLUGINS . "$o\\{$o}Model";

        if (!is_subclass_of($model, Plugin::class)) {
            http_response_code(404);
            [$data, $main] = $this->error('Not found', 'There is no such plugin.');
        } elseif (!$ctx->role()->can($model::guard($m))) {
            // Anonymous visitors are sent to log in; a signed-in user who simply
            // lacks the role gets an honest 403, not a pointless login redirect.
            if (!$ctx->user) {
                $ctx->flash(Flash::Warning, 'Please sign in to continue.');
                header('Location: ?o=Auth&m=create');
                exit;
            }
            http_response_code(403);
            [$data, $main] = $this->error('Forbidden', 'Your account does not have access to that.');
        } else {
            $data = new $model($ctx)->$m();
            $view = self::PLUGINS . "$o\\{$o}View";
            $view = is_a($view, View::class, true) ? $view : View::class;
            $main = new $view($ctx, $data)->$m();
        }

        $this->out = [...$ctx->out, ...$data, 'main' => $main];
    }

    /** @return array{0: array{title:string,body:string}, 1: string} */
    private function error(string $title, string $body): array
    {
        $data = ['title' => $title, 'body' => $body];
        return [$data, new View($this->ctx, $data)->list()];
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
