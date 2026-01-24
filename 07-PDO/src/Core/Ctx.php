<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Core;

use SPE\App\Db;
use SPE\App\QueryType;

final class Ctx
{
    public array $in;
    public array $nav;
    public Db $db;

    public function __construct(
        public string $email = 'mc@netserva.org',
        array $in = ['o' => 'Home', 'm' => 'list', 'x' => '', 'id' => 0],
        public array $out = ['doc' => 'SPE::07', 'page' => '← 07 PDO', 'head' => '', 'main' => '', 'foot' => ''],
        public array $colors = [['circle', 'Stone', 'default'], ['waves', 'Ocean', 'ocean'], ['trees', 'Forest', 'forest'], ['sunset', 'Sunset', 'sunset']],
    ) {
        session_status() === PHP_SESSION_NONE && session_start();

        // Only 'o' (plugin) is sticky; 'm' defaults to 'list' each request
        $this->in = [
            'o' => $this->ses('o', $in['o']),
            'm' => ($_REQUEST['m'] ?? $in['m']) |> trim(...) |> htmlspecialchars(...),
            'x' => ($_REQUEST['x'] ?? $in['x']) |> trim(...) |> htmlspecialchars(...),
            'id' => (int) ($_REQUEST['id'] ?? $in['id']),
        ];

        // Initialize database and build navigation from pages
        $this->db = new Db('blog');
        $pages = $this->db->read('posts', 'title,slug,icon', "type='page' ORDER BY id", [], QueryType::All);
        // Map emoji icons to Lucide icon names
        $iconMap = ['🏠' => 'home', '📋' => 'book-open', '✉️' => 'mail', '📰' => 'newspaper', '📝' => 'edit', '📄' => 'file-text', '📚' => 'library'];
        $this->nav = array_map(
            static fn($r) => [$iconMap[$r['icon']] ?? 'file-text', $r['title'], ucfirst($r['slug'])],
            $pages,
        );
        $this->nav[] = ['newspaper', 'Blog', 'Blog'];
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
