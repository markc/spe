<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

echo new class {
    private const string DEFAULT = 'home';

    private const array PAGES = [
        'home'    => ['Home', '<h2>Home</h2><p>This is the <b>Home</b> page.</p>'],
        'about'   => ['About', '<h2>About</h2><p>This is the <b>About</b> page.</p>'],
        'contact' => ['Contact', '<h2>Contact</h2><p>This is the <b>Contact</b> page.</p>'],
    ];

    public private(set) string $page;
    public private(set) string $main;

    public function __construct()
    {
        $o = $_GET['o'] ?? '';
        $this->page = (is_string($o) ? $o : '')
            |> trim(...)
            |> strtolower(...)
            |> (static fn(string $p) => $p === '' ? self::DEFAULT : $p);

        if (!isset(self::PAGES[$this->page])) {
            http_response_code(404);
        }
        $this->main = self::PAGES[$this->page][1] ?? '<h2>Not found</h2><p>There is no such page.</p>';
    }

    public function __toString(): string
    {
        $nav = self::PAGES
            |> array_keys(...)
            |> (fn(array $keys) => array_map(fn(string $k) => sprintf(
                '<a href="?o=%s"%s>%s</a>', $k, $k === $this->page ? ' class="active"' : '', self::PAGES[$k][0]
            ), $keys))
            |> (static fn(array $links) => implode(' | ', $links));

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>SPE::01</title>
    <style>body{margin:auto;width:25rem;text-align:center}a{text-decoration:none}a.active{font-weight:bold}hr{margin:0}</style>
</head>
<body>
    <header><h1><a href="../">« Simple PHP Engine</a></h1><hr><nav>{$nav}</nav><hr></header>
    <main>{$this->main}</main>
    <footer><small><em>Copyright © 2015-2026 Mark Constable (MIT License)</em></small></footer>
</body>
</html>
HTML;
    }
};
