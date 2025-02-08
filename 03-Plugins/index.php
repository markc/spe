<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250207
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

define('DBG', true);

class Config
{
    public function __construct(
        public string $email = 'markc@renta.net',
        public array $in = [
            'l' => '',      // Log (message)
            'm' => 'read',  // Method (action)
            'o' => 'home',  // Object (plugin)
            'x' => '',      // XHR (request)
        ],
        public array $out = [
            'doc'   => 'SPE::03',
            'css'   => '',
            'log'   => '',
            'nav1'  => '',
            'head'  => '« Plugin PHP Example',
            'main'  => 'Error: missing plugin!',
            'foot'  => 'Copyright (C) 2015-2025 Mark Constable (AGPL-3.0)',
            'js'    => '',
        ],
        public array $nav1 = [
            ['Home', '?o=home'],
            ['About', '?o=about'],
            ['Contact', '?o=contact'],
        ]
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

        // Process input parameters
        foreach ($this->config->in as $k => $v)
        {
            $this->config->in[$k] = $_REQUEST[$k] ?? $v;
            if (isset($_REQUEST[$k]))
            {
                $this->config->in[$k] = htmlentities(trim($_REQUEST[$k]));
            }
        }

        // Handle plugin execution
        $pluginClass = $this->config->in['o'];
        if (class_exists($pluginClass))
        {
            $plugin = new $pluginClass($this->config);
            $method = $this->config->in['m'];
            if (method_exists($plugin, $method))
            {
                $this->config->out['main'] = $plugin->$method();
            }
            else
            {
                $this->config->out['main'] = "Error: no plugin method!";
            }
        }
        else
        {
            $this->config->out['main'] = "Error: no plugin object!";
        }

        // Process output components
        foreach ($this->config->out as $k => $v)
        {
            if (method_exists($this, $k))
            {
                $this->config->out[$k] = $this->$k();
            }
        }
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        if ($this->config->in['x'])
        {
            $xhr = $this->config->out[$this->config->in['x']] ?? '';
            if ($xhr) return $xhr;
            header('Content-Type: application/json');
            return json_encode($this->config->out, JSON_PRETTY_PRINT);
        }
        return $this->html();
    }

    private function css(): string
    {
        Util::elog(__METHOD__);

        return '
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        pre { background: #EFEEED; padding: 1rem; border-radius: 0.25rem; }
    </style>';
    }

    private function js(): string
    {
        Util::elog(__METHOD__);

        return '
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <script>
        // Only show toasts that have data-auto-show attribute
        var autoShowToasts = document.querySelectorAll(".toast[data-auto-show]");
        autoShowToasts.forEach(toastEl => {
            var toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 3000 });
            toast.show();
        });
        </script>';
    }

    public function log(): string
    {
        Util::elog(__METHOD__);

        if ($this->config->in['l'])
        {
            list($lvl, $msg) = explode(':', $this->config->in['l']);
            $bgClass = $lvl === 'success' ? 'bg-success' : 'bg-danger';
            return sprintf('
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1500">
            <div class="toast" data-auto-show="true" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header %s text-white">
                    <strong class="me-auto">Notification</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">%s</div>
            </div>
        </div>', $bgClass, $msg);
        }
        return '';
    }

    private function nav1(): string
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
                <a class="navbar-brand" href="/">' . $this->config->out['head'] . '</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">' . $links . '</ul>
                </div>
            </div>
        </nav>';
    }

    private function head(): string
    {
        Util::elog(__METHOD__);

        return $this->config->out['nav1'];
    }

    private function main(): string
    {
        Util::elog(__METHOD__);

        return '
        <main class="container py-4">' . $this->config->out['main'] . '</main>';
    }

    private function foot(): string
    {
        Util::elog(__METHOD__);

        return '
        <footer class="bg-light text-center py-3 mt-auto">
            <div class="container">
                <p class="text-muted mb-0"><small>' . $this->config->out['foot'] . '</small></p>
            </div>
        </footer>';
    }

    private function html(): string
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
        <title>' . $doc . '</title>' . $css . '
        <style>
            body { padding-top: 4.5rem; }
        </style>
    </head>
    <body class="d-flex flex-column min-vh-100">' . $head . $log . $main . $foot . $js . '
    </body>
</html>';
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

    protected function showToast(string $message, string $level = 'success'): string
    {
        if (!in_array($level, ['success', 'danger']))
        {
            $level = 'success';
        }
        return '?' . http_build_query(array_merge(
            $_GET,
            ['l' => $level . ':' . $message]
        ));
    }

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
}

