<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Core;

final readonly class Ctx
{
    public array $in;
    public string $token;
    public Db $db;

    public function __construct(
        public array $out = ['doc' => 'SPE::07', 'page' => '07 PDO', 'main' => ''],
        public array $nav = [['home', 'Home', 'Home'], ['book-open', 'About', 'About'], ['file-text', 'Posts', 'Posts']],
        public array $schemes = [['circle', 'Stone', 'default'], ['waves', 'Ocean', 'ocean'], ['trees', 'Forest', 'forest'], ['sunset', 'Sunset', 'sunset']],
        public string $email = 'mc@netserva.org',
    ) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
                'cookie_secure' => (($_SERVER['HTTPS'] ?? 'off') !== 'off'),
            ]);
        }
        $this->token = $_SESSION['token'] ??= bin2hex(random_bytes(16));
        $this->db = new Db(getenv('SPE_DB') ?: __DIR__ . '/../../data/spe.db', __DIR__ . '/../../schema.sql');
        $this->in = [
            'o' => self::get('o', 'Home', '/^[A-Z][A-Za-z]{0,31}$/'),
            'm' => self::get('m', 'list', '/^(create|read|update|delete|list)$/'),
            'x' => self::get('x', '', '/^json$/'),
            'i' => (int) ($_GET['i'] ?? $_POST['i'] ?? 0),
        ];
    }

    private static function get(string $key, string $default, string $pattern): string
    {
        $v = $_GET[$key] ?? '';
        return is_string($v) && preg_match($pattern, $v) ? $v : $default;
    }

    public function post(): ?array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }
        if (!hash_equals($this->token, (string) ($_POST['csrf'] ?? ''))) {
            $this->flash(Flash::Danger, 'That form has expired. Please try again.');
            return null;
        }
        return $_POST;
    }

    public function flash(Flash $level, string $message): void
    {
        $_SESSION['flash'][] = [$level->value, $message];
    }

    public function takeFlash(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }
}
