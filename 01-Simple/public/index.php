<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

echo new class {
    private const string DEFAULT = 'home';                                      // PHP 8.3 typed constant
    private array $pages = [
        'home'    => ['Home', '<h2>Home</h2><p>Lorem ipsum home.</p>'],
        'about'   => ['About', '<h2>About</h2><p>Lorem ipsum about.</p>'],
        'contact' => ['Contact', '<h2>Contact</h2><p>Lorem ipsum contact.</p>'],
    ];
    public private(set) string $page;                                           // PHP 8.4 asymmetric visibility
    public private(set) string $main;

    public function __construct() {
        $this->page = ($_REQUEST['m'] ?? '') |> trim(...) |> htmlspecialchars(...) |> (fn($p) => $p ?: self::DEFAULT);
        $this->main = $this->pages[$this->page][1] ?? '<p>Error: page not found</p>';
    }

    public function __toString(): string {
        $nav = $this->pages |> array_keys(...) |> (fn($k) => array_map(                 // PHP 8.1 first-class callable
            fn($p) => "<a href=\"?m=$p\">{$this->pages[$p][0]}</a>", $k)) |> (fn($a) => implode(' | ', $a));
        return <<<HTML
        <!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="color-scheme" content="light dark">
        <title>SPE::01</title><style>a{text-decoration:none}nav{margin:1em 0}</style></head>
        <body><header><h1><a href="/">« Simple PHP Example</a></h1><nav>$nav</nav></header>
        <main>{$this->main}</main>
        <footer><small>© 2015-2025 Mark Constable (MIT License)</small></footer></body></html>
        HTML;
    }
};