final class Home extends Plugin
{
    public function read(): string
    {
        Util::elog(__METHOD__);

        return '
            <div class="px-4 py-5 text-center bg-light rounded-3 border">
                <h1 class="display-4 fw-bold">Home Page</h1>
                <div class="mx-auto">
                    <p class="lead mb-4">
                        This is an ultra simple single-file PHP8.4 framework and template system example.
                        Comments and pull requests are most welcome via the Issue Tracker link.
                    </p>
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                        <a class="btn btn-primary btn-lg px-4 gap-3" href="https://github.com/markc/spe" role="button">Project Page &raquo;</a>
                        <a class="btn btn-primary btn-lg px-4 gap-3" href="https://github.com/markc/spe/issues" role="button">Issue Tracker &raquo;</a>
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

        return <<<'EOT'
            <div class="px-4 py-5 bg-light rounded-3 border">
                <h1 class="display-5 fw-bold">About Page</h1>
                <p class="lead mb-4">
                    This is an example of a simple PHP8.4 "framework" to provide the core
                    structure for further experimental development with both the framework
                    design and some of the new features of PHP8.4.
                </p>
                <form method="post">
                    <div class="d-flex flex-column flex-sm-row gap-2 mb-4">
                        <button type="button" class="btn btn-success flex-fill" onclick="showToast('Everything is working great!', 'success');">Success Message</button>
                        <button type="button" class="btn btn-danger flex-fill" onclick="showToast('Something went wrong!', 'danger');">Danger Message</button>
                        <button type="button" class="btn btn-primary flex-fill" onclick="ajax('1')">JSON</button>
                        <button type="button" class="btn btn-primary flex-fill" onclick="ajax('')">HTML</button>
                        <button type="button" class="btn btn-primary flex-fill" onclick="ajax('foot')">FOOT</button>
                    </div>
                </form>
                <pre id="dbg" class="text-start overflow-auto"></pre>
            </div>
      <script>
function showToast(message, level) {
    var toastContainer = document.createElement("div");
    toastContainer.className = "toast-container position-fixed top-0 end-0 p-3";
    toastContainer.style.zIndex = "1500";
    
    var toastHtml = [
        '<div class="toast" data-auto-show="true" role="alert" aria-live="assertive" aria-atomic="true">',
        '<div class="toast-header bg-' + level + ' text-white">',
        '<strong class="me-auto">Notification</strong>',
        '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>',
        '</div>',
        '<div class="toast-body">' + message + '</div>',
        '</div>'
    ].join("");
    
    toastContainer.innerHTML = toastHtml;
    document.body.appendChild(toastContainer);
    
    var toastEl = toastContainer.querySelector(".toast");
    var toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 3000 });
    toast.show();
    
    toastEl.addEventListener("hidden.bs.toast", function() {
        document.body.removeChild(toastContainer);
    });
}
function ajax(a) {
  if (window.XMLHttpRequest) {
    var x = new XMLHttpRequest();
    x.open("POST", "", true);
    x.onreadystatechange = function() {
      if (x.readyState === 4 && x.status === 200) {
        document.getElementById("dbg").innerHTML = x.responseText
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/\\n/g, "\n")
          .replace(/\\/g, "");
      }
    };
    x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    x.send("o=about&x=" + encodeURIComponent(a));
    return false;
  }
}
      </script>
EOT;
    }
}

final class Contact extends Plugin
{
    public function read(): string
    {
        Util::elog(__METHOD__);

        return '
            <div class="px-4 py-5 bg-light rounded-3 border">
                <h1 class="display-5 fw-bold mb-4">Contact Page</h1>
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <form id="contact-send" method="post" onsubmit="return mailform(this);">
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input class="form-control form-control-lg" id="subject" required type="text" placeholder="Message Subject">
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control form-control-lg" id="message" rows="4" required placeholder="Message Content"></textarea>
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-primary btn-lg" type="submit" id="send">Send</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
      <script>
function mailform(form) {
    location.href = "mailto:' . $this->config->email . '" + "?subject=" + encodeURIComponent(form.subject.value) + "&body=" + encodeURIComponent(form.message.value);
    form.subject.value = "";
    form.message.value = "";
    alert("Thank you for your message. We will get back to you as soon as possible.");
    return false;
}
      </script>';
    }
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
