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
    public array $out;
    public array $nav;
    public Db $db;

    // Centralized config
    public int $perp = 10; // Items per page

    public function __construct(
        public string $email = 'noreply@localhost',
        array $out = [
            'doc' => 'SPE::09',
            'head' => '',
            'main' => '',
            'foot' => '',
            'css' => '',
            'js' => '',
            'end' => '',
        ],
        public array $themes = [
            ['layout-template', 'Simple',  'Simple'],
            ['navigation',      'TopNav',  'TopNav'],
            ['panel-left',      'SideBar', 'SideBar'],
        ],
    ) {
        session_status() === PHP_SESSION_NONE && session_start();

        // Process flash message from URL
        if (isset($_GET['l']) && $_GET['l']) {
            Util::log(htmlspecialchars($_GET['l']), $_GET['lt'] ?? 'success');
        }

        // Input parameters
        $this->in = [
            'o' => $this->ses('o', 'Blog'),
            'm' => $_REQUEST['m'] ?? 'list',
            't' => $this->ses('t', 'Simple'),
            'x' => $_REQUEST['x'] ?? '',
            'i' => (int) ($_REQUEST['i'] ?? 0),
            'g' => (int) ($_REQUEST['g'] ?? 0), // Category filter
        ];
        $this->out = $out;

        // Initialize database and build navigation
        $this->db = new Db('blog');
        $this->nav = $this->buildNav();

        // Set email from hostname
        $hostname = trim(`hostname -f 2>/dev/null`) ?: 'localhost';
        $this->email = "noreply@{$hostname}";
    }

    private function buildNav(): array
    {
        // Detect base path for root router compatibility
        $base = preg_match('#^/(\d{2}-[^/]+)/#', $_SERVER['SCRIPT_NAME'] ?? '', $m) ? "/{$m[1]}" : '';

        // Base navigation from pages table
        $pages = array_map(
            static fn($r) => [trim(($r['icon'] ?? '') . ' ' . $r['title']), "$base/" . $r['slug']],
            $this->db->read('posts', 'id,title,slug,icon', "type='page' ORDER BY id", [], QueryType::All),
        );
        $pages[] = ['📝 Blog', "$base/blog"];

        // Role-based additions
        $acl = Acl::current();

        if ($acl->can(Acl::Admin)) {
            $pages[] = ['👥 Users', '?o=Users'];
            $pages[] = ['📝 Posts', '?o=Blog&edit'];
            $pages[] = ['🏷️ Categories', '?o=Categories'];
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
