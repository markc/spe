<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250208
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

define('DBG', true);

class Config
{
    public function __construct(
        public string $str = '',    // Global string buffer
        public array $ary = [],     // Plugin CRUDL return array
        public array $cfg = [       // Misc config properties
            'email' => 'markc@renta.net',
            'self'  => '/',
        ],
        public array $in = [        // Input URI variables
            'l' => '',              // Log (alert)
            'm' => 'read',          // Method (action)
            'o' => 'home',          // Object (plugin)
            't' => 'simple',        // Theme (current)
            'x' => '',              // XHR (request)
        ],
        public array $out = [       // Theme Method partials
            'doc'   => 'SPE::04',
            'css'   => '',
            'log'   => '',
            'nav1'  => '',
            'nav2'  => '',
            'head'  => 'Themes PHP Example',
            'main'  => 'Error: missing plugin!',
            'foot'  => 'Copyright (C) 2015-2025 Mark Constable (AGPL-3.0)',
            'js'    => '',
        ],
        public array $nav1 = [
            ['Home',        '?o=home'],
            ['About',       '?o=about'],
            ['Contact',     '?o=contact'],
        ],
        public array $nav2 = [
            ['Simple',      '?t=SimpleTheme'],
            ['TopNav',      '?t=TopNavTheme'],
            ['Sidebar',     '?t=SideBarTheme'],
        ],
    )
    {
        Util::elog(__METHOD__);
    }
}

readonly class Init
{
    public function __construct(
        private Config $config
    )
    {
        Util::elog(__METHOD__);

        $this->config->cfg['self'] = str_replace('index.php', '', $_SERVER['PHP_SELF']);

        // Process input parameters
        foreach ($this->config->in as $k => $v)
        {
            $this->config->in[$k] = $_REQUEST[$k] ?? $v;
            if (isset($_REQUEST[$k]))
            {
                $this->config->in[$k] = htmlentities(trim($_REQUEST[$k]));
            }
        }

        // Handle plugin execution (o=plugin object/class, m=action method)
        $object = $this->config->in['o'];
        $method = $this->config->in['m'];

        $this->config->out['main'] = match (true)
        {
            !class_exists($object) => "Error: no plugin object!",
            !method_exists($object, $method) => "Error: no plugin method!",
            default => (new $object($this->config))->$method()
        };

        if ($this->config->in['x'])
        {
            $xhr = $this->config->out[$this->config->in['x']] ?? '';
            if ($xhr) return $xhr;
            header('Content-Type: application/json');
            return json_encode($this->config->out, JSON_PRETTY_PRINT);
        }

        // Dynamically select the theme based on the 't' parameter
        $themeClass = match ($this->config->in['t'])
        {
            'TopNavTheme' => TopNavTheme::class,
            'SideBarTheme' => SideBarTheme::class,
            default => SimpleTheme::class, // Use SimpleTheme as default instead of abstract Theme
        };

        $theme = new $themeClass($this->config);

        foreach ($this->config->out as $k => $v)
        {
            if (method_exists($theme, $k))
            {
                $this->config->out[$k] = $theme->$k();
            }
        }

        Util::elog(__METHOD__ . ' ' . var_export($this->config->out, true));

        $this->config->str = $theme->html();
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        return $this->config->str;
    }
}

abstract class Plugin
{
    protected string $buf = '';

    public function __construct(
        protected readonly Config $config
    )
    {
        Util::elog(__METHOD__);
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        return $this->buf;
    }

    abstract public function read(): string;

    public function create(): string
    {
        Util::elog(__METHOD__);

        return "Plugin::create() not implemented yet!";
    }

    public function update(): string
    {
        Util::elog(__METHOD__);

        return "Plugin::update() not implemented yet!";
    }

    public function delete(): string
    {
        Util::elog(__METHOD__);

        return "Plugin::delete() not implemented yet!";
    }

    public function list(): string
    {
        Util::elog(__METHOD__);

        return "Plugin::list() not implemented yet!";
    }
}

