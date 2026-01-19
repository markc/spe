<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Lib;

/**
 * Virtual Host Management Library
 *
 * Orchestrates vhost operations:
 * - Database operations run as web user (SQLite group-writable)
 * - Filesystem operations run via VhostOps (SSH)
 *
 * Schema: vhosts(id, domain, uname, uid, gid, active, aliases, created_at, updated_at)
 */
final class Vhosts
{
    private static ?\PDO $pdo = null;

    /**
     * Get database connection.
     */
    private static function db(): \PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new \PDO('sqlite:' . Config::sysadmDb(), null, null, [
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
     * 2. Create filesystem structure via VhostOps
     * 3. Insert into database
     */
    public static function add(string $domain, ?string $uname = null): array
    {
        $domain = strtolower(trim($domain));

        // Validate domain format
        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/', $domain)) {
            return ['success' => false, 'error' => "Invalid domain format: {$domain}"];
        }

        // Check if domain already exists in DB
        $stmt = self::db()->prepare('SELECT id FROM vhosts WHERE domain = ?');
        $stmt->execute([$domain]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => "Domain already exists: {$domain}"];
        }

        // Generate username from domain if not provided
        $uname = $uname ?: self::domainToUsername($domain);

        // Create vhost filesystem via VhostOps
        $result = VhostOps::add($domain, $uname);

        if (!$result['success']) {
            return $result;
        }

        // Insert into database
        try {
            $stmt = self::db()->prepare('INSERT INTO vhosts (domain, uname, uid, gid, active, aliases, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 1, ?, datetime("now"), datetime("now"))');
            $stmt->execute([$domain, $uname, $result['uid'], $result['gid'], '']);
        } catch (\PDOException $e) {
            // Rollback: remove vhost filesystem since DB insert failed
            VhostOps::del($domain);
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }

        return $result;
    }

    /**
     * Delete a virtual host.
     *
     * 1. Get vhost info from DB
     * 2. Delete filesystem via VhostOps
     * 3. Remove from database
     */
    public static function del(string $domain, bool $removeFiles = true): array
    {
        $domain = strtolower(trim($domain));

        // Get vhost info
        $stmt = self::db()->prepare('SELECT id, uname FROM vhosts WHERE domain = ?');
        $stmt->execute([$domain]);
        $vhost = $stmt->fetch();

        if (!$vhost) {
            return ['success' => false, 'error' => "Domain not found: {$domain}"];
        }

        // Remove filesystem via VhostOps
        if ($removeFiles) {
            $result = VhostOps::del($domain);
            if (!$result['success']) {
                return $result;
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
        $stmt = self::db()->query('SELECT domain, uname, uid, gid, active, aliases, created_at, updated_at
             FROM vhosts ORDER BY domain');
        $rows = $stmt->fetchAll();

        $vhosts = [];
        foreach ($rows as $row) {
            $vhosts[] = [
                'domain' => $row['domain'],
                'uname' => $row['uname'],
                'uid' => $row['uid'],
                'gid' => $row['gid'],
                'active' => (bool) $row['active'],
                'aliases' => $row['aliases'] ? explode(',', $row['aliases']) : [],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];
        }

        return $vhosts;
    }

    /**
     * Show single vhost details.
     * Fetches disk usage via VhostOps.
     */
    public static function show(string $domain, bool $includeFilesystem = true): array
    {
        $domain = strtolower(trim($domain));

        $stmt = self::db()->prepare('SELECT id, domain, uname, uid, gid, active, aliases, created_at, updated_at
             FROM vhosts WHERE domain = ?');
        $stmt->execute([$domain]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['success' => false, 'error' => "Domain not found: {$domain}"];
        }

        // Get filesystem info via VhostOps if requested
        $diskUsage = '0';
        $mailboxCount = 0;
        if ($includeFilesystem) {
            $fsInfo = VhostOps::show($domain);
            if ($fsInfo['success']) {
                $diskUsage = $fsInfo['size'];
                $mailboxCount = $fsInfo['mailboxes'];
            }
        }

        // Get mailbox count from DB
        $stmt = self::db()->prepare('SELECT COUNT(*) as cnt FROM vmails WHERE user LIKE ?');
        $stmt->execute(["%@{$domain}"]);
        $dbMailCount = (int) $stmt->fetch()['cnt'];

        // Get alias count
        $stmt = self::db()->prepare('SELECT COUNT(*) as cnt FROM valias WHERE source LIKE ?');
        $stmt->execute(["%@{$domain}"]);
        $aliasCount = (int) $stmt->fetch()['cnt'];

        return [
            'success' => true,
            'id' => $row['id'],
            'domain' => $row['domain'],
            'uname' => $row['uname'],
            'uid' => $row['uid'],
            'gid' => $row['gid'],
            'active' => (bool) $row['active'],
            'aliases' => $row['aliases'] ? explode(',', $row['aliases']) : [],
            'disk_usage' => $diskUsage,
            'mailbox_count' => $dbMailCount ?: $mailboxCount,
            'alias_count' => $aliasCount,
            'home' => Config::vhostPath($row['domain']),
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

        $stmt = self::db()->prepare('UPDATE vhosts SET active = ?, updated_at = datetime("now") WHERE domain = ?');
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

        $stmt = self::db()->prepare('UPDATE vhosts SET aliases = ?, updated_at = datetime("now") WHERE domain = ?');
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
}
