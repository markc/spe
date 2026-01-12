<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Core;

use SPE\App\Db;
use SPE\App\QueryType;

final class Ctx
{
    public array $in;
    public array $out;
    public array $nav;
    public Db $db;

    public function __construct(
        public string $email = 'mc@netserva.org',
        array $in = ['o' => 'Home', 'm' => 'list', 't' => 'Simple', 'x' => '', 'id' => 0],
        array $out = ['doc' => 'SPE::07', 'head' => '', 'main' => '', 'foot' => ''],
        public array $themes = [
            ['🎨 Simple',  'Simple'],
            ['🎨 TopNav',  'TopNav'],
            ['🎨 SideBar', 'SideBar'],
        ],
    ) {
        session_status() === PHP_SESSION_NONE && session_start();

        // Sticky parameters: URL overrides session, session persists across requests
        $this->in = array_map($this->ses(...), array_keys($in), $in)
            |> (static fn($v) => array_combine(array_keys($in), $v));
        $this->out = $out;

        // Initialize database and build navigation from pages
        $this->db = new Db('blog');
        $this->nav = array_map(
            static fn($r) => [trim(($r['icon'] ?? '') . ' ' . $r['title']), ucfirst($r['slug'])],
            $this->db->read('posts', 'id,title,slug,icon', "type='page' ORDER BY id", [], QueryType::All),
        );
        $this->nav[] = ['📝 Blog', 'Blog'];
    }

    // Get/set session value: URL param overrides, else use session, else use default
    public function ses(string $k, mixed $v = ''): mixed
    {
        return $_SESSION[$k] = isset($_REQUEST[$k])
            ? (is_array($_REQUEST[$k]) ? $_REQUEST[$k] : (trim($_REQUEST[$k]) |> htmlspecialchars(...)))
            : $_SESSION[$k] ?? $v;
    }

    // Flash message: set message, retrieve once, then clear
    public function flash(string $k, ?string $msg = null): ?string
    {
        if ($msg !== null) {
            $_SESSION["_flash_{$k}"] = $msg;
            return $msg;
        }
        $val = $_SESSION["_flash_{$k}"] ?? null;
        unset($_SESSION["_flash_{$k}"]);
        return $val;
    }
}
