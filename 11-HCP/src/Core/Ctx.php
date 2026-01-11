<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Core;

use SPE\App\{Acl, Util};

final class Ctx
{
    public array $in;
    public array $out;
    public array $nav;
    public HcpDb $db;

    public function __construct(
        public string $email = 'noreply@localhost',
        array $out = ['doc' => 'HCP', 'head' => '', 'main' => '', 'foot' => '', 'css' => '', 'js' => '', 'end' => ''],
        public array $themes = [['TopNav', 'TopNav'], ['SideBar', 'SideBar']]
    ) {
        session_status() === PHP_SESSION_NONE && session_start();

        // Process flash message from URL
        if (isset($_GET['l']) && $_GET['l']) {
            Util::log(htmlspecialchars($_GET['l']), $_GET['lt'] ?? 'success');
        }

        // Input parameters - HCP defaults to System dashboard
        $this->in = [
            'o' => $this->ses('o', 'System'),
            'm' => $_REQUEST['m'] ?? 'list',
            't' => $this->ses('t', 'TopNav'),
            'x' => $_REQUEST['x'] ?? '',
            'i' => (int)($_REQUEST['i'] ?? 0),
        ];
        $this->out = $out;

        // Initialize HCP database for auth
        $this->db = new HcpDb();
        $this->nav = $this->buildNav();

        // Set email from hostname
        $hostname = trim(shell_exec('hostname -f 2>/dev/null') ?? '') ?: 'localhost';
        $this->email = "noreply@{$hostname}";
    }

    private function buildNav(): array
    {
        // HCP navigation - all require admin access
        $nav = [];

        $acl = Acl::current();
        if ($acl->can(Acl::Admin)) {
            $nav = [
                ['📊 Dashboard', '?o=System'],
                ['🌐 Vhosts', '?o=Vhosts'],
                ['📧 Mail', '?o=Vmails'],
                ['🔗 DNS', '?o=Vdns'],
                ['🔒 SSL', '?o=Ssl'],
                ['📈 Stats', '?o=Stats'],
            ];
        }

        return $nav;
    }

    public function ses(string $k, mixed $v = ''): mixed
    {
        return $_SESSION[$k] = isset($_REQUEST[$k])
            ? (is_array($_REQUEST[$k]) ? $_REQUEST[$k] : trim($_REQUEST[$k]) |> htmlspecialchars(...))
            : ($_SESSION[$k] ?? $v);
    }
}
