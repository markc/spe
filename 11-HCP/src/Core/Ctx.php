<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Core;

use SPE\App\Acl;
use SPE\App\Util;

final class Ctx
{
    public array $in;
    public array $nav;
    public HcpDb $db;

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

        // Input parameters - HCP defaults to System dashboard
        $this->in = [
            'o' => $this->ses('o', 'System'),
            'm' => ($_REQUEST['m'] ?? 'list') |> trim(...) |> htmlspecialchars(...),
            'x' => ($_REQUEST['x'] ?? '') |> trim(...) |> htmlspecialchars(...),
            'i' => (int) ($_REQUEST['i'] ?? 0),
        ];

        // Initialize HCP database
        $this->db = new HcpDb();
        $this->nav = $this->buildNav();

        // Set email from hostname
        $hostname = trim(shell_exec('hostname -f 2>/dev/null') ?? '') ?: 'localhost';
        $this->email = "noreply@{$hostname}";
    }

    private function buildNav(): array
    {
        // HCP navigation - all require admin access
        if (!Acl::check(Acl::Admin)) {
            return [];
        }

        return [
            ['layout-dashboard', 'Dashboard', '?o=System'],
            ['globe', 'Vhosts', '?o=Vhosts'],
            ['mail', 'Mail', '?o=Vmails'],
            ['at-sign', 'Aliases', '?o=Valias'],
            ['network', 'DNS', '?o=Vdns'],
            ['shield-check', 'SSL', '?o=Ssl'],
            ['bar-chart-3', 'Stats', '?o=Stats'],
        ];
    }

    public function ses(string $k, mixed $v = ''): mixed
    {
        return $_SESSION[$k] = isset($_REQUEST[$k])
            ? (is_array($_REQUEST[$k]) ? $_REQUEST[$k] : (trim($_REQUEST[$k]) |> htmlspecialchars(...)))
            : $_SESSION[$k] ?? $v;
    }
}
