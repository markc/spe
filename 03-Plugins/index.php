<?php declare(strict_types=1);
// Created: 20150101 - Updated: 20250206
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

class Init
{
    public function __construct($g)
    {
        $this->g = $g;

        foreach ($g->in as $k => $v)
            $this->g->in[$k] = isset($_REQUEST[$k])
                ? htmlentities(trim($_REQUEST[$k])) : $v;

        if (class_exists($g->in['o'])) {
            $plugin = new $g->in['o']($g);
            if (method_exists($plugin, $g->in['m'])) {
                $g->out['main'] = $plugin->{$g->in['m']}();
            } else $g->out['main'] = "Error: no plugin method!";
        } else $g->out['main'] = "Error: no plugin object!";

        foreach ($g->out as $k => $v)
            $g->out[$k] = method_exists($this, $k) ? $this->$k() : $v;
    }

    public function __toString() : string
    {
        if ($this->g->in['x']) {
            $xhr = $this->g->out[$this->g->in['x']] ?? '';
            if ($xhr) return $xhr;
            header('Content-Type: application/json');
            return json_encode($this->g->out, JSON_PRETTY_PRINT);
        }
        return $this->html();
    }

    private function css() : string
    {
        return '
    <link href="//fonts.googleapis.com/css?family=Roboto:100,300,400,500,300italic" rel="stylesheet" type="text/css">
    <style>
* { transition: 0.25s linear; }
body {
    background-color: #fff;
    color: #444;
    font-family: "Roboto", sans-serif;
    font-weight: 300;
    height: 50rem;
    line-height: 1.5;
    margin: 0 auto;
    max-width: 42rem;
}
h1, h2, h3, nav, footer {
    color: #0275d8;
    font-weight: 300;
    text-align: center;
    margin: 0.5rem 0;
}
nav a, .btn {
    background-color: #ffffff;
    border-radius: 0.2em;
    border: 0.01em solid #0275d8;
    display: inline-block;
    padding: 0.25em 1em;
    font-family: "Roboto", sans-serif;
    font-weight: 300;
    font-size: 1rem;
}
nav a:hover, button:hover, input[type="submit"]:hover, .btn:hover  {
    background-color: #0275d8;
    color: #fff;
    text-decoration: none;
}
label, input[type="text"], textarea, pre {
    display: inline-block;
    width: 100%;
    padding: 0.5em;
    font-size: 1rem;
    box-sizing : border-box;
}
p, pre, ul { margin-top: 0; }
a:link, a:visited { color: #0275d8; text-decoration: none; }
a:hover { text-decoration: underline; }
a.active { background-color: #2295f8; color: #ffffff; }
a.active:hover { background-color: #2295f8; }
.rhs { text-align: right; }
.center { text-align: center; }
.alert { padding: 0.5em; text-align: center; border-radius: 0.2em; }
.success { background-color: #dff0d8; border-color: #d0e9c6; color: #3c763d; }
.danger { background-color: #f2dede; border-color: #ebcccc; color: #a94442; }
@media (max-width: 46rem) { body { width: 92%; } }
        </style>';
    }

    public function log() : string
    {
        if ($this->g->in['l']) {
            list($lvl, $msg) = explode(':', $this->g->in['l']);
            return '
      <p class="alert ' . $lvl . '">' . $msg . '</p>';
        }
        return '';
    }

    private function nav1() : string
    {
        $m = '?m='.$this->g->in['m'];
        return '
      <nav>' . join('', array_map(function ($n) use ($m) {
            $c = $m === $n[1] ? ' class="active"' : '';
            return '
        <a' . $c . ' href="' . $n[1] . '">' . $n[0] . '</a>';
        }, $this->g->nav1)) . '
      </nav>';
    }

    private function head() : string
    {
        return '
    <header>
      <h1>' . $this->g->out['head'] . '</h1>' . $this->g->out['nav1'] . '
    </header>';
    }

    private function main() : string
    {
        return '
    <main>' . $this->g->out['main'] . '
    </main>';
    }

    private function foot() : string
    {
        return '
    <footer>
      <p><em><small>' . $this->g->out['foot'] . '</small></em></p>
    </footer>';
    }

    private function html() : string
    {
        extract($this->g->out, EXTR_SKIP);
        return '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>' . $doc . '</title>' . $css . '
  </head>
  <body>' . $head . $log . $main . $foot . '
  </body>
</html>
';
    }
}

class Plugin
{
    private
    $buf = '',
    $in  = [];

    public function __construct($g)
    {
        $this->g = $g;
    }

    public function __toString() : string
    {
        return $this->buf;
    }

    public function create() : string
    {
        return "Plugin::create() not implemented yet!";
    }

    public function read() : string
    {
        return "Plugin::read() not implemented yet!";
    }

    public function update() : string
    {
        return "Plugin::update() not implemented yet!";
    }

    public function delete() : string
    {
        return "Plugin::delete() not implemented yet!";
    }

}

class Home extends Plugin
{
    public function read() : string
    {
        $this->g->nav1 = array_merge($this->g->nav1, [
            ['Project Page', 'https://github.com/markc/spe/tree/master/02-Styled'],
            ['Issue Tracker', 'https://github.com/markc/spe/issues'],
        ]);
        return '
      <h2>Home</h2>
      <p>
This is an ultra simple single-file PHP7 framework and template system example.
Comments and pull requests are most welcome via the Issue Tracker link above.
      </p>';
    }
}

class About extends Plugin
{
    public function read() : string
    {
        return '
      <h2>About</h2>
      <p>
This is an example of a simple PHP7 "framework" to provide the core
structure for further experimental development with both the framework
design and some of the new features of PHP7.
      </p>
      <form method="post">
        <p>
          <a class="btn success" href="?o=about&l=success:Howdy, all is okay.">Success Message</a>
          <a class="btn danger" href="?o=about&l=danger:Houston, we have a problem.">Danger Message</a>
          <a class="btn" href="#" onclick="ajax(\'1\')">JSON</a>
          <a class="btn" href="#" onclick="ajax(\'\')">HTML</a>
          <a class="btn" href="#" onclick="ajax(\'foot\')">FOOT</a>
        </p>
      </form>
      <pre id="dbg"></pre>
      <script>
function ajax(a) {
  if (window.XMLHttpRequest)  {
    var x = new XMLHttpRequest();
    x.open("POST", "", true);
    x.onreadystatechange = function() {
      if (x.readyState == 4 && x.status == 200) {
        document.getElementById("dbg").innerHTML = x.responseText
          .replace(/</g,"&lt;")
          .replace(/>/g,"&gt;")
          .replace(/\\\n/g,"\n")
          .replace(/\\\/g,"");
    }}
    x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    x.send("o=about&x="+a);
    return false;
  }
}
      </script>';
    }
}

class Contact extends Plugin
{
    public function read() : string
    {
        return '
      <h2>Email Contact Form</h2>
      <form id="contact-send" method="post" onsubmit="return mailform(this);">
        <p><input id="subject" required="" type="text" placeholder="Message Subject"></p>
        <p><textarea id="message" rows="9" required=""placeholder="Message Content"></textarea></p>
        <p class="rhs">
          <small>(Note: Doesn\'t seem to work with Firefox 50.1)</small>
          <input class="btn" type="submit" id="send" value="Send">
        </p>
      </form>
      <script>
function mailform(form) {
    location.href = "mailto:' . $this->g->email . '"
        + "?subject=" + encodeURIComponent(form.subject.value)
        + "&body=" + encodeURIComponent(form.message.value);
    form.subject.value = "";
    form.message.value = "";
    alert("Thank you for your message. We will get back to you as soon as possible.");
    return false;
}
      </script>';
    }
}

echo new Init(new class
{
    public
    $email = 'markc@renta.net',
    $in = [
        'l'     => '',      // Log (message)
        'm'     => 'read',  // Method (action)
        'o'     => 'home',  // Object (content)
        'x'     => '',      // XHR (request)
    ],
    $out = [
        'doc'   => 'SPE::03',
        'css'   => '',
        'log'   => '',
        'nav1'  => '',
        'head'  => 'Plugins',
        'main'  => 'Error: missing page!',
        'foot'  => 'Copyright (C) 2015 Mark Constable (AGPL-3.0)',
    ],
    $nav1 = [
        ['Home', '?o=home'],
        ['About', '?o=about'],
        ['Contact', '?o=contact'],
    ];
});
