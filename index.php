<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250208
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

echo new class
{
    private readonly array $nav;

    private array $out = [
        'doc'   => 'SPE::00',
        'nav'   => '',
        'head'  => 'Simple PHP Examples',
        'main'  => '<p>This is a super simple set of PHP 8 examples.</p>',
        'foot'  => 'Copyright © 2015-2025 Mark Constable (AGPL-3.0)',
    ];

    public function __construct()
    {
        $this->nav = [
            ['01-Simple',   '01-Simple'],
            ['02-Styled',   '02-Styled'],
            ['03-Plugins',  '03-Plugins'],
            ['04-Themes',   '04-Themes'],
            ['05-Autoload', '05-Autoload'],
            ['06-Session',  '06-Session'],
            ['07-PDO',      '07-PDO'],
            ['08-Users',    '08-Users'],
            ['09-Auth',     '09-Auth'],
            ['10-Files',    '10-Files'],
        ];
    }

    public function __toString(): string
    {
        return $this->html();
    }

    private function nav(): string
    {
        $links = array_map(
            fn($n) => '            <li>
                        <a href="' . $n[1] . '">' . $n[0] . '</a>
                    </li>',
            $this->nav
        );
        return '
            <nav aria-label="Main navigation">
                <ul role="list">
        ' . implode('
        ', $links) . '
                </ul>
            </nav>';
    }

    private function head(): string
    {
        return '
        <header>
            <h1>' . $this->out['head'] . '</h1>' . $this->nav() . '
        </header>';
    }

    private function main(): string
    {
        return '

        <main id="main">
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
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
        <meta http-equiv="Content-Security-Policy" content="default-src \'self\'">
        <meta name="color-scheme" content="light dark">
        <title>' . $this->out['doc'] . '</title>
        <meta name="description" content="Simple PHP Examples">
        <meta name="author" content="Mark Constable">
        <link rel="icon" href="favicon.ico">
    </head>
    <body>' . $this->head() . $this->main() . $this->foot() . '
    </body>
</html>';
    }
};
