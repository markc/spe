<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

final readonly class Ctx
{
    public array $in;
    public string $token;
    public Db $db;
    public ?User $user;
    public array $nav;

    public function __construct(
        public array $out = ['doc' => 'SPE::09', 'page' => '09 Blog', 'main' => ''],
        public array $schemes = [['circle', 'Stone', 'default'], ['waves', 'Ocean', 'ocean'], ['trees', 'Forest', 'forest'], ['sunset', 'Sunset', 'sunset']],
        public string $email = 'mc@netserva.org',
        public int $perPage = 5,
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
        $this->db = new Db(__DIR__ . '/../../data/spe.db', __DIR__ . '/../../schema.sql');
        $this->user = $this->restore();
        $this->nav = $this->buildNav();
        $this->in = [
            'o' => self::get('o', 'Blog', '/^[A-Z][A-Za-z]{0,31}$/'),
            'm' => self::get('m', 'list', '/^(create|read|update|delete|list)$/'),
            'x' => self::get('x', '', '/^json$/'),
            'i' => (int) ($_GET['i'] ?? $_POST['i'] ?? 0),
            'page' => max(1, (int) ($_GET['page'] ?? 1)),
            'tag' => self::get('tag', '', '/^[a-z0-9-]{1,50}$/'),
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

    public function role(): Role
    {
        return $this->user->role ?? Role::Anon;
    }

    private function buildNav(): array
    {
        $nav = [['home', 'Home', 'Home'], ['newspaper', 'Blog', 'Blog'], ['book-open', 'Docs', 'Docs']];
        if ($this->role()->can(Role::Admin)) {
            $nav[] = ['tag', 'Tags', 'Tags'];
            $nav[] = ['users', 'Users', 'Users'];
        }
        return $nav;
    }

    // === Session lifecycle ===

    public function login(User $user, bool $remember): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = $user->id;
        if ($remember) {
            $this->issueRemember($user->id);
        }
    }

    public function logout(): void
    {
        $this->clearRemember();
        $_SESSION = [];
        session_regenerate_id(true);
    }

    private function restore(): ?User
    {
        $uid = $_SESSION['uid'] ?? $this->restoreRemember();
        if (!$uid) {
            return null;
        }
        $row = $this->db->read('users', 'id, email, name, role', 'id = :id', ['id' => $uid], QueryType::One);
        return $row ? User::fromRow($row) : null;
    }

    private function issueRemember(int $uid): void
    {
        [$selector, $validator] = [bin2hex(random_bytes(9)), bin2hex(random_bytes(32))];
        (void) $this->db->create('user_tokens', [
            'selector' => $selector,
            'validator_hash' => hash('sha256', $validator),
            'user_id' => $uid,
            'expires' => date('Y-m-d H:i:s', time() + 30 * 86400),
        ]);
        setcookie('remember', "$selector:$validator", [
            'expires' => time() + 30 * 86400,
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']),
        ]);
    }

    private function restoreRemember(): ?int
    {
        if (!preg_match('/^([0-9a-f]{18}):([0-9a-f]{64})$/', $_COOKIE['remember'] ?? '', $m)) {
            return null;
        }
        $row = $this->db->read('user_tokens', '*', 'selector = :s', ['s' => $m[1]], QueryType::One);
        if (!$row || $row['expires'] < date('Y-m-d H:i:s') || !hash_equals($row['validator_hash'], hash('sha256', $m[2]))) {
            return null;
        }
        (void) $this->db->delete('user_tokens', 'selector = :s', ['s' => $m[1]]);
        // Same as a fresh login: rotate the token and the session id, then sign in.
        session_regenerate_id(true);
        $this->issueRemember((int) $row['user_id']);
        return $_SESSION['uid'] = (int) $row['user_id'];
    }

    private function clearRemember(): void
    {
        if (preg_match('/^([0-9a-f]{18}):/', $_COOKIE['remember'] ?? '', $m)) {
            (void) $this->db->delete('user_tokens', 'selector = :s', ['s' => $m[1]]);
        }
        setcookie('remember', '', ['expires' => time() - 3600, 'httponly' => true, 'samesite' => 'Lax']);
    }

    // === Flash ===

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
