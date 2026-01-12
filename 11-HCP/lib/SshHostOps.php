<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Lib;

/**
 * SSH Host Operations
 *
 * Manages vnodes (SSH hosts) in the database and generates
 * ~/.ssh/hosts/* files for native SSH compatibility.
 */
final class SshHostOps
{
    private static ?\PDO $pdo = null;

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
     * Add a new SSH host.
     */
    public static function add(
        string $name,
        string $hostname,
        int $port = 22,
        string $user = 'root',
        ?string $sshKey = null,
        ?string $label = null
    ): array {
        $name = strtolower(trim($name));
        $hostname = strtolower(trim($hostname));

        // Validate name
        if (!preg_match('/^[a-z0-9][a-z0-9_-]*$/', $name)) {
            return ['success' => false, 'error' => 'Invalid name (alphanumeric, dash, underscore)'];
        }

        // Validate SSH key exists if specified
        if ($sshKey) {
            $keyPath = Config::sshKeysDir() . '/' . $sshKey;
            if (!file_exists($keyPath)) {
                return ['success' => false, 'error' => "SSH key not found: {$sshKey}"];
            }
        }

        // Check if exists
        $stmt = self::db()->prepare('SELECT id FROM vnodes WHERE name = ?');
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => "Host already exists: {$name}"];
        }

        // Insert into database
        $stmt = self::db()->prepare(
            'INSERT INTO vnodes (name, hostname, port, user, ssh_key, label)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $hostname, $port, $user, $sshKey, $label]);

        // Generate SSH config file
        self::generateHostFile($name);

        return [
            'success' => true,
            'name' => $name,
            'hostname' => $hostname,
            'port' => $port,
            'user' => $user,
            'ssh_key' => $sshKey,
        ];
    }

    /**
     * Delete an SSH host.
     */
    public static function del(string $name): array
    {
        $name = strtolower(trim($name));

        $stmt = self::db()->prepare('SELECT id FROM vnodes WHERE name = ?');
        $stmt->execute([$name]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => "Host not found: {$name}"];
        }

        // Delete from database
        $stmt = self::db()->prepare('DELETE FROM vnodes WHERE name = ?');
        $stmt->execute([$name]);

        // Remove SSH config file
        $hostFile = Config::sshHostsDir() . '/' . $name;
        if (file_exists($hostFile)) {
            unlink($hostFile);
        }

        return ['success' => true, 'name' => $name];
    }

    /**
     * Get a single host.
     */
    public static function get(string $name): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM vnodes WHERE name = ?'
        );
        $stmt->execute([strtolower(trim($name))]);
        return $stmt->fetch() ?: null;
    }

    /**
     * List all hosts.
     */
    public static function list(?string $group = null): array
    {
        if ($group) {
            $stmt = self::db()->prepare(
                'SELECT v.* FROM vnodes v
                 JOIN vnode_group_members m ON v.id = m.vnode_id
                 JOIN vnode_groups g ON m.group_id = g.id
                 WHERE g.name = ?
                 ORDER BY v.name'
            );
            $stmt->execute([$group]);
        } else {
            $stmt = self::db()->query('SELECT * FROM vnodes ORDER BY name');
        }

        return $stmt->fetchAll();
    }

    /**
     * Update a host property.
     */
    public static function update(string $name, string $field, string $value): array
    {
        $name = strtolower(trim($name));

        $allowedFields = ['hostname', 'port', 'user', 'ssh_key', 'label', 'notes', 'enabled'];
        if (!in_array($field, $allowedFields)) {
            return ['success' => false, 'error' => "Invalid field: {$field}"];
        }

        // Validate SSH key if changing
        if ($field === 'ssh_key' && $value) {
            $keyPath = Config::sshKeysDir() . '/' . $value;
            if (!file_exists($keyPath)) {
                return ['success' => false, 'error' => "SSH key not found: {$value}"];
            }
        }

        $stmt = self::db()->prepare("UPDATE vnodes SET {$field} = ? WHERE name = ?");
        $stmt->execute([$value, $name]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => "Host not found: {$name}"];
        }

        // Regenerate SSH config file
        self::generateHostFile($name);

        return ['success' => true, 'name' => $name, 'field' => $field, 'value' => $value];
    }

    /**
     * Set active host (for targeting).
     */
    public static function setActive(string $name): array
    {
        $name = strtolower(trim($name));

        // Check host exists
        $stmt = self::db()->prepare('SELECT id FROM vnodes WHERE name = ?');
        $stmt->execute([$name]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => "Host not found: {$name}"];
        }

        // Clear all active flags
        self::db()->exec('UPDATE vnodes SET is_active = 0');

        // Set this one active
        $stmt = self::db()->prepare('UPDATE vnodes SET is_active = 1 WHERE name = ?');
        $stmt->execute([$name]);

        return ['success' => true, 'name' => $name, 'active' => true];
    }

    /**
     * Get active host.
     */
    public static function getActive(): ?array
    {
        $stmt = self::db()->query('SELECT * FROM vnodes WHERE is_active = 1 LIMIT 1');
        return $stmt->fetch() ?: null;
    }

    /**
     * Generate SSH host config file.
     */
    public static function generateHostFile(string $name): bool
    {
        $host = self::get($name);
        if (!$host) return false;

        $config = "Host {$host['name']}\n";
        $config .= "    Hostname {$host['hostname']}\n";
        $config .= "    Port {$host['port']}\n";
        $config .= "    User {$host['user']}\n";

        if ($host['ssh_key']) {
            $keyPath = Config::sshKeysDir() . '/' . $host['ssh_key'];
            $config .= "    IdentityFile {$keyPath}\n";
        }

        $hostFile = Config::sshHostsDir() . '/' . $name;
        file_put_contents($hostFile, $config);
        chmod($hostFile, 0600);

        return true;
    }

    /**
     * Regenerate all SSH host config files from database.
     */
    public static function regenerateAllHostFiles(): int
    {
        $count = 0;
        foreach (self::list() as $host) {
            if (self::generateHostFile($host['name'])) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Import existing ~/.ssh/hosts/* files into database.
     */
    public static function importFromFiles(): array
    {
        $hostsDir = Config::sshHostsDir();
        $imported = [];
        $skipped = [];

        foreach (glob("{$hostsDir}/*") as $file) {
            if (is_dir($file)) continue;

            $name = basename($file);
            if (str_starts_with($name, '.')) continue;

            // Parse SSH config file
            $content = file_get_contents($file);
            $host = self::parseSshConfig($content);

            if (!$host['hostname']) {
                $skipped[] = $name;
                continue;
            }

            // Check if already exists
            $stmt = self::db()->prepare('SELECT id FROM vnodes WHERE name = ?');
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                $skipped[] = $name;
                continue;
            }

            // Import
            $result = self::add(
                $name,
                $host['hostname'],
                $host['port'],
                $host['user'],
                $host['ssh_key']
            );

            if ($result['success']) {
                $imported[] = $name;
            } else {
                $skipped[] = $name;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * Parse SSH config file format.
     */
    private static function parseSshConfig(string $content): array
    {
        $host = [
            'hostname' => '',
            'port' => 22,
            'user' => 'root',
            'ssh_key' => null,
        ];

        foreach (explode("\n", $content) as $line) {
            $line = trim($line);
            if (!$line || str_starts_with($line, '#')) continue;

            if (preg_match('/^\s*Hostname\s+(.+)$/i', $line, $m)) {
                $host['hostname'] = trim($m[1]);
            } elseif (preg_match('/^\s*Port\s+(\d+)$/i', $line, $m)) {
                $host['port'] = (int)$m[1];
            } elseif (preg_match('/^\s*User\s+(.+)$/i', $line, $m)) {
                $host['user'] = trim($m[1]);
            } elseif (preg_match('/^\s*IdentityFile\s+(.+)$/i', $line, $m)) {
                // Extract key name from path
                $keyPath = trim($m[1]);
                $host['ssh_key'] = basename($keyPath);
            }
        }

        return $host;
    }

    /**
     * Test SSH connectivity to a host.
     */
    public static function test(string $name): array
    {
        $host = self::get($name);
        if (!$host) {
            return ['success' => false, 'error' => "Host not found: {$name}"];
        }

        $startTime = microtime(true);

        // Use ssh with timeout
        $cmd = sprintf(
            'ssh -o BatchMode=yes -o ConnectTimeout=5 -o StrictHostKeyChecking=no %s "echo ok" 2>&1',
            escapeshellarg($name)
        );

        $output = trim(shell_exec($cmd) ?? '');
        $elapsed = round((microtime(true) - $startTime) * 1000);

        $success = ($output === 'ok');

        // Update last_seen_at if successful
        if ($success) {
            $stmt = self::db()->prepare('UPDATE vnodes SET last_seen_at = CURRENT_TIMESTAMP WHERE name = ?');
            $stmt->execute([$name]);
        }

        return [
            'success' => $success,
            'name' => $name,
            'hostname' => $host['hostname'],
            'elapsed_ms' => $elapsed,
            'output' => $output,
        ];
    }

    // === Group Management ===

    /**
     * Add host to a group.
     */
    public static function addToGroup(string $name, string $group): array
    {
        $host = self::get($name);
        if (!$host) {
            return ['success' => false, 'error' => "Host not found: {$name}"];
        }

        // Get or create group
        $stmt = self::db()->prepare('SELECT id FROM vnode_groups WHERE name = ?');
        $stmt->execute([$group]);
        $groupRow = $stmt->fetch();

        if (!$groupRow) {
            $stmt = self::db()->prepare('INSERT INTO vnode_groups (name) VALUES (?)');
            $stmt->execute([$group]);
            $groupId = self::db()->lastInsertId();
        } else {
            $groupId = $groupRow['id'];
        }

        // Add membership
        $stmt = self::db()->prepare(
            'INSERT OR IGNORE INTO vnode_group_members (vnode_id, group_id) VALUES (?, ?)'
        );
        $stmt->execute([$host['id'], $groupId]);

        return ['success' => true, 'name' => $name, 'group' => $group];
    }

    /**
     * Remove host from a group.
     */
    public static function removeFromGroup(string $name, string $group): array
    {
        $stmt = self::db()->prepare(
            'DELETE FROM vnode_group_members
             WHERE vnode_id = (SELECT id FROM vnodes WHERE name = ?)
             AND group_id = (SELECT id FROM vnode_groups WHERE name = ?)'
        );
        $stmt->execute([$name, $group]);

        return ['success' => true, 'name' => $name, 'group' => $group];
    }

    /**
     * List all groups.
     */
    public static function listGroups(): array
    {
        $stmt = self::db()->query(
            'SELECT g.*, COUNT(m.vnode_id) as member_count
             FROM vnode_groups g
             LEFT JOIN vnode_group_members m ON g.id = m.group_id
             GROUP BY g.id
             ORDER BY g.name'
        );
        return $stmt->fetchAll();
    }
}
