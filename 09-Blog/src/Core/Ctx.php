<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

use SPE\App\Acl;
use SPE\App\Db;
use SPE\App\QueryType;
use SPE\App\Util;

final class Ctx
{
    public array $in;
    public array $nav;
    public Db $db;
    public int $perp = 6; // Items per page

    public function __construct(
        public string $email = 'noreply@localhost',
        array $in = ['o' => 'Blog', 'm' => 'list', 'x' => '', 'i' => 0, 'g' => 0],
        public array $out = ['doc' => 'SPE::09', 'page' => '09 Blog', 'head' => '', 'main' => '', 'foot' => '', 'css' => '', 'js' => '', 'end' => ''],
        public array $colors = [['circle', 'Stone', 'default'], ['waves', 'Ocean', 'ocean'], ['trees', 'Forest', 'forest'], ['sunset', 'Sunset', 'sunset']],
    ) {
        session_status() === PHP_SESSION_NONE && session_start();

        // Process flash message from URL
        if (isset($_GET['l']) && $_GET['l']) {
            Util::log(htmlspecialchars($_GET['l']), $_GET['lt'] ?? 'success');
        }

        // Only 'o' is sticky; 'm' resets each request
        $this->in = [
            'o' => $this->ses('o', $in['o']),
            'm' => ($_REQUEST['m'] ?? $in['m']) |> trim(...) |> htmlspecialchars(...),
            'x' => ($_REQUEST['x'] ?? $in['x']) |> trim(...) |> htmlspecialchars(...),
            'i' => (int) ($_REQUEST['i'] ?? $in['i']),
            'g' => (int) ($_REQUEST['g'] ?? $in['g']),
        ];

        // Initialize database and build navigation
        $this->db = new Db('blog');
        $this->nav = $this->buildNav();

        // Set email from hostname
        $hostname = trim(`hostname -f 2>/dev/null`) ?: 'localhost';
        $this->email = "noreply@{$hostname}";
    }

    private function buildNav(): array
    {
        $base = preg_match('#^/(\d{2}-[^/]+)/#', $_SERVER['SCRIPT_NAME'] ?? '', $m) ? "/{$m[1]}" : '';

        // Map emoji icons to Lucide icon names
        $iconMap = ['🏠' => 'home', '📋' => 'book-open', '✉️' => 'mail', '📰' => 'newspaper', '📝' => 'edit', '📄' => 'file-text', '📚' => 'library'];

        // Base navigation from pages table
        $pages = array_map(
            fn($r) => [$iconMap[$r['icon']] ?? 'file-text', $r['title'], "$base/" . $r['slug']],
            $this->db->read('posts', 'title,slug,icon', "type='page' ORDER BY id", [], QueryType::All),
        );
        $pages[] = ['newspaper', 'Blog', "$base/blog"];

        // Admin links (role-based)
        if (Acl::check(Acl::Admin)) {
            $pages[] = ['users', 'Users', '?o=Users'];
            $pages[] = ['file-text', 'Posts', '?o=Posts'];
            $pages[] = ['tags', 'Categories', '?o=Categories'];
        }

        return $pages;
    }

    public function ses(string $k, mixed $v = ''): mixed
    {
        return $_SESSION[$k] = isset($_REQUEST[$k])
            ? (is_array($_REQUEST[$k]) ? $_REQUEST[$k] : (trim($_REQUEST[$k]) |> htmlspecialchars(...)))
            : $_SESSION[$k] ?? $v;
    }
}
