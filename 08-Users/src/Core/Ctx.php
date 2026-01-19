<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Users\Core;

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

    // Centralized config (from HCP pattern)
    public int $perp = 10; // Items per page

    public function __construct(
        public string $email = 'mc@netserva.org',
        array $out = [
            'doc' => 'SPE::08',
            'head' => '',
            'main' => '',
            'foot' => '',
            'css' => '',
            'js' => '',
            'end' => '',
        ],
        public array $themes = [
            ['🎨 Simple',  'Simple'],
            ['🎨 TopNav',  'TopNav'],
            ['🎨 SideBar', 'SideBar'],
        ],
    ) {
        session_status() === PHP_SESSION_NONE && session_start();

        // Process flash message from URL (l parameter, from HCP pattern)
        if (isset($_GET['l']) && $_GET['l']) {
            Util::log(htmlspecialchars($_GET['l']), $_GET['lt'] ?? 'success');
        }

        // Input parameters (extended from HCP pattern)
        $this->in = [
            'o' => $this->ses('o', 'Blog'), // Object/plugin
            'm' => $_REQUEST['m'] ?? 'list', // Method/action
            't' => $this->ses('t', 'Simple'), // Theme
            'x' => $_REQUEST['x'] ?? '', // Output format (json, text, {key})
            'i' => (int) ($_REQUEST['i'] ?? 0), // Item ID
            'g' => (int) ($_REQUEST['g'] ?? 0), // Group/category filter
        ];
        $this->out = $out;

        // Initialize database and build role-based navigation
        $this->db = new Db('blog');
        $this->nav = $this->buildNav();
    }

    // Build navigation based on user role (from HCP pattern)
    private function buildNav(): array
    {
        // Base navigation from pages table (clean URLs)
        $pages = array_map(
            static fn($r) => [trim(($r['icon'] ?? '') . ' ' . $r['title']), '/' . $r['slug']],
            $this->db->read('posts', 'id,title,slug,icon', "type='page' ORDER BY id", [], QueryType::All),
        );
        $pages[] = ['📝 Blog', '/blog'];

        // Role-based additions
        $acl = Acl::current();

        if ($acl->can(Acl::User)) {
            // Authenticated users get profile link
        }

        if ($acl->can(Acl::Admin)) {
            // Admins get management links
            $pages[] = ['👥 Users', '?o=Users'];
            $pages[] = ['📝 Posts', '?o=Blog&edit'];
        }

        return $pages;
    }

    // Get/set session value: URL param overrides, else use session, else use default
    public function ses(string $k, mixed $v = ''): mixed
    {
        return $_SESSION[$k] = isset($_REQUEST[$k])
            ? (is_array($_REQUEST[$k]) ? $_REQUEST[$k] : (trim($_REQUEST[$k]) |> htmlspecialchars(...)))
            : $_SESSION[$k] ?? $v;
    }
}
