<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Plugins\System;

use SPE\HCP\Core\{Ctx, Plugin, Shell};

/**
 * System overview - dashboard with stats and service status.
 */
final class SystemModel extends Plugin
{
    public function list(): array
    {
        return [
            'stats' => Shell::systemStats(),
            'services' => Shell::services(),
            'hostname' => gethostname(),
            'os' => $this->getOsInfo(),
            'counts' => $this->getCounts(),
        ];
    }

    public function read(): array
    {
        // Detailed view of a specific service
        $service = $_GET['service'] ?? 'nginx';
        $result = Shell::run('systemctl', ['status', $service]);

        return [
            'service' => $service,
            'status' => $result['output'],
            'active' => strpos($result['output'], 'active (running)') !== false,
        ];
    }

    public function service(): array
    {
        // Start/stop/restart a service
        $service = $_POST['service'] ?? '';
        $action = $_POST['action'] ?? 'status'; // start, stop, restart, reload

        if (!in_array($action, ['start', 'stop', 'restart', 'reload'])) {
            return ['error' => 'Invalid action'];
        }

        $allowed = ['nginx', 'php8.3-fpm', 'php8.4-fpm', 'mariadb', 'mysql', 'postfix', 'dovecot', 'redis'];
        if (!in_array($service, $allowed)) {
            return ['error' => 'Service not allowed'];
        }

        $result = Shell::run('systemctl', [$action, $service]);

        return [
            'service' => $service,
            'action' => $action,
            'success' => $result['success'],
            'output' => $result['output'],
        ];
    }

    private function getOsInfo(): array
    {
        $release = file_exists('/etc/os-release')
            ? parse_ini_file('/etc/os-release')
            : [];

        return [
            'name' => $release['PRETTY_NAME'] ?? php_uname('s'),
            'kernel' => php_uname('r'),
            'arch' => php_uname('m'),
        ];
    }

    private function getCounts(): array
    {
        $vhosts = count(glob('/home/u/*', GLOB_ONLYDIR)) - 1; // Exclude sysadm
        $mailboxes = 0;
        $databases = 0;

        // Count mailboxes from /home/u/*/home/*
        foreach (glob('/home/u/*/home', GLOB_ONLYDIR) as $maildir) {
            $mailboxes += count(glob($maildir . '/*', GLOB_ONLYDIR));
        }

        // Count databases
        $result = Shell::run('mysql', ['-Nse', 'SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name NOT IN ("information_schema","mysql","performance_schema","sys")']);
        if ($result['success']) {
            $databases = (int)trim($result['output']);
        }

        return [
            'vhosts' => $vhosts,
            'mailboxes' => $mailboxes,
            'databases' => $databases,
        ];
    }
}
