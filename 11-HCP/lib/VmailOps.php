<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Lib;

/**
 * Vmail Filesystem Operations
 *
 * Creates/deletes/manages mailbox directories on remote servers.
 * Each method is a sequence of individual SSH commands via Remote.
 * Used by both CLI (bin/*) and web UI (Vmails.php).
 */
final class VmailOps
{
    private const string VPATH = '/srv';

    /**
     * Create a mailbox filesystem structure.
     */
    public static function add(string $email): array
    {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }

        [$user, $domain] = explode('@', $email);
        $domainPath = self::VPATH . "/{$domain}";
        $home = "{$domainPath}/msg/{$user}";

        // Check domain exists
        if (!Remote::exists($domainPath)) {
            return ['success' => false, 'error' => 'Domain not found'];
        }

        // Check mailbox doesn't already exist
        if (Remote::exists($home)) {
            return ['success' => false, 'error' => 'Mailbox already exists'];
        }

        // Get uid/gid from domain directory
        $stat = Remote::stat($domainPath);

        // Create maildir structure
        Remote::run("mkdir -p {$home}/Maildir/cur {$home}/Maildir/new {$home}/Maildir/tmp {$home}/sieve");
        Remote::run("chown -R {$stat['uid']}:{$stat['gid']} {$home}");
        Remote::run("chmod -R 700 {$home}");

        // Create default sieve script
        $sieve = self::sieveScript();
        Remote::run("cat > {$home}/sieve/default.sieve << 'EOF'\n{$sieve}\nEOF");
        Remote::run("chown {$stat['uid']}:{$stat['gid']} {$home}/sieve/default.sieve");
        Remote::run("chmod 600 {$home}/sieve/default.sieve");

        return [
            'success' => true,
            'email' => $email,
            'home' => $home,
            'uid' => $stat['uid'],
            'gid' => $stat['gid'],
        ];
    }

    /**
     * Delete a mailbox filesystem structure.
     */
    public static function del(string $email): array
    {
        $email = strtolower(trim($email));
        [$user, $domain] = explode('@', $email);
        $home = self::VPATH . "/{$domain}/msg/{$user}";

        if (!Remote::exists($home)) {
            return ['success' => false, 'error' => 'Mailbox not found'];
        }

        Remote::run("rm -rf {$home}");

        return ['success' => true, 'email' => $email];
    }

    /**
     * Show mailbox info from filesystem.
     */
    public static function show(string $email): array
    {
        $email = strtolower(trim($email));
        [$user, $domain] = explode('@', $email);
        $home = self::VPATH . "/{$domain}/msg/{$user}";

        if (!Remote::exists($home)) {
            return ['success' => false, 'error' => 'Mailbox not found'];
        }

        $stat = Remote::stat($home);
        $size = (int)Remote::exec("du -sb {$home} | cut -f1");
        $msgs = (int)Remote::exec("find {$home}/Maildir -type f 2>/dev/null | wc -l");

        return [
            'success' => true,
            'email' => $email,
            'home' => $home,
            'uid' => $stat['uid'],
            'gid' => $stat['gid'],
            'size' => $size,
            'messages' => $msgs,
        ];
    }

    /**
     * Generate password hash using doveadm on remote.
     */
    public static function hashPassword(string $password): string
    {
        $hash = Remote::exec("doveadm pw -s SHA512-CRYPT -p " . escapeshellarg($password) . " 2>/dev/null");

        if (!$hash) {
            // Fallback: generate locally
            $salt = '$6$rounds=5000$' . bin2hex(random_bytes(8)) . '$';
            $hash = '{SHA512-CRYPT}' . crypt($password, $salt);
        }

        return $hash;
    }

    // =======================================================================
    // TEMPLATES
    // =======================================================================

    private static function sieveScript(): string
    {
        return <<<'SIEVE'
require ["fileinto", "mailbox"];

# Example: Move spam to Junk folder
# if header :contains "X-Spam-Flag" "YES" {
#     fileinto :create "Junk";
#     stop;
# }

keep;
SIEVE;
    }
}
