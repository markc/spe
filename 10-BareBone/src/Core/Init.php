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

        $this->ctx->self = $_SERVER['PHP_SELF'];

        if (session_status() === PHP_SESSION_NONE)
        {
            session_start();
        }

        $this->ctx->nav = (new PluginNav(__DIR__ . '/../Plugins'))->scanPlugins();

        array_map(
            fn($k) => $this->ctx->in[$k] = Util::ses($k, $this->ctx->in[$k], $_SESSION[$k] ?? $this->ctx->in[$k]),
            array_keys($this->ctx->in)
        );

        extract($this->ctx->in, EXTR_SKIP);

        $pm = 'SPE\\BareBone\\Plugins\\' . $o . '\\Model';  // plugin model
        $t1 = 'SPE\\BareBone\\Plugins\\' . $o . '\\View';   // plugin view theme
        $t2 = 'SPE\\BareBone\\Themes\\' . $t;               // current theme extends Theme

        // Handle plugin action
        if (!class_exists($pm))
        {
            $this->ctx->out['main'] = "Error: no plugin object!";
        }
        else
        {
            $plugin = new $pm($this->ctx);
            if (!method_exists($plugin, $m))
            {
                $this->ctx->out['main'] = "Error: no plugin method!";
            }
            else
            {
                // Execute plugin model method to populate ctx->ary
                $plugin->$m();

                // Get view to render the model data
                if (class_exists($t1))
                {
                    $view = new $t1($this->ctx);
                    if (method_exists($view, $m))
                    {
                        $this->ctx->out['main'] = $view->$m();
                    }
                }
            }
        }

        // Initialize themes
        $theme1 = class_exists($t1) ? new $t1($this->ctx) : null;
        $theme2 = class_exists($t2) ? new $t2($this->ctx) : null;

        // Apply theme methods to output sections
        if (!$theme2)
        {
            $this->ctx->buf = 'Error: No theme available';
            return;
        }

        // For each output section, try plugin view first, then fall back to theme
        foreach ($this->ctx->out as $k => $v)
        {
            if ($theme1 && method_exists($theme1, $k))
            {
                $this->ctx->out[$k] = $theme1->$k();
            }
            elseif (method_exists($theme2, $k))
            {
                $this->ctx->out[$k] = $theme2->$k();
            }
        }

        // For final HTML rendering, try plugin view first, then fall back to theme
        if ($theme1 && method_exists($theme1, 'html'))
        {
            $this->ctx->buf = $theme1->html();
        }
        else
        {
            $this->ctx->buf = $theme2->html();
        }
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

        //Util::elog(__METHOD__ . ' SESSION=' . var_export($_SESSION, true));
        //Util::elog($_SERVER['REMOTE_ADDR'] . ' ' . round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']), 4));
    }
}
