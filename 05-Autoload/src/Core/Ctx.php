<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Core;

final readonly class Ctx
{
    public array $in;

    public function __construct(
        public array $out = ['doc' => 'SPE::05', 'page' => '05 Autoload', 'main' => ''],
        public array $nav = [['home', 'Home', 'Home'], ['book-open', 'About', 'About'], ['mail', 'Contact', 'Contact']],
        public array $schemes = [['circle', 'Stone', 'default'], ['waves', 'Ocean', 'ocean'], ['trees', 'Forest', 'forest'], ['sunset', 'Sunset', 'sunset']],
        public string $email = 'mc@netserva.org',
    ) {
        $this->in = [
            'o' => self::get('o', 'Home', '/^[A-Z][A-Za-z]{0,31}$/'),
            'm' => self::get('m', 'list', '/^(create|read|update|delete|list)$/'),
            'x' => self::get('x', '', '/^json$/'),
        ];
    }

    private static function get(string $key, string $default, string $pattern): string
    {
        $v = $_GET[$key] ?? '';
        return is_string($v) && preg_match($pattern, $v) ? $v : $default;
    }
}
