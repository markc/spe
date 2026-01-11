<?php
// NS 3.0 Server Configuration
// Install to: /etc/ns3/config.php

return [
    // Base paths
    'VPATH' => '/srv',                              // Base path for all vhosts
    'WPATH' => '/srv/%s/web/app/public',            // Web docroot (sprintf with domain)
    'MPATH' => '/srv/%s/msg',                       // Mail base (sprintf with domain)
    'UPATH' => '/srv/%s/msg/%s',                    // User maildir (sprintf with domain, user)

    // Nginx paths
    'NGINX_AVAILABLE' => '/etc/nginx/sites-available',
    'NGINX_ENABLED' => '/etc/nginx/sites-enabled',

    // PHP versions (checked in order)
    'PHP_VERSIONS' => ['8.5', '8.4', '8.3'],

    // UID/GID range for vhost users
    'UID_MIN' => 1001,
    'UID_MAX' => 1999,

    // Database paths (for lib/ classes)
    'SYSADM_DB' => '/srv/.local/sqlite/sysadm.db',
    'HCP_DB' => '/srv/.local/sqlite/hcp.db',
];
