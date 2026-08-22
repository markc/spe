<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Core;

final readonly class Ctx
{
    public array $in;
    public string $token;

    public function __construct(
        public array $out = ['doc' => 'SPE::06', 'page' => '06 Session', 'main' => ''],
        public array $nav = [['home', 'Home', 'Home'], ['book-open', 'About', 'About'], ['mail', 'Contact', 'Contact']],
        public array $schemes = [['circle', 'Stone', 'default'], ['waves', 'Ocean', 'ocean'], ['trees', 'Forest', 'forest'], ['sunset', 'Sunset', 'sunset']],
        public string $email = 'mc@netserva.org',
    ) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
                'cookie_secure' => !empty($_SERVER['HTTPS']),
            ]);
        }
        $this->token = $_SESSION['token'] ??= bin2hex(random_bytes(16));
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

    /**
     * The submitted form, but only for a genuine POST carrying this session's
     * CSRF token. Any other request — including a POST with a wrong or missing
     * token — returns null, so a model can guard every write with one line.
     */
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

    /** Returns the queued flash messages and clears them. */
    public function takeFlash(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }
}
