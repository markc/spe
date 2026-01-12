<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Lib;

/**
 * Vhost Filesystem Operations
 *
 * Creates/deletes/manages vhost directories and configs on remote servers.
 * Each method is a sequence of individual SSH commands via Remote.
 * Used by both CLI (bin/*) and web UI (Vhosts.php).
 */
final class VhostOps
{
    private const string VPATH = '/srv';

    /**
     * Create a vhost filesystem structure.
     */
    public static function add(string $domain, ?string $uname = null): array
    {
        $domain = strtolower(trim($domain));
        $uname = $uname ?: preg_replace('/[^a-z0-9]/', '', explode('.', $domain)[0]);
        $home = self::VPATH . "/{$domain}";
        $docroot = "{$home}/web/app/public";

        // Check if already exists
        if (Remote::exists($home)) {
            return ['success' => false, 'error' => 'Vhost already exists'];
        }

        // Get next available UID
        $uid = Remote::nextUid();
        if (!$uid) {
            return ['success' => false, 'error' => 'No UID available'];
        }

        // Create group and user
        Remote::run("groupadd -g {$uid} {$uname}");
        Remote::run("useradd -u {$uid} -g {$uid} -d {$home} -s /bin/bash {$uname}");

        // Create directory structure
        Remote::run("mkdir -p {$home}/.ssh {$home}/var/log {$home}/var/run {$home}/msg {$docroot}");
        Remote::run("chown -R {$uid}:{$uid} {$home}");
        Remote::run("chmod 700 {$home}/.ssh");

        // Create index.html
        $index = self::indexHtml($domain);
        Remote::run("cat > {$docroot}/index.html << 'EOF'\n{$index}\nEOF");
        Remote::run("chown {$uid}:{$uid} {$docroot}/index.html");

        // Find PHP version and create FPM pool
        $phpVer = Remote::exec('ls /etc/php/ 2>/dev/null | sort -rV | head -1');
        if ($phpVer) {
            $fpmConf = self::fpmConfig($domain, $uname, $home);
            Remote::run("cat > /etc/php/{$phpVer}/fpm/pool.d/{$domain}.conf << 'EOF'\n{$fpmConf}\nEOF");
        }

        // Create nginx config
        $nginxConf = self::nginxConfig($domain, $home, $docroot);
        Remote::run("cat > /etc/nginx/sites-available/{$domain} << 'EOF'\n{$nginxConf}\nEOF");
        Remote::run("ln -sf /etc/nginx/sites-available/{$domain} /etc/nginx/sites-enabled/{$domain}");

        return [
            'success' => true,
            'domain' => $domain,
            'uname' => $uname,
            'uid' => $uid,
            'gid' => $uid,
            'home' => $home,
        ];
    }

    /**
     * Delete a vhost filesystem structure.
     */
    public static function del(string $domain): array
    {
        $domain = strtolower(trim($domain));
        $uname = preg_replace('/[^a-z0-9]/', '', explode('.', $domain)[0]);
        $home = self::VPATH . "/{$domain}";

        if (!Remote::exists($home)) {
            return ['success' => false, 'error' => 'Vhost not found'];
        }

        // Remove nginx config
        Remote::run("rm -f /etc/nginx/sites-enabled/{$domain}");
        Remote::run("rm -f /etc/nginx/sites-available/{$domain}");

        // Remove PHP-FPM pool
        $phpVer = Remote::exec('ls /etc/php/ 2>/dev/null | sort -rV | head -1');
        if ($phpVer) {
            Remote::run("rm -f /etc/php/{$phpVer}/fpm/pool.d/{$domain}.conf");
        }

        // Remove user and group
        Remote::run("userdel {$uname} 2>/dev/null || true");
        Remote::run("groupdel {$uname} 2>/dev/null || true");

        // Remove directory
        Remote::run("rm -rf {$home}");

        return ['success' => true, 'domain' => $domain];
    }

