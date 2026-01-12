<?php declare(strict_types=1);
// NS 3.0 Server Configuration
// Install to: /etc/ns3/config.php

return [
    // === Primary Host ===
    'VHOST' => trim(shell_exec('hostname -f 2>/dev/null') ?? '') ?: 'localhost',
    'ADMIN' => 'sysadm',
    'AMAIL' => '',                                  // Set per-server

    // === Base Paths ===
    'VPATH' => '/srv',                              // Base: /srv/domain
    'WPATH' => '/srv/%s/web/app/public',            // Docroot: sprintf(WPATH, $domain)
    'MPATH' => '/srv/%s/msg',                       // Mail: sprintf(MPATH, $domain)
    'UPATH' => '/srv/%s/msg/%s',                    // Maildir: sprintf(UPATH, $domain, $user)

    // === Database ===
    'DTYPE' => 'sqlite',                            // sqlite or mysql
    'SYSADM_DB' => '/srv/.local/sqlite/sysadm.db',
    'HCP_DB' => '/srv/.local/sqlite/hcp.db',

    // === Nginx ===
    'NGINX_AVAILABLE' => '/etc/nginx/sites-available',
    'NGINX_ENABLED' => '/etc/nginx/sites-enabled',

    // === PHP ===
    'V_PHP' => '8.4',
    'PHP_VERSIONS' => ['8.5', '8.4', '8.3'],

    // === UID/GID Range ===
    'UID_MIN' => 1001,
    'UID_MAX' => 1999,
    'WUGID' => 'www-data',                          // Web server group
];
