<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Lib;

/**
 * Virtual Mail Alias Management Library
 *
 * Manages mail aliases (DB only - no filesystem operations needed).
 * Postfix reads directly from SQLite via postfix-sqlite driver.
 *
 * Schema: valias(id, source, target, active, created_at, updated_at)
 */
final class Valias
{
    private const string DB_PATH = '/srv/.local/sqlite/sysadm.db';

    private static ?\PDO $pdo = null;

    /**
     * Get database connection.
     */
    private static function db(): \PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new \PDO('sqlite:' . self::DB_PATH, null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        }
        return self::$pdo;
    }

    /**
     * Create a mail alias.
     *
     * @param string $source Source address (e.g., "info@example.com" or "@example.com" for catch-all)
     * @param string|array $target Target address(es)
     */
    public static function add(string $source, string|array $target): array
    {
        $source = strtolower(trim($source));

        // Validate source format
        if (!self::validateAddress($source)) {
            return ['success' => false, 'error' => "Invalid source address: {$source}"];
        }

        // Extract domain from source
        $domain = self::extractDomain($source);

        // Check domain exists in vhosts
        $stmt = self::db()->prepare('SELECT id FROM vhosts WHERE domain = ? AND active = 1');
        $stmt->execute([$domain]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => "Domain not found or inactive: {$domain}"];
        }

        // Check if alias already exists
        $stmt = self::db()->prepare('SELECT id FROM valias WHERE source = ?');
        $stmt->execute([$source]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => "Alias already exists: {$source}"];
        }

        // Normalize target(s)
        $targets = is_array($target) ? $target : [$target];
        $targets = array_map(fn($t) => strtolower(trim($t)), $targets);
        $targetStr = implode(',', $targets);

        // Validate each target
        foreach ($targets as $t) {
            if (!filter_var($t, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'error' => "Invalid target address: {$t}"];
            }
        }

        // Insert into database
        try {
            $stmt = self::db()->prepare(
                'INSERT INTO valias (source, target, active, created_at, updated_at)
                 VALUES (?, ?, 1, datetime("now"), datetime("now"))'
            );
            $stmt->execute([$source, $targetStr]);
        } catch (\PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }

        return [
            'success' => true,
            'source' => $source,
            'target' => $targets,
        ];
    }

    /**
     * Delete a mail alias.
     */
    public static function del(string $source): array
    {
        $source = strtolower(trim($source));

        $stmt = self::db()->prepare('SELECT id FROM valias WHERE source = ?');
        $stmt->execute([$source]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => "Alias not found: {$source}"];
        }

        $stmt = self::db()->prepare('DELETE FROM valias WHERE source = ?');
        $stmt->execute([$source]);

        return ['success' => true, 'source' => $source];
    }

    /**
     * List aliases, optionally filtered by domain.
     */
    public static function list(?string $domain = null): array
    {
        $sql = 'SELECT source, target, active, created_at, updated_at FROM valias';
        $params = [];

        if ($domain) {
            $sql .= ' WHERE source LIKE ?';
            $params[] = "%@{$domain}";
        }

        $sql .= ' ORDER BY source';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $aliases = [];
        foreach ($rows as $row) {
            $aliases[] = [
                'source' => $row['source'],
                'target' => explode(',', $row['target']),
                'active' => (bool)$row['active'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];
        }

        return $aliases;
    }

    /**
     * Show single alias details.
     */
    public static function show(string $source): array
    {
        $source = strtolower(trim($source));

        $stmt = self::db()->prepare(
            'SELECT id, source, target, active, created_at, updated_at
             FROM valias WHERE source = ?'
        );
        $stmt->execute([$source]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['success' => false, 'error' => "Alias not found: {$source}"];
        }

        return [
            'success' => true,
            'id' => $row['id'],
            'source' => $row['source'],
            'target' => explode(',', $row['target']),
            'active' => (bool)$row['active'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }

    /**
     * Update alias targets.
     */
    public static function update(string $source, string|array $target): array
    {
        $source = strtolower(trim($source));

        // Normalize target(s)
        $targets = is_array($target) ? $target : [$target];
        $targets = array_map(fn($t) => strtolower(trim($t)), $targets);
        $targetStr = implode(',', $targets);

        // Validate each target
        foreach ($targets as $t) {
            if (!filter_var($t, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'error' => "Invalid target address: {$t}"];
            }
        }

        $stmt = self::db()->prepare(
            'UPDATE valias SET target = ?, updated_at = datetime("now") WHERE source = ?'
        );
        $stmt->execute([$targetStr, $source]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => "Alias not found: {$source}"];
        }

        return ['success' => true, 'source' => $source, 'target' => $targets];
    }

    /**
     * Enable/disable alias.
     */
    public static function setActive(string $source, bool $active): array
    {
        $source = strtolower(trim($source));

        $stmt = self::db()->prepare(
            'UPDATE valias SET active = ?, updated_at = datetime("now") WHERE source = ?'
        );
        $stmt->execute([$active ? 1 : 0, $source]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => "Alias not found: {$source}"];
        }

        return ['success' => true, 'source' => $source, 'active' => $active];
    }

    /**
     * Validate source address format.
     * Accepts: user@domain.tld or @domain.tld (catch-all)
     */
    private static function validateAddress(string $address): bool
    {
        // Catch-all format
        if (str_starts_with($address, '@')) {
            $domain = substr($address, 1);
            return preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/', $domain) === 1;
        }
        // Regular email
        return filter_var($address, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Extract domain from source address.
     */
    private static function extractDomain(string $source): string
    {
        if (str_starts_with($source, '@')) {
            return substr($source, 1);
        }
        return explode('@', $source)[1];
    }
}
