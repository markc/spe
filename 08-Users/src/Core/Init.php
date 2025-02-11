<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250208
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Users\Core;

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
        $this->ctx->nav = (new \SPE\Users\Core\PluginNav(__DIR__ . '/../Plugins'))->scanPlugins();

        //Util::elog(__METHOD__ . ' this->ctx->nav=' . var_export($this->ctx->nav, true));

        // Store core session values, preserving existing session values
        $this->ctx->in['i'] = Util::ses('i', $this->ctx->in['i'], $_SESSION['i'] ?? $this->ctx->in['i']);
        $this->ctx->in['m'] = Util::ses('m', $this->ctx->in['m'], $_SESSION['m'] ?? $this->ctx->in['m']);
        $this->ctx->in['o'] = Util::ses('o', $this->ctx->in['o'], $_SESSION['o'] ?? $this->ctx->in['o']);
        $this->ctx->in['p'] = Util::ses('p', $this->ctx->in['p'], $_SESSION['p'] ?? $this->ctx->in['p']);
        $this->ctx->in['t'] = Util::ses('t', $this->ctx->in['t'], $_SESSION['t'] ?? $this->ctx->in['t']);

        // Process input parameters
        foreach ($this->ctx->in as $k => $v)
        {
            $this->ctx->in[$k] = isset($_REQUEST[$k])
                ? trim($_REQUEST[$k])
                : $v;
        }

        // Merge POST data for form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
        {
            foreach ($_POST as $k => $v)
            {
                if (!isset($this->ctx->in[$k]))
                {
                    $this->ctx->in[$k] = trim($v);
                }
            }
        }

        // Handle plugin execution
        $baseNamespace = __NAMESPACE__;  // Gets current namespace (e.g., SPE\PDO\Core)
        $baseNamespace = substr($baseNamespace, 0, strrpos($baseNamespace, '\\')); // Remove 'Core' from namespace
        $plugin = $baseNamespace . '\\Plugins\\' . $this->ctx->in['o'] . '\\Model'; // Full plugin class path

        //Util::elog(var_export($plugin, true));

        $m = $this->ctx->in['m']; // m=action method

        //Util::elog(var_export($m, true));

        // Execute Model
        match (true)
        {
            !class_exists($plugin) => $this->ctx->out['main'] = "Error: no plugin object!",
            !method_exists($plugin, $m) => $this->ctx->out['main'] = "Error: no plugin method!",
            default => (new $plugin($this->cfg, $this->ctx))->$m()
        };

        // Execute View
        $view = str_replace('Model', 'View', $plugin);
        $viewInstance = null;
        if (class_exists($view))
        {
            $viewInstance = new $view($this->cfg, $this->ctx);
            if (method_exists($viewInstance, $m))
            {
                $this->ctx->out['main'] = $viewInstance->$m();
            }

            // Check for other plugin partials (js, css, etc.)
            foreach ($this->ctx->out as $k => $v)
            {
                //if ($k !== 'main' && method_exists($viewInstance, $k))
                if (method_exists($viewInstance, $k))
                {
                    $this->ctx->out[$k] = $viewInstance->$k();
                }
            }
        }

        if ($this->ctx->in['x'])
        {
            $xhr = $this->ctx->out[$this->ctx->in['x']] ?? '';
            if ($xhr) return $xhr;
            header('Content-Type: application/json');
            return json_encode($this->ctx->out, JSON_PRETTY_PRINT);
        }

        // Dynamically select the theme based on the 't' parameter
        $themeClass = $baseNamespace . '\\Themes\\' . $this->ctx->in['t'];
        if (!class_exists($themeClass))
        {
            $themeClass = $baseNamespace . '\\Themes\\Simple'; // Default to Simple theme
        }

        $theme = new $themeClass($this->cfg, $this->ctx);

        // Fall back to theme methods for any partials not set by plugin
        foreach ($this->ctx->out as $k => $v)
        {
            if (empty($v) && method_exists($theme, $k))
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
        Util::elog(__METHOD__);

        //Util::elog(__METHOD__ . ' SESSION=' . var_export($_SESSION, true));
        //Util::elog($_SERVER['REMOTE_ADDR'] . ' ' . round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']), 4));
    }
}