abstract class Theme
{
    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
        Util::elog(__METHOD__);
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);
        return $this->html();
    }


    public function css(): string
    {
        Util::elog(__METHOD__);

        return '
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <style>
            body { padding-top: 4.5rem; }
        </style>';
    }

    public function js(): string
    {
        Util::elog(__METHOD__);

        return '
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const toastElList = document.querySelectorAll(".toast");
                    toastElList.forEach(function(toastEl) {
                        const toast = new bootstrap.Toast(toastEl, {
                            autohide: true,
                            delay: 3000
                        });
                        toast.show();
                    });
                });
                function showToast(message, type) {
                    const toastContainer = document.createElement("div");
                    toastContainer.setAttribute("aria-live", "polite");
                    toastContainer.setAttribute("aria-atomic", "true");
                    toastContainer.style.position = "fixed";
                    toastContainer.style.top = "20px";
                    toastContainer.style.right = "20px";
                    toastContainer.style.zIndex = "1050";

                    toastContainer.innerHTML = 
                        \'<div class="toast align-items-center text-white bg-\' + type + \' border-0" role="alert" aria-live="assertive" aria-atomic="true">\' +
                            \'<div class="d-flex">\' +
                                \'<div class="toast-body">\' + message + \'</div>\' +
                                \'<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>\' +
                            \'</div>\' +
                        \'</div>\';

                    document.body.appendChild(toastContainer);
                    const toastElement = toastContainer.querySelector(".toast");
                    const toast = new bootstrap.Toast(toastElement, {
                        autohide: true,
                        delay: 3000
                    });
                    toast.show();

                    toastElement.addEventListener("hidden.bs.toast", () => {
                        toastContainer.remove();
                    });
                }    
            </script>';
    }

    public function log(): string
    {
        Util::elog(__METHOD__);
        //Util::elog(__METHOD__ . ' ' . var_export($this->config, true));

        if ($this->config->in['l'])
        {
            [$lvl, $msg] = explode(':', $this->config->in['l']);
            $bgClass = $lvl === 'success' ? 'bg-success' : 'bg-danger';
            return '
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1500">
            <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header ' . $bgClass . ' text-white">
                    <strong class="me-auto">Notification</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">' . $msg . '</div>
            </div>
        </div>';
        }
        return '';
    }

    public function nav1(): string
    {
        Util::elog(__METHOD__);

        $o = '?o=' . $this->config->in['o'];
        $links = join('', array_map(function ($n) use ($o)
        {
            $url = str_starts_with($n[1], 'http') ? $n[1] : $n[1];
            $c = $o === $url ? ' active' : '';
            return '
                        <li class="nav-item">
                            <a class="nav-link' . $c . '" href="' . $url . '"' . ($c ? ' aria-current="page"' : '') . '>' . $n[0] . '</a>
                        </li>';
        }, $this->config->nav1));

        return '
        <nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
            <div class="container">
                <a class="navbar-brand" href="/">« ' . $this->config->out['head'] . '</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">' . $links . '</ul>
                </div>
            </div>
        </nav>';
    }


    public function nav2(): string
    {
        Util::elog(__METHOD__);

        $o = '?o=' . $this->config->in['o'];
        $links = join('', array_map(function ($n) use ($o)
        {
            $url = str_starts_with($n[1], 'http') ? $n[1] : $n[1];
            $c = $o === $url ? ' active' : '';
            return '
                        <li class="nav-item">
                            <a class="nav-link' . $c . '" href="' . $url . '"' . ($c ? ' aria-current="page"' : '') . '>' . $n[0] . '</a>
                        </li>';
        }, $this->config->nav2));

        return '
        <nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
            <div class="container">
                <a class="navbar-brand" href="/">« ' . $this->config->out['head'] . '</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">' . $links . '</ul>
                </div>
            </div>
        </nav>';
    }

    public function head(): string
    {
        Util::elog(__METHOD__);

        return $this->config->out['nav2'];
    }

    public function main(): string
    {
        Util::elog(__METHOD__);

        return '
        <main class="container py-4">' . $this->config->out['main'] . '</main>';
    }

    public function foot(): string
    {
        Util::elog(__METHOD__);

        return '
        <footer class="bg-light text-center py-3 mt-auto">
            <div class="container">
                <p class="text-muted mb-0"><small>' . $this->config->out['foot'] . '</small></p>
            </div>
        </footer>';
    }

    public function html(): string
    {
        Util::elog(__METHOD__);

        extract($this->config->out, EXTR_SKIP);
        return '<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Simple PHP Example with Plugins">
        <meta name="author" content="Mark Constable">
        <link rel="icon" href="favicon.ico">
        <title>Theme ' . $doc . '</title>' . $css . '
    </head>
    <body class="d-flex flex-column min-vh-100">' . $head . $log . $main . $foot . $js . '
    </body>
</html>';
    }

    public function create(array $in): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function read(array $in): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function update(array $in): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function delete(array $in): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }

    public function list(array $in): string
    {
        Util::elog(__METHOD__);

        return __METHOD__;
    }
}