    /**
     * Show vhost info from filesystem.
     */
    public static function show(string $domain): array
    {
        $domain = strtolower(trim($domain));
        $home = self::VPATH . "/{$domain}";

        if (!Remote::exists($home)) {
            return ['success' => false, 'error' => 'Vhost not found'];
        }

        $stat = Remote::stat($home);
        $user = Remote::exec("stat -c '%U' {$home}");
        $size = Remote::exec("du -sh {$home} | cut -f1");
        $mboxes = (int) Remote::exec("ls -1 {$home}/msg 2>/dev/null | wc -l");
        $nginx = Remote::exists("/etc/nginx/sites-enabled/{$domain}") ? 'enabled' : 'disabled';
        $phpVer = Remote::exec('ls /etc/php/ 2>/dev/null | sort -rV | head -1');

        return [
            'success' => true,
            'domain' => $domain,
            'home' => $home,
            'user' => $user,
            'uid' => $stat['uid'],
            'gid' => $stat['gid'],
            'size' => $size,
            'mailboxes' => $mboxes,
            'nginx' => $nginx,
            'php' => $phpVer,
        ];
    }

    /**
     * Enable/disable/restart vhost services.
     */
    public static function manage(string $domain, string $action): array
    {
        $domain = strtolower(trim($domain));
        $home = self::VPATH . "/{$domain}";

        if (!Remote::exists($home)) {
            return ['success' => false, 'error' => 'Vhost not found'];
        }

        $phpVer = Remote::exec('ls /etc/php/ 2>/dev/null | sort -rV | head -1');

        switch ($action) {
            case 'enable':
                if (!Remote::exists("/etc/nginx/sites-available/{$domain}")) {
                    return ['success' => false, 'error' => 'Nginx config not found'];
                }
                Remote::run("ln -sf /etc/nginx/sites-available/{$domain} /etc/nginx/sites-enabled/{$domain}");
                Remote::run('nginx -t && systemctl reload nginx');
                return ['success' => true, 'action' => 'enabled', 'domain' => $domain];

            case 'disable':
                Remote::run("rm -f /etc/nginx/sites-enabled/{$domain}");
                Remote::run('systemctl reload nginx');
                return ['success' => true, 'action' => 'disabled', 'domain' => $domain];

            case 'restart':
                Remote::run('systemctl reload nginx');
                if ($phpVer) {
                    Remote::run("systemctl reload php{$phpVer}-fpm");
                }
                return ['success' => true, 'action' => 'restarted', 'domain' => $domain, 'php' => $phpVer];

            default:
                return ['success' => false, 'error' => "Unknown action: {$action}"];
        }
    }

    // =======================================================================
    // CONFIG TEMPLATES
    // =======================================================================

    private static function nginxConfig(string $domain, string $home, string $docroot): string
    {
        return <<<NGINX
        server {
            listen 80;
            server_name {$domain} www.{$domain};
            root {$docroot};
            index index.html index.php;

            access_log {$home}/var/log/access.log;
            error_log {$home}/var/log/error.log;

            location / {
                try_files \$uri \$uri/ /index.php?\$query_string;
            }

            location ~ \\.php\$ {
                fastcgi_pass unix:{$home}/var/run/fpm.sock;
                fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
                include fastcgi_params;
            }

            location ~ /\\.ht {
                deny all;
            }
        }
        NGINX;
    }

    private static function fpmConfig(string $domain, string $uname, string $home): string
    {
        return <<<FPM
        [{$domain}]
        user = {$uname}
        group = {$uname}
        listen = {$home}/var/run/fpm.sock
        listen.owner = www-data
        listen.group = www-data
        pm = ondemand
        pm.max_children = 3
        pm.process_idle_timeout = 60s
        FPM;
    }

    private static function indexHtml(string $domain): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head><title>Welcome to {$domain}</title></head>
        <body>
        <h1>Welcome to {$domain}</h1>
        <p>This site is under construction.</p>
        </body>
        </html>
        HTML;
    }
}
