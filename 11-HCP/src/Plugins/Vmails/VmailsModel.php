<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Plugins\Vmails;

use SPE\HCP\Core\{Ctx, Plugin};
use SPE\HCP\Lib\Vmail;

/**
 * Virtual mailbox management - uses lib/Vmail.php orchestration layer.
 */
final class VmailsModel extends Plugin
{
    private array $in = [
        'email' => '',
        'password' => '',
        'domain' => '',
    ];

    public function __construct(protected Ctx $ctx)
    {
        parent::__construct($ctx);
        foreach ($this->in as $k => &$v) {
            $v = $_REQUEST[$k] ?? $v;
        }
    }

    public function create(): array
    {
        if ($_POST) {
            $email = filter_var($this->in['email'], FILTER_VALIDATE_EMAIL);
            if (!$email) {
                return ['error' => 'Invalid email address'];
            }

            $result = Vmail::add($email, $this->in['password'] ?: null);

            if ($result['success']) {
                // Store password temporarily to show user
                $_SESSION['new_password'] = $result['password'];
                header('Location: ?o=Vmails&m=read&email=' . urlencode($email));
                exit;
            }

            return ['error' => $result['error']];
        }

        // Get available domains for dropdown
        $domains = $this->getActiveDomains();
        return ['domains' => $domains];
    }

    public function read(): array
    {
        $email = filter_var($this->in['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            return ['error' => 'Invalid email address'];
        }

        $result = Vmail::show($email);

        if (!$result['success']) {
            return ['error' => $result['error']];
        }

        // Check for newly created password
        $newPassword = $_SESSION['new_password'] ?? null;
        unset($_SESSION['new_password']);
        $result['new_password'] = $newPassword;

        return $result;
    }

    public function update(): array
    {
        $email = filter_var($this->in['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            return ['error' => 'Invalid email address'];
        }

        if ($_POST) {
            $action = $_POST['action'] ?? '';

            if ($action === 'password' && !empty($_POST['password'])) {
                $result = Vmail::passwd($email, $_POST['password']);
                return $result['success']
                    ? ['success' => 'Password updated', 'email' => $email]
                    : ['error' => $result['error']];
            }

            if ($action === 'toggle') {
                $active = ($_POST['active'] ?? '0') === '1';
                $result = Vmail::setActive($email, $active);
                return $result['success']
                    ? ['success' => ($active ? 'Enabled' : 'Disabled') . " mailbox", 'email' => $email]
                    : ['error' => $result['error']];
            }
        }

        return ['email' => $email];
    }

    public function delete(): array
    {
        $email = filter_var($this->in['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            return ['error' => 'Invalid email address'];
        }

        if ($_POST && isset($_POST['confirm'])) {
            $result = Vmail::del($email);

            if ($result['success']) {
                header('Location: ?o=Vmails');
                exit;
            }

            return ['error' => $result['error']];
        }

        return ['email' => $email, 'confirm_required' => true];
    }

    public function list(): array
    {
        $domain = $this->in['domain'] ?: null;
        $mailboxes = Vmail::list($domain);

        // Get domains for filter dropdown
        $domains = $this->getActiveDomains();

        return [
            'items' => $mailboxes,
            'total' => count($mailboxes),
            'domains' => $domains,
            'filter_domain' => $domain,
        ];
    }

    private function getActiveDomains(): array
    {
        // Get domains from vhosts table
        try {
            $pdo = new \PDO('sqlite:/srv/.local/sqlite/sysadm.db', null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $stmt = $pdo->query('SELECT domain FROM vhosts WHERE active = 1 ORDER BY domain');
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            return [];
        }
    }
}
