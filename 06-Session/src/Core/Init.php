<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250208
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Session\Core;

use SPE\Session\Themes\TopNav;
use SPE\Session\Themes\SideBar;
use SPE\Session\Themes\Simple;

readonly class Init
{
    public function __construct(
        private Cfg $cfg,
        private Ctx $ctx
    )
    {
        Util::elog(__METHOD__);

        // Handle session initialization before any output
        if (session_status() === PHP_SESSION_NONE)
        {
            session_start();
        }

        // Initialize plugin navigation
        $this->ctx->nav = (new PluginNav(__DIR__ . '/../Plugins'))->scanPlugins();

        //Util::elog(__METHOD__ . ' this->ctx->nav=' . var_export($this->ctx->nav, true));

        // Store core session values, using session override for 'o'
        $this->ctx->in['o'] = Util::ses('o', $this->ctx->in['o'], $_SESSION['o'] ?? null);
        Util::ses('m', $this->ctx->in['m']);
        Util::ses('t', $this->ctx->in['t']);

        // Process input parameters
        foreach ($this->ctx->in as $k => $v)
        {
            $this->ctx->in[$k] = isset($_REQUEST[$k])
                ? htmlentities(trim($_REQUEST[$k]))
                : $v;
        }

        // Handle plugin execution
        $plugin = 'SPE\\Session\\Plugins\\' . $this->ctx->in['o'] . '\\Model'; // Full plugin class path

        Util::elog(var_export($plugin, true));

        $m = $this->ctx->in['m']; // m=action method

        Util::elog(var_export($m, true));

        // Execute Model
        match (true)
        {
            !class_exists($plugin) => $this->ctx->out['main'] = "Error: no plugin object!",
            !method_exists($plugin, $m) => $this->ctx->out['main'] = "Error: no plugin method!",
            default => (new $plugin($this->cfg, $this->ctx))->$m()
        };

        // Execute View
        $view = str_replace('Model', 'View', $plugin);
        if (class_exists($view) && method_exists($view, $m))
        {
            $this->ctx->out['main'] = (new $view($this->cfg, $this->ctx))->$m();
        }

        if ($this->ctx->in['x'])
        {
            $xhr = $this->ctx->out[$this->ctx->in['x']] ?? '';
            if ($xhr) return $xhr;
            header('Content-Type: application/json');
            return json_encode($this->ctx->out, JSON_PRETTY_PRINT);
        }

        // Dynamically select the theme based on the 't' parameter
        $t = match ($this->ctx->in['t'])
        {
            'TopNav' => TopNav::class,
            'SideBar' => SideBar::class,
            default => Simple::class,
        };

        $theme = new $t($this->cfg, $this->ctx);

        foreach ($this->ctx->out as $k => $v)
        {
            if (method_exists($theme, $k))
            {
                $this->ctx->out[$k] = $theme->$k();
            }
        }

        //Util::elog(__METHOD__ . ' ' . var_export($this->ctx->out, true));

        $this->ctx->buf = $theme->html();
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        return $this->ctx->buf;
    }

    public function __destruct()
    {
        //Util::elog(__METHOD__);

        Util::elog(__METHOD__ . ' SESSION=' . var_export($_SESSION, true));
        //Util::elog($_SERVER['REMOTE_ADDR'] . ' ' . round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']), 4));
    }
}
