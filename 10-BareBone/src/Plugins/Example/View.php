<?php

declare(strict_types=1);
// Created: 20250201 - Updated: 20250213
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\BareBone\Plugins\Example;

use SPE\BareBone\Core\{Ctx, Util};

class View
{
    public function __construct(private Ctx $ctx)
    {
        //parent::__construct($ctx); // Hmm, maybe not needed
    }

    // Plugin Actions Views

    public function create(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function read(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function update(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function delete(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function list(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    // HTML Partial Views

    public function html(): string
    {
        Util::elog(__METHOD__);

        extract($this->ctx->out, EXTR_SKIP);

        return '<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>' . $doc . '</title>' . $css . '
    </head>
    <body>
        <a href="?o=Home">Home</a>' . $head . $log . $main . $foot . $js . '
    </body>
</html>
';
    }

    public function doc(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function css(): string
    {
        Util::elog(__METHOD__);

        return '
        <style>
            body {
                text-align: center;
                width: 60rem;
                margin-left: auto;
                margin-right: auto;
            }
            
            nav, header, main, footer, pre, div {
                border: dashed 1px red;
                margin: 1rem;
                padding: 1rem;
            }
            
            @media screen and (max-width: 768px) {
                body {
                    width: 100%;
                    margin: 0;
                }
                
                nav, header, main, footer, pre, div {
                    width: auto;
                    margin: 1rem;
                }
            }
        </style>';
    }

    public function log(): string
    {
        Util::elog(__METHOD__);

        return '

        <div>
            ' . __METHOD__ . ' (Alerts area)
        </div>';
    }

    public function nav1(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function nav2(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function nav3(): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function head(): string
    {
        Util::elog(__METHOD__);

        return '
        <header>
            ' . __METHOD__ . '
            <nav>
                ' . $this->nav1() . ' |
                ' . $this->nav2() . ' |
                ' . $this->nav3() . '
            </nav>
        </header>';
    }

    public function main(): string
    {
        Util::elog(__METHOD__);

        return '

        <main>
            ' . __METHOD__ . '
        </main>';
    }

    public function foot(): string
    {
        Util::elog(__METHOD__);

        return '

        <footer>
            ' . __METHOD__ . '
        </footer>';
    }

    public function js(): string
    {
        Util::elog(__METHOD__);

        return '

        <script>
            document.write("<div>' . addslashes(__METHOD__) . '</div>")
        </script>';
    }
}
