<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Core;

/**
 * Application initializer for YouTube Manager
 * Uses PHP 8.5 pipe operator and first-class callables
 */
final class Init {
    private const string NS = 'SPE\\YouTube\\';

    public function __construct(private Ctx $ctx) {
        session_status() === PHP_SESSION_NONE && session_start();

        // Parse request input using pipe operator
        $this->ctx->in = $this->parseInput();

        ['o' => $o, 'm' => $m, 't' => $t] = $this->ctx->in;

        // Redirect to Auth if not authenticated (except Auth plugin)
        if ($o !== 'Auth' && empty($_SESSION['authenticated'])) {
            Util::redirect('?o=Auth');
        }

        // Execute Model -> View pipeline
        $model = self::NS . "Plugins\\{$o}\\{$o}Model";
        $this->ctx->ary = class_exists($model)
            ? (new $model($this->ctx))->$m()
            : [];

        // Render View -> Theme pipeline
        $view = self::NS . "Plugins\\{$o}\\{$o}View";
        $theme = self::NS . "Themes\\{$t}";

        // Return null for empty strings to allow fallback via ??
        $render = static fn(?object $obj, string $method): ?string =>
            ($obj && method_exists($obj, $method)) ? ($obj->$method() ?: null) : null;

        $v1 = class_exists($view) ? new $view($this->ctx) : null;
        $v2 = class_exists($theme) ? new $theme($this->ctx) : null;

        // Build output using pipe operator pattern
        $this->ctx->out['main'] = $render($v1, $m)
            ?? $render($v2, $m)
            ?? $this->ctx->out['main'];

        foreach ($this->ctx->out as $k => &$v) {
            $v = $render($v1, $k) ?? $render($v2, $k) ?? $v;
        }

        $this->ctx->buf = $render($v1, 'html') ?? $render($v2, 'html') ?? '';
    }

    /**
     * Parse input parameters with session persistence
     */
    private function parseInput(): array {
        return [
            'id' => $_REQUEST['id'] ?? 0,
            'l' => $_REQUEST['l'] ?? '',
            'm' => $_REQUEST['m'] ?? 'list',
            'o' => Util::ses('o', $this->ctx->in['o']),
            't' => Util::ses('t', $this->ctx->in['t']),
            'x' => $_REQUEST['x'] ?? '',
        ];
    }

    /**
     * Output response using match expression
     */
    public function __toString(): string {
        $_SESSION['x'] = '';
        return match ($this->ctx->in['x']) {
            'text' => $this->ctx->out['main']
                |> strip_tags(...)
                |> (static fn($s) => preg_replace('/^\h*\v+/m', '', $s)),
            'json' => (header('Content-Type: application/json') ?: '')
                . $this->ctx->out['main'],
            default => $this->ctx->out[$this->ctx->in['x']]
                ?? $this->ctx->buf
        };
    }
}
