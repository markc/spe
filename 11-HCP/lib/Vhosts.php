<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Lib;

/**
 * Virtual Host Management Library
 *
 * Orchestrates vhost operations:
 * - Database operations run as web user (SQLite group-writable)
 * - Filesystem operations run as root via Exec (SSH)
 *
 * Schema: vhosts(id, domain, uname, uid, gid, active, aliases, created_at, updated_at)
 */
final class Vhosts
{
    private const string VHOST_BASE = '/srv';  // NS 3.0: /srv/domain/web/app/public

    private static ?\PDO $pdo = null;

    /**
     * Get database path from environment or use local default.
     */
    private static function dbPath(): string
    {
        return $_ENV['SYSADM_DB'] ?? getenv('SYSADM_DB') ?: __DIR__ . '/../sysadm.db';
    }

    /**
     * Get database connection.
     */
    private static function db(): \PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new \PDO('sqlite:' . self::dbPath(), null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        }
        return self::$pdo;
    }

    /**
     * Create a virtual host.
     *
     * 1. Validate domain
     * 2. Create filesystem structure via SSH (root)
     * 3. Insert into database
     */
    public static function add(string $domain, ?string $uname = null, ?string $host = null): array
    {
        $domain = strtolower(trim($domain));

        // Validate domain format
        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/', $domain)) {
            return ['success' => false, 'error' => "Invalid domain format: {$domain}"];
        }

        // Check if domain already exists
        $stmt = self::db()->prepare('SELECT id FROM vhosts WHERE domain = ?');
        $stmt->execute([$domain]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => "Domain already exists: {$domain}"];
        }

        // Generate username from domain if not provided
        $uname = $uname ?: self::domainToUsername($domain);

        // Create vhost via SSH (privileged operation)
        $result = Exec::run('addvhost', [$domain, $uname], $host);

        if (!$result['success']) {
            return ['success' => false, 'error' => $result['output']];
        }

        // Parse uid/gid from output
        $uid = $gid = 0;
        if (preg_match('/uid=(\d+)\s+gid=(\d+)/', $result['output'], $m)) {
            $uid = (int)$m[1];
            $gid = (int)$m[2];
        }

        // Insert into database
        try {
            $stmt = self::db()->prepare(
                'INSERT INTO vhosts (domain, uname, uid, gid, active, aliases, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 1, ?, datetime("now"), datetime("now"))'
            );
            $stmt->execute([$domain, $uname, $uid, $gid, '']);
        } catch (\PDOException $e) {
            // Rollback: remove vhost since DB insert failed
            Exec::run('delvhost', [$domain, '--keep-db'], $host);
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }

        return [
            'success' => true,
            'domain' => $domain,
            'uname' => $uname,
            'uid' => $uid,
            'gid' => $gid,
            'home' => self::VHOST_BASE . "/{$domain}",
        ];
    }

    /**
     * Delete a virtual host.
     *
     * 1. Get vhost info from DB
     * 2. Delete filesystem via SSH (root)
     * 3. Remove from database
     */
    public static function del(string $domain, bool $removeFiles = true, ?string $host = null): array
    {
        $domain = strtolower(trim($domain));

        // Get vhost info
        $stmt = self::db()->prepare('SELECT id, uname FROM vhosts WHERE domain = ?');
        $stmt->execute([$domain]);
        $vhost = $stmt->fetch();

        if (!$vhost) {
            return ['success' => false, 'error' => "Domain not found: {$domain}"];
        }

        // Remove filesystem via SSH (privileged operation)
        if ($removeFiles) {
            $result = Exec::run('delvhost', [$domain], $host);
            if (!$result['success']) {
                return ['success' => false, 'error' => $result['output']];
            }
        }

        // Delete from database
        $stmt = self::db()->prepare('DELETE FROM vhosts WHERE id = ?');
        $stmt->execute([$vhost['id']]);

        // Also delete associated mailboxes and aliases
        $stmt = self::db()->prepare('DELETE FROM vmails WHERE user LIKE ?');
        $stmt->execute(["%@{$domain}"]);

        $stmt = self::db()->prepare('DELETE FROM valias WHERE source LIKE ? OR target LIKE ?');
        $stmt->execute(["%@{$domain}", "%@{$domain}%"]);

        return ['success' => true, 'domain' => $domain];
    }

    /**
     * List virtual hosts (DB only, no SSH needed).
     */
    public static function list(): array
    {
        $stmt = self::db()->query(
            'SELECT domain, uname, uid, gid, active, aliases, created_at, updated_at
             FROM vhosts ORDER BY domain'
        );
        $rows = $stmt->fetchAll();

        $vhosts = [];
        foreach ($rows as $row) {
            $vhosts[] = [
                'domain' => $row['domain'],
                'uname' => $row['uname'],
                'uid' => $row['uid'],
                'gid' => $row['gid'],
                'active' => (bool)$row['active'],
                'aliases' => $row['aliases'] ? explode(',', $row['aliases']) : [],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];
        }

        return $vhosts;
    }

    /**
     * Show single vhost details.
     * Fetches disk usage via SSH if host provided.
     */
    public static function show(string $domain, ?string $host = null): array
    {
        $domain = strtolower(trim($domain));

        $stmt = self::db()->prepare(
            'SELECT id, domain, uname, uid, gid, active, aliases, created_at, updated_at
             FROM vhosts WHERE domain = ?'
        );
        $stmt->execute([$domain]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['success' => false, 'error' => "Domain not found: {$domain}"];
        }

        // Get disk usage via SSH if host provided
        $diskUsage = 0;
        $diskHuman = '0 B';
        if ($host) {
            $result = Exec::run('shvhost', [$domain, '--size'], $host);
            if ($result['success'] && preg_match('/Size:\s*(\d+)/', $result['output'], $m)) {
                $diskUsage = (int)$m[1];
                $diskHuman = self::formatBytes($diskUsage);
            }
        }

        // Get mailbox count
        $stmt = self::db()->prepare('SELECT COUNT(*) as cnt FROM vmails WHERE user LIKE ?');
        $stmt->execute(["%@{$domain}"]);
        $mailCount = (int)$stmt->fetch()['cnt'];

        // Get alias count
        $stmt = self::db()->prepare('SELECT COUNT(*) as cnt FROM valias WHERE source LIKE ?');
        $stmt->execute(["%@{$domain}"]);
        $aliasCount = (int)$stmt->fetch()['cnt'];

        return [
            'success' => true,
            'id' => $row['id'],
            'domain' => $row['domain'],
            'uname' => $row['uname'],
            'uid' => $row['uid'],
            'gid' => $row['gid'],
            'active' => (bool)$row['active'],
            'aliases' => $row['aliases'] ? explode(',', $row['aliases']) : [],
            'disk_usage' => $diskUsage,
            'disk_human' => $diskHuman,
            'mailbox_count' => $mailCount,
            'alias_count' => $aliasCount,
            'home' => self::VHOST_BASE . "/{$row['domain']}",
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }

    /**
     * Enable/disable vhost (DB only).
     */
    public static function setActive(string $domain, bool $active): array
    {
        $domain = strtolower(trim($domain));

        $stmt = self::db()->prepare(
            'UPDATE vhosts SET active = ?, updated_at = datetime("now") WHERE domain = ?'
        );
        $stmt->execute([$active ? 1 : 0, $domain]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => "Domain not found: {$domain}"];
        }

        return ['success' => true, 'domain' => $domain, 'active' => $active];
    }

    /**
     * Update vhost aliases (DB only).
     */
    public static function setAliases(string $domain, array $aliases): array
    {
        $domain = strtolower(trim($domain));
        $aliasStr = implode(',', array_map('strtolower', array_map('trim', $aliases)));

        $stmt = self::db()->prepare(
            'UPDATE vhosts SET aliases = ?, updated_at = datetime("now") WHERE domain = ?'
        );
        $stmt->execute([$aliasStr, $domain]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => "Domain not found: {$domain}"];
        }

        return ['success' => true, 'domain' => $domain, 'aliases' => $aliases];
    }

    /**
     * Generate username from domain.
     */
    private static function domainToUsername(string $domain): string
    {
        // Remove TLD and use first part, limit to 16 chars
        $parts = explode('.', $domain);
        $name = preg_replace('/[^a-z0-9]/', '', $parts[0]);
        return substr($name, 0, 16);
    }

    /**
     * Format bytes as human readable.
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < 3) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
