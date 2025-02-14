<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250213
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\BareBone\Core;

use SPE\BareBone\Core\{Ctx, Util, PluginNav};

readonly class Init
{
    public function __construct(private Ctx $ctx)
    {
        Util::elog(__METHOD__);

        $ns = __NAMESPACE__ ? substr(__NAMESPACE__, 0, strrpos(__NAMESPACE__, '\\')) : '';

        $this->ctx->nav = $ns ? (new PluginNav(__DIR__ . '/../Plugins'))->scanPlugins() : $this->ctx->nav1;

        session_status() === PHP_SESSION_NONE && session_start();

        foreach ($this->ctx->in as $k => &$v) $v = Util::ses($k, $v);

        extract($this->ctx->in, EXTR_SKIP);

        $pm = $ns ? "{$ns}\\Plugins\\{$o}\\{$o}Model" : "{$o}Model";
        $t1 = $ns ? "{$ns}\\Plugins\\{$o}\\{$o}View" : "{$o}View";
        $t2 = $ns ? "{$ns}\\Themes\\{$t}" : "{$t}";

        $this->ctx->ary = class_exists($pm) ? (new $pm($this->ctx))->$m() : [];

        $theme1 = class_exists($t1) ? new $t1($this->ctx) : null;
        $theme2 = class_exists($t2) ? new $t2($this->ctx) : null;

        $render = fn(?object $theme, string $method) => ($theme && method_exists($theme, $method))
            ? $theme->$method() : null;

        $this->ctx->out['main'] = $render($theme1, $m)
            ?? $render($theme2, $m) ?? $this->ctx->out['main'];

        foreach ($this->ctx->out as $k => &$v)
            $v = $render($theme1, $k) ?? $render($theme2, $k) ?? $v;

        $this->ctx->buf = $render($theme1, 'html') ?? $render($theme2, 'html') ?? '';
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        if ($x = $this->ctx->in['x'])
        {
            $xhr = $this->ctx->out[$x] ?? '';
            if ($xhr) return $xhr;
            header('Content-Type: application/json');
            return json_encode($this->ctx->out, JSON_PRETTY_PRINT);
        }
        return $this->ctx->buf;
    }

    public function __destruct()
    {
        Util::elog($_SERVER['REMOTE_ADDR'] . ' ' . round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']), 4));
    }
}

readonly class Init2
{
    public function __construct(private Ctx $ctx)
    {
        Util::elog(__METHOD__);

        $this->ctx->self = $_SERVER['PHP_SELF'];
        session_status() === PHP_SESSION_NONE && session_start();
        $this->ctx->nav = (new PluginNav(__DIR__ . '/../Plugins'))->scanPlugins();
        Util::elog(dirname(__NAMESPACE__));
        foreach (array_keys($this->ctx->in) as $k)
        {
            $this->ctx->in[$k] = Util::ses($k, $this->ctx->in[$k]);
        }

        extract($this->ctx->in, EXTR_SKIP);

        // Define the plugin and theme names to call
        $pm = __NAMESPACE__ ? "SPE\\BareBone\\Plugins\\{$o}\\{$o}Model" : "{$o}Model";
        $t1 = __NAMESPACE__ ? "SPE\\BareBone\\Plugins\\{$o}\\{$o}View" : "{$o}View";
        $t2 = __NAMESPACE__ ? "SPE\\BareBone\\Themes\\{$t}" : "{$t}";

        // Call the Plugin modal action method and save the results to a global array
        $this->ctx->ary = class_exists($pm) ? (new $pm($this->ctx))?->$m() : null;

        // Instantiate the view and theme, leveraging null coalescing for brevity
        $theme1 = class_exists($t1) ? new $t1($this->ctx) : null;
        $theme2 = class_exists($t2) ? new $t2($this->ctx) : null;

        $render = function (?object $theme, string $method): ?string
        {
            return ($theme && method_exists($theme, $method)) ? $theme->$method() : null;
        };

        $this->ctx->out['main'] = $render($theme1, $m)
            ?? $render($theme2, $m)
            ?? $this->ctx->out['main'];

        // For each output section, try plugin view first, then fall back to theme
        foreach ($this->ctx->out as $k => &$v)
        {
            $v = $render($theme1, $k) ?? $render($theme2, $k) ?? $v;
        }

        $this->ctx->buf = $render($theme1, 'html') ?? $render($theme2, 'html') ?? '';
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);
        if ($x = $this->ctx->in['x'])
        {
            $xhr = $this->ctx->out[$x] ?? '';
            if ($xhr) return $xhr;
            header('Content-Type: application/json');
            return json_encode($this->ctx->out, JSON_PRETTY_PRINT);
        }
        return $this->ctx->buf;
    }

    public function __destruct()
    {
        Util::elog($_SERVER['REMOTE_ADDR'] . ' ' . round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']), 4));
    }
}
