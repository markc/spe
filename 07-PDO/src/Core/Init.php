<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250213
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\PDO\Core;

use SPE\PDO\Core\{Ctx, Util, NavPlugin};

readonly class Init
{
    public function __construct(private Ctx $ctx)
    {
        Util::elog(__METHOD__);

        Util::elog('GET=' . var_export($_GET, true));
        Util::elog('POST=' . var_export($_POST, true));

        $ns = __NAMESPACE__ ? substr(__NAMESPACE__, 0, strrpos(__NAMESPACE__, '\\')) : '';

        session_status() === PHP_SESSION_NONE && session_start();

        //$_SESSION = []; // To clear for testing

        $this->ctx->nav = (new NavPlugin(__DIR__ . '/../Plugins'))->scanPlugins();

        //Util::elog(__METHOD__ . ' this->ctx->nav=' . var_export($this->ctx->nav, true));

        foreach ($this->ctx->in as $k => &$v) $v = Util::ses($k, $v);

        extract($this->ctx->in, EXTR_SKIP);

        $pm = $ns ? "{$ns}\\Plugins\\{$o}\\{$o}Model" : "{$o}Model";
        $t1 = $ns ? "{$ns}\\Plugins\\{$o}\\{$o}View" : "{$o}View";
        $t2 = $ns ? "{$ns}\\Themes\\{$t}" : "{$t}";

        $this->ctx->ary = class_exists($pm) ? (new $pm($this->ctx))->$m() : [];

        $theme1 = class_exists($t1) ? new $t1($this->ctx) : null;

        $theme2 = class_exists($t2) ? new $t2($this->ctx) : null;

        $render = fn(?object $o, string $m) => ($o && method_exists($o, $m))
            ? $o->$m() : null;

        $this->ctx->out['main'] = $render($theme1, $m)
            ?? $render($theme2, $m) ?? $this->ctx->out['main'];

        foreach ($this->ctx->out as $k => &$v)
            $v = $render($theme1, $k) ?? $render($theme2, $k) ?? $v;

        $this->ctx->buf = $render($theme1, 'html') ?? $render($theme2, 'html') ?? '';
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        $x = $this->ctx->in['x'] ?? null;

        $_SESSION['x'] = '';

        return match ($x)
        {
            'text' => preg_replace('/^\h*\v+/m', '', strip_tags($this->ctx->out['main'])),
            'json' => (function ()
            {
                header('Content-Type: application/json');
                return $this->ctx->out['main'];
            })(),
            default => $this->ctx->out[$x] ?? $this->ctx->buf,
        };
    }

    public function __destruct()
    {
        //Util::elog(__METHOD__);

        Util::elog(__METHOD__ . ' SESSION=' . var_export($_SESSION, true));
        //Util::elog($_SERVER['REMOTE_ADDR'] . ' ' . round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']), 4));
    }
}