final class Home extends Plugin
{
    public function read(): string
    {
        Util::elog(__METHOD__);

        return '
            <div class="px-4 py-5 bg-light rounded-3 border">
                <div class="row d-flex justify-content-center">
                <div class="col-lg-8 col-md-10 col-sm-12">
                    <h1 class="display-5 fw-bold text-center">' . $this->config->out['head'] . '</h1>
                    <p class="lead mb-4">
This is an example of a simple PHP8.4 "framework" to provide the core
structure for further experimental development with both the framework
design and some of the new features of PHP8.4.
                    </p>
                    <form method="post">
                        <div class="d-flex flex-column flex-sm-row gap-2 mb-4">
                            <button type="button" class="btn btn-success flex-fill" onclick="showToast(\'Everything is working great!\', \'success\');">Success Message</button>
                            <button type="button" class="btn btn-danger flex-fill" onclick="showToast(\'Something went wrong!\', \'danger\');">Danger Message</button>
                        </div>
                    </form>
                    <pre id="dbg" class="text-start overflow-auto"></pre>
                </div>
                </div>
            </div>';
    }
}

final class About extends Plugin
{
    public function read(): string
    {
        Util::elog(__METHOD__);

        return '<h1 class="text-center">This is the About page</h1>';
    }
}

final class Contact extends Plugin
{
    public function read(): string
    {
        Util::elog(__METHOD__);

        return '<h1 class="text-center">This is the Contact page</h1>';
    }
}

class SimpleTheme extends Theme
{
    public function __construct(protected Config $config)
    {
        Util::elog(__METHOD__);

        parent::__construct($config);
    }

    public function html(): string
    {
        Util::elog(__METHOD__);

        extract($this->config->out, EXTR_SKIP);
        return '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SimpleTheme ' . $doc . '</title>' . $css . '
  </head>
  <body>' . $head . $main . $foot . $js . '
  </body>
</html>';
    }
}

class TopNavTheme extends Theme
{
    public function __construct(protected Config $config)
    {
        Util::elog(__METHOD__);

        parent::__construct($config);
    }

    public function html(): string
    {
        Util::elog(__METHOD__);

        extract($this->config->out, EXTR_SKIP);
        return '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TopNavTheme ' . $doc . '</title>' . $css . '
  </head>
  <body>' . $nav1 . $head . $main . $foot . $js . '
  </body>
</html>';
    }
}

class SideBarTheme extends Theme
{
    public function __construct(protected Config $config)
    {
        Util::elog(__METHOD__);

        parent::__construct($config);
    }
    /*
    public function html(): string
    {
        Util::elog(__METHOD__);

        extract($this->config->out, EXTR_SKIP);
        return '<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Simple PHP Example with Plugins">
        <meta name="author" content="Mark Constable">
        <link rel="icon" href="favicon.ico">
        <title>SideBarTheme ' . $doc . '</title>' . $css . '
    </head>
    <body class="d-flex flex-column min-vh-100">' . $nav2 . $head . $main . $foot . $js . '
    </body>
</html>';
    }
    */
}

final class Util
{
    public static function elog(string $content): void
    {
        if (defined('DBG') && DBG)
        {
            error_log($content);
        }
    }
}

// Bootstrap the application
echo new Init(new Config());
