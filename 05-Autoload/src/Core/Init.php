<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Autoload\Core;

//use SPE\Autoload\Core\{Ctx, Util};

readonly class Init
{
    public function __construct(
        private Ctx $ctx
    )
    {
        Util::elog(__METHOD__);

        $ns = __NAMESPACE__ ? substr(__NAMESPACE__, 0, strrpos(__NAMESPACE__, '\\')) : '';

        // Process input parameters
        foreach ($this->ctx->in as $k => $v)
        {
            $this->ctx->in[$k] = $_REQUEST[$k] ?? $v;
            if (isset($_REQUEST[$k]))
            {
                $this->ctx->in[$k] = htmlentities(trim($_REQUEST[$k]));
            }
        }
        //Util::elog('this->ctx->in=' . var_export($this->ctx->in, true));
        extract($this->ctx->in, EXTR_SKIP);

        $pm = $ns ? "{$ns}\\Plugins\\{$o}\\{$o}Model" : "{$o}Model";
        $t1 = $ns ? "{$ns}\\Plugins\\{$o}\\{$o}View" : "{$o}View";
        $t2 = $ns ? "{$ns}\\Themes\\{$t}" : "{$t}";

        Util::elog("o={$o}, m={$m}, t={$t}, pm={$pm}, t1={$t1}, t2={$t2}");

        $this->ctx->ary = class_exists($pm) ? (new $pm($this->ctx))->$m() : [];
        //Util::elog('this->ctx->ary=' . var_export($this->ctx->ary, true));
        $theme1 = class_exists($t1) ? new $t1($this->ctx) : null;
        $theme2 = class_exists($t2) ? new $t2($this->ctx) : null;

        $render = fn(?object $theme, string $method) => ($theme && is_callable([$theme, $method]))
            ? $theme->$method() : null;

        $this->ctx->out['main'] = $render($theme1, $m)
            ?? $render($theme2, $m) ?? $this->ctx->out['main'];

        foreach ($this->ctx->out as $k => &$v)
            $v = $render($theme1, $k) ?? $render($theme2, $k) ?? $v;

        $this->ctx->buf = $render($theme1, 'html') ?? $render($theme2, 'html') ?? '';
        //Util::elog('this->ctx->buf=' . var_export($this->ctx->buf, true));
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        return $this->ctx->buf;
    }
}
