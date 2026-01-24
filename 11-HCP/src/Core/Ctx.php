<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Core;

use SPE\App\Acl;
use SPE\App\Db;
use SPE\App\QueryType;
use SPE\App\Util;

final class Ctx
{
    public array $in;
    public array $nav;
    public Db $db;        // Blog database (posts/pages/categories)
    public HcpDb $hcpDb;  // HCP database (vhosts/vmails/etc)
    public int $perp = 9; // Items per page

    public function __construct(
        public string $email = 'noreply@localhost',
        public array $out = ['doc' => 'HCP', 'page' => 'Hosting Control Panel', 'head' => '', 'main' => '', 'foot' => '', 'css' => '', 'js' => '', 'end' => ''],
        public array $colors = [['circle', 'Stone', 'default'], ['waves', 'Ocean', 'ocean'], ['trees', 'Forest', 'forest'], ['sunset', 'Sunset', 'sunset']],
    ) {
        session_status() === PHP_SESSION_NONE && session_start();

        // Process flash message from URL
        if (isset($_GET['l']) && $_GET['l']) {
            Util::log(htmlspecialchars($_GET['l']), $_GET['lt'] ?? 'success');
        }

        // Input parameters
        $this->in = [
            'o' => $this->ses('o', 'Home'),
            'm' => ($_REQUEST['m'] ?? 'list') |> trim(...) |> htmlspecialchars(...),
            'x' => ($_REQUEST['x'] ?? '') |> trim(...) |> htmlspecialchars(...),
            'i' => (int) ($_REQUEST['i'] ?? 0),
        ];

        // Initialize databases
        $this->db = new Db('blog');     // Posts, pages, categories
        $this->hcpDb = new HcpDb();     // Vhosts, vmails, etc

        // Build navigation
        $this->nav = $this->buildNav();

        // Set email from hostname
        $hostname = trim(shell_exec('hostname -f 2>/dev/null') ?? '') ?: 'localhost';
        $this->email = "noreply@{$hostname}";
    }

    private function buildNav(): array
    {
        $base = preg_match('#^/(\d{2}-[^/]+)/#', $_SERVER['SCRIPT_NAME'] ?? '', $m) ? "/{$m[1]}" : '';

        // Map stored icons to Lucide names (for legacy emoji support)
        $iconMap = ['🏠' => 'home', '📋' => 'book-open', '✉️' => 'mail', '📰' => 'newspaper', '📝' => 'file-text', '📄' => 'file-text', '📚' => 'library'];

        // Base navigation from pages (visible to all)
        $pages = array_map(
            fn($r) => [$iconMap[$r['icon']] ?? ($r['icon'] ?: 'file-text'), $r['title'], "$base/" . $r['slug']],
            $this->db->read('posts', 'title,slug,icon', "type='page' ORDER BY id", [], QueryType::All),
        );

        // Blog link (visible to all)
        $pages[] = ['newspaper', 'Blog', "$base/blog"];

        // Docs link (visible to all)
        $pages[] = ['library', 'Docs', '?o=Docs'];

        // Blog admin links (Admin+)
        if (Acl::check(Acl::Admin)) {
            $pages[] = ['file-text', 'Posts', '?o=Posts'];
            $pages[] = ['tags', 'Categories', '?o=Categories'];
        }

        // HCP admin links (SuperAdmin only)
        if (Acl::check(Acl::SuperAdmin)) {
            $pages[] = ['users', 'Users', '?o=Users'];
            $pages[] = ['layout-dashboard', 'System', '?o=System'];
            $pages[] = ['globe', 'Vhosts', '?o=Vhosts'];
            $pages[] = ['mail', 'Mail', '?o=Vmails'];
            $pages[] = ['at-sign', 'Aliases', '?o=Valias'];
            $pages[] = ['network', 'DNS', '?o=Vdns'];
            $pages[] = ['shield-check', 'SSL', '?o=Ssl'];
            $pages[] = ['bar-chart-3', 'Stats', '?o=Stats'];
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
