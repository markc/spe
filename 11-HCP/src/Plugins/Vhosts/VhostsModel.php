<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Plugins\Vhosts;

use SPE\HCP\Core\{Ctx, Plugin};
use SPE\HCP\Lib\{VhostOps, Vhosts};

/**
 * Virtual hosts management - uses lib/Vhosts.php orchestration layer.
 */
final class VhostsModel extends Plugin
{
    private array $in = [
        'domain' => '',
        'uname' => '',
        'ssl' => '1',
        'cms' => '', // wp, drupal, laravel, etc.
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
            $domain = filter_var($this->in['domain'], FILTER_VALIDATE_DOMAIN);
            if (!$domain) {
                return ['error' => 'Invalid domain name'];
            }

            $result = Vhosts::add($domain, $this->in['uname'] ?: null);

            if ($result['success']) {
                header('Location: ?o=Vhosts&m=read&domain=' . urlencode($domain));
                exit;
            }

            return ['error' => $result['error']];
        }

        return [];
    }

    public function read(): array
    {
        $domain = filter_var($this->in['domain'], FILTER_VALIDATE_DOMAIN);
        if (!$domain) {
            return ['error' => 'Invalid domain'];
        }

        $result = Vhosts::show($domain);

        if (!$result['success']) {
            return ['error' => $result['error']];
        }

        return $result;
    }

    public function update(): array
    {
        $domain = filter_var($this->in['domain'], FILTER_VALIDATE_DOMAIN);
        if (!$domain) {
            return ['error' => 'Invalid domain'];
        }

        if ($_POST) {
            $action = $_POST['action'] ?? '';

            if ($action === 'toggle') {
                $active = ($_POST['active'] ?? '0') === '1';
                $result = Vhosts::setActive($domain, $active);
                return $result['success']
                    ? ['success' => ($active ? 'Enabled' : 'Disabled') . " vhost", 'domain' => $domain]
                    : ['error' => $result['error']];
            }

            if ($action === 'aliases' && isset($_POST['aliases'])) {
                $aliases = preg_split('/[,\n]+/', $_POST['aliases'], -1, PREG_SPLIT_NO_EMPTY);
                $result = Vhosts::setAliases($domain, $aliases);
                return $result['success']
                    ? ['success' => 'Aliases updated', 'domain' => $domain]
                    : ['error' => $result['error']];
            }
        }

        return Vhosts::show($domain);
    }

    public function delete(): array
    {
        $domain = filter_var($this->in['domain'], FILTER_VALIDATE_DOMAIN);
        if (!$domain) {
            return ['error' => 'Invalid domain'];
        }

        if ($_POST && isset($_POST['confirm'])) {
            $result = Vhosts::del($domain);

            if ($result['success']) {
                header('Location: ?o=Vhosts');
                exit;
            }

            return ['error' => $result['error']];
        }

        return ['domain' => $domain, 'confirm_required' => true];
    }

    public function list(): array
    {
        $vhosts = Vhosts::list();

        // Enhance with filesystem info (SSL, enabled status)
        foreach ($vhosts as &$v) {
            $v['ssl'] = file_exists("/etc/ssl/{$v['domain']}/fullchain.pem");
            $v['enabled'] = file_exists("/etc/nginx/sites-enabled/{$v['domain']}");
            $v['size'] = '-'; // Could fetch via SSH if needed
        }

        return ['items' => $vhosts, 'total' => count($vhosts)];
    }

    // Actions
    public function ssl(): array
    {
        $domain = filter_var($this->in['domain'], FILTER_VALIDATE_DOMAIN);
        if (!$domain) {
            return ['error' => 'Invalid domain'];
        }

        if ($_POST) {
            // TODO: Implement SSL via VhostOps when certbot integration is added
            return ['error' => 'SSL installation not yet implemented'];
        }

        return ['domain' => $domain, 'action' => 'ssl'];
    }

    public function nginx(): array
    {
        $domain = filter_var($this->in['domain'], FILTER_VALIDATE_DOMAIN);
        $action = $_POST['action'] ?? 'status'; // enable, disable, restart

        if ($action === 'enable' || $action === 'disable' || $action === 'restart') {
            $result = VhostOps::manage($domain, $action);
            if (!$result['success']) {
                return ['error' => $result['error']];
            }
        }

        return ['domain' => $domain, 'enabled' => file_exists("/etc/nginx/sites-enabled/{$domain}")];
    }
}
