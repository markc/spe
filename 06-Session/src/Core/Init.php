<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Core;

final class Init {
    private const string NS = 'SPE\\Session\\';

    public function __construct(private Ctx $ctx) {
        session_status() === PHP_SESSION_NONE && session_start();

        // Only persist o and t in session; m defaults to 'list' when not in URL
        $this->ctx->in['o'] = $this->ses('o', $this->ctx->in['o']);
        $this->ctx->in['t'] = $this->ses('t', $this->ctx->in['t']);
        $this->ctx->in['m'] = $_REQUEST['m'] ?? 'list';

        ['o' => $o, 'm' => $m, 't' => $t] = $this->ctx->in;

        $model = self::NS . "Plugins\\{$o}\\{$o}Model";
        $this->ctx->ary = class_exists($model) ? (new $model($this->ctx))->$m() : [];

        $view = self::NS . "Plugins\\{$o}\\{$o}View";
        $theme = self::NS . "Themes\\{$t}";
        // Fallback to Simple if theme doesn't exist
        if (!class_exists($theme)) {
            $t = 'Simple';
            $this->ctx->in['t'] = $t;
            $_SESSION['t'] = $t;
            $theme = self::NS . "Themes\\{$t}";
        }
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

    private function ses(string $k, mixed $v = ''): mixed {
        if (isset($_REQUEST[$k])) {
            $_SESSION[$k] = is_array($_REQUEST[$k]) ? $_REQUEST[$k] : trim($_REQUEST[$k]);
        } elseif (!isset($_SESSION[$k])) {
            $_SESSION[$k] = $v;
        }
        return $_SESSION[$k];
    }
}
