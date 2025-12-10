<?php declare(strict_types=1);
// Created: 20150101 - Updated: 20251209
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

echo new class {
    private const string TITLE = 'Simple PHP Examples';                         // PHP 8.3 typed constant

    private array $chapters = [                                                 // Chapter directory mappings
        '01-Simple'   => '01-Simple',
        '02-Styled'   => '02-Styled',
        '03-Plugins'  => '03-Plugins',
        '04-Themes'   => '04-Themes',
        '05-Autoload' => '05-Autoload/public',
        '06-Session'  => '06-Session/public',
        '07-PDO'      => '07-PDO/public',
        '08-Users'    => '08-Users/public',
        '09-Blog'     => '09-Blog/public',
    ];

    public function __toString(): string
    {
        $title = self::TITLE;
        $nav = $this->chapters                                                  // PHP 8.5 pipe operator
            |> array_keys(...)                                                  // PHP 8.1 first-class callable
            |> (fn($keys) => array_map(
                fn($k) => "<li><a href=\"{$this->chapters[$k]}\">$k</a></li>",
                $keys
            ))
            |> (fn($items) => implode("\n                    ", $items));

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="color-scheme" content="light dark">
            <title>SPE::00</title>
            <link rel="stylesheet" href="/spe.css">
        </head>
        <body>
            <div class="container">
                <header>
                    <h1>⚙️ $title</h1>
                    <p>A progressive PHP 8.5 micro-framework tutorial in 10 chapters.</p>
                </header>
                <nav>
                    <ul>
                    $nav
                    </ul>
                </nav>
                <main class="card">
                    <p>Each chapter builds on the previous, demonstrating modern PHP 8.x features:</p>
                    <ul>
                        <li><strong>PHP 8.1:</strong> First-class callables, readonly properties, enums</li>
                        <li><strong>PHP 8.3:</strong> Typed class constants, #[Override] attribute</li>
                        <li><strong>PHP 8.4:</strong> Asymmetric visibility, property hooks</li>
                        <li><strong>PHP 8.5:</strong> Pipe operator for functional transformations</li>
                    </ul>
                </main>
                <footer class="text-center mt-3">
                    <small>© 2015-2025 Mark Constable (MIT License)</small>
                </footer>
            </div>
            <script src="/spe.js"></script>
        </body>
        </html>
        HTML;
    }
};
