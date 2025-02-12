<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250208
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Auth\Core;

readonly class Init
{
    public function __construct(
        private Cfg $cfg,
        private Ctx $ctx
    )
    {
        Util::elog(__METHOD__);

        $this->ctx->self = $_SERVER['PHP_SELF'];

        if (session_status() === PHP_SESSION_NONE)
        {
            session_start();
        }
        //$_SESSION = [];
        $this->ctx->nav = (new PluginNav(__DIR__ . '/../Plugins'))->scanPlugins();

        array_map(
            fn($k) => $this->ctx->in[$k] = Util::ses($k, $this->ctx->in[$k], $_SESSION[$k] ?? $this->ctx->in[$k]),
            array_keys($this->ctx->in)
        );

        extract($this->ctx->in, EXTR_SKIP);

        $pm = 'SPE\\Auth\\Plugins\\' . $o . '\\Model';  // plugin model
        $t1 = 'SPE\\Auth\\Plugins\\' . $o . '\\View';   // plugin view theme
        $t2 = 'SPE\\Auth\\Themes\\' . $t;               // current theme extends Theme

        match (true)
        {
            !class_exists($pm) => $this->ctx->out['main'] = "Error: no plugin object!",
            !method_exists($pm, $m) => $this->ctx->out['main'] = "Error: no plugin method!",
            default => (new $pm($this->cfg, $this->ctx))->$m()
        };

        if (class_exists($t1) && method_exists($t1, $m))
        {
            $this->ctx->out['main'] = (new $t1($this->cfg, $this->ctx))->$m($this->ctx->in);
        }

        $theme1 = class_exists($t1) ? new $t1($this->cfg, $this->ctx) : null;
        $theme2 = class_exists($t2) ? new $t2($this->cfg, $this->ctx) : null;

        foreach ($this->ctx->out as $k => $v)
        {
            $this->ctx->out[$k] = match (true)
            {
                $theme1 && method_exists($t1, $k) => $theme1->$k(),
                $theme2 && method_exists($t2, $k) => $theme2->$k(),
                default => sprintf('Error: %s() does not exist in any template', $k)
            };
        }

        $this->ctx->buf = match (true)
        {
            $theme1 && method_exists($t1, 'html') => $theme1->html(),
            $theme2 && method_exists($t2, 'html') => $theme2->html(),
            default => 'Error: html() does not exist'
        };
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        if ($this->ctx->in['x'])
        {
            $xhr = $this->ctx->out[$this->ctx->in['x']] ?? '';
            if ($xhr) return $xhr;
            header('Content-Type: application/json');
            return json_encode($this->ctx->out, JSON_PRETTY_PRINT);
        }
        return $this->ctx->buf;
    }

    public function __destruct()
    {
        Util::elog(__METHOD__);

        Util::elog(__METHOD__ . ' SESSION=' . var_export($_SESSION, true));
        //Util::elog($_SERVER['REMOTE_ADDR'] . ' ' . round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']), 4));
    }
}
