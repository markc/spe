<?php declare(strict_types=1);

// Created: 20150101 - Updated: 20250206
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

echo new class {
    private const DEFAULT_PAGE = 'home';
    
    private readonly array $nav;

    private array $out = [
        'doc'   => 'SPE::01',
        'nav'   => '',
        'head'  => 'Simple PHP Example',
        'main'  => '<p>Error: missing page!</p>',
        'foot'  => 'Copyright © 2015-2025 Mark Constable (AGPL-3.0)',
    ];

    public function __construct()
    {
        $this->nav = [
            ['Home', 'home'],
            ['About', 'about'],
            ['Contact', 'contact']
        ];
        
        $page = filter_var(trim($_REQUEST['m'] ?? '', '/'), FILTER_SANITIZE_URL);
        $method = empty($page) ? self::DEFAULT_PAGE : $page;
        
        $this->out['main'] = method_exists($this, $method) ? $this->{$method}() : $this->out['main'];
        
        foreach ($this->out as $key => $value) {
            if (method_exists($this, $key)) {
                $this->out[$key] = $this->{$key}();
            }
        }
    }

    public function __toString(): string
    {
        return $this->html();
    }

    private function nav(): string 
    {
        $links = array_map(
            fn($n) => '            <li>
                        <a href="?m=' . $n[1] . '" rel="noopener">' . $n[0] . '</a>
                    </li>',
            $this->nav
        );
        return '
            <nav aria-label="Main navigation">
                <ul>
        ' . implode('
        ', $links) . '
                </ul>
            </nav>';
    }

    private function head(): string
    {
        return '
        <header>
            <h1>' . $this->out['head'] . '</h1>' . $this->out['nav'] . '
        </header>';
    }

    private function main(): string
    {
        return '

        <main>
            ' . $this->out['main'] . '
        </main>';
    }

    private function foot(): string
    {
        return '

        <footer>
            <p>
                <small>' . $this->out['foot'] . '</small>    
            </p>
        </footer>';
    }

    private function html(): string 
    {
        return '<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Simple PHP Example">
        <meta name="author" content="Mark Constable">
        <title>' . $this->out['doc'] . '</title>
        <link rel="icon" href="favicon.ico">
    </head>
    <body>' . $this->out['head'] . $this->out['main'] . $this->out['foot'] . '
    </body>
</html>';
    }

    private function home(): string
    {
        return '<h2>Home Page</h2>
            <p>
                Lorem ipsum home.
            </p>';
    }

    private function about(): string
    {
        return '<h2>About Page</h2>
            <p>
                Lorem ipsum about.
            </p>';
    }

    private function contact(): string
    {
        return '<h2>Contact Page</h2>
            <p>
                Lorem ipsum contact.
            </p>';
    }
};
