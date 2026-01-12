<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Lib;

/**
 * Virtual Mail Management Library
 *
 * Orchestrates mail operations:
 * - Database operations run as web user (SQLite group-writable)
 * - Filesystem operations run via VmailOps (SSH)
 *
 * Schema: vmails(id, user, pass, home, uid, gid, active, created_at, updated_at)
 */
final class Vmails
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
     * Create a virtual mailbox.
     *
     * 1. Validate input
     * 2. Check domain exists
     * 3. Create maildir via VmailOps
     * 4. Insert into database
     */
    public static function add(string $email, ?string $password = null): array
    {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => "Invalid email: {$email}"];
        }

        $email = strtolower($email);
        [$user, $domain] = explode('@', $email);

        // Check domain exists in vhosts
        $stmt = self::db()->prepare('SELECT uid, gid FROM vhosts WHERE domain = ? AND active = 1');
        $stmt->execute([$domain]);
        $vhost = $stmt->fetch();

        if (!$vhost) {
            return ['success' => false, 'error' => "Domain not found or inactive: {$domain}"];
        }

        // Check if mailbox already exists in DB
        $stmt = self::db()->prepare('SELECT id FROM vmails WHERE user = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => "Mailbox already exists: {$email}"];
        }

        // Generate password if not provided
        $password = $password ?: self::generatePassword();

        // Create maildir via VmailOps
        $result = VmailOps::add($email);

        if (!$result['success']) {
            return $result;
        }

        // Generate password hash
        $hash = VmailOps::hashPassword($password);

        // Insert into database
        try {
            $stmt = self::db()->prepare(
                'INSERT INTO vmails (user, pass, home, uid, gid, active, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 1, datetime("now"), datetime("now"))'
            );
            $stmt->execute([$email, $hash, $result['home'], $vhost['uid'], $vhost['gid']]);
        } catch (\PDOException $e) {
            // Rollback: remove maildir since DB insert failed
            VmailOps::del($email);
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }

        return [
            'success' => true,
            'email' => $email,
            'password' => $password,
            'home' => $result['home'],
        ];
    }

    /**
     * Delete a virtual mailbox.
     *
     * 1. Get mailbox info from DB
     * 2. Delete maildir via VmailOps
     * 3. Remove from database
     */
    public static function del(string $email, bool $removeFiles = true): array
    {
        $email = strtolower($email);

        // Get mailbox info
        $stmt = self::db()->prepare('SELECT id, home FROM vmails WHERE user = ?');
        $stmt->execute([$email]);
        $mailbox = $stmt->fetch();

        if (!$mailbox) {
            return ['success' => false, 'error' => "Mailbox not found: {$email}"];
        }

        // Remove maildir via VmailOps
        if ($removeFiles) {
            $result = VmailOps::del($email);
            if (!$result['success']) {
                return $result;
            }
        }

        // Delete from database
        $stmt = self::db()->prepare('DELETE FROM vmails WHERE id = ?');
        $stmt->execute([$mailbox['id']]);

        // Also delete any aliases pointing to/from this address
        $stmt = self::db()->prepare('DELETE FROM valias WHERE source = ? OR target LIKE ?');
        $stmt->execute([$email, "%{$email}%"]);

        return ['success' => true, 'email' => $email, 'home' => $mailbox['home']];
    }

    /**
     * List virtual mailboxes (DB only, no SSH needed).
     */
    public static function list(?string $domain = null): array
    {
        $sql = 'SELECT user, home, uid, gid, active, created_at, updated_at FROM vmails';
        $params = [];

        if ($domain) {
            $sql .= ' WHERE user LIKE ?';
            $params[] = "%@{$domain}";
        }

        $sql .= ' ORDER BY user';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $mailboxes = [];
        foreach ($rows as $row) {
            $mailboxes[] = [
                'email' => $row['user'],
                'home' => $row['home'],
                'uid' => $row['uid'],
                'gid' => $row['gid'],
                'active' => (bool)$row['active'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];
        }

        return $mailboxes;
    }

    /**
     * Show single mailbox details.
     * Fetches size via VmailOps.
     */
    public static function show(string $email, bool $includeFilesystem = true): array
    {
        $email = strtolower($email);

        $stmt = self::db()->prepare(
            'SELECT id, user, home, uid, gid, active, created_at, updated_at
             FROM vmails WHERE user = ?'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['success' => false, 'error' => "Mailbox not found: {$email}"];
        }

        // Get size via VmailOps if requested
        $size = 0;
        $messages = 0;
        if ($includeFilesystem) {
            $fsInfo = VmailOps::show($email);
            if ($fsInfo['success']) {
                $size = $fsInfo['size'];
                $messages = $fsInfo['messages'];
            }
        }

        // Get aliases for this mailbox
        $stmt = self::db()->prepare('SELECT source, target FROM valias WHERE target LIKE ?');
        $stmt->execute(["%{$email}%"]);
        $aliases = $stmt->fetchAll();

        return [
            'success' => true,
            'id' => $row['id'],
            'email' => $row['user'],
            'home' => $row['home'],
            'uid' => $row['uid'],
            'gid' => $row['gid'],
            'active' => (bool)$row['active'],
            'size' => $size,
            'size_human' => self::formatBytes($size),
            'messages' => $messages,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'aliases' => $aliases,
        ];
    }

    /**
     * Change mailbox password.
     */
    public static function passwd(string $email, string $password): array
    {
        $email = strtolower($email);

        // Verify mailbox exists
        $stmt = self::db()->prepare('SELECT id FROM vmails WHERE user = ?');
        $stmt->execute([$email]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => "Mailbox not found: {$email}"];
        }

        // Generate password hash via VmailOps (uses doveadm on remote)
        $hash = VmailOps::hashPassword($password);

        // Update database
        $stmt = self::db()->prepare(
            'UPDATE vmails SET pass = ?, updated_at = datetime("now") WHERE user = ?'
        );
        $stmt->execute([$hash, $email]);

        return ['success' => true, 'email' => $email];
    }

    /**
     * Enable/disable mailbox (DB only).
     */
    public static function setActive(string $email, bool $active): array
    {
        $email = strtolower($email);

        $stmt = self::db()->prepare(
            'UPDATE vmails SET active = ?, updated_at = datetime("now") WHERE user = ?'
        );
        $stmt->execute([$active ? 1 : 0, $email]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => "Mailbox not found: {$email}"];
        }

        return ['success' => true, 'email' => $email, 'active' => $active];
    }

    /**
     * Generate secure random password.
     */
    private static function generatePassword(int $length = 12): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
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
