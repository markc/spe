<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Lib;

/**
 * SSH Key Operations
 *
 * Manages SSH keys in ~/.ssh/keys/ directory.
 * Keys are stored as files (no database).
 */
final class SshKeyOps
{
    /**
     * List all SSH keys.
     */
    public static function list(): array
    {
        $keysDir = Config::sshKeysDir();
        if (!is_dir($keysDir)) {
            return [];
        }

        $keys = [];
        foreach (glob("{$keysDir}/*") as $file) {
            if (is_dir($file))
                continue;

            $name = basename($file);

            // Skip .pub files (we'll pair them with private keys)
            if (str_ends_with($name, '.pub'))
                continue;

            $keys[] = self::getKeyInfo($name);
        }

        // Sort by name
        usort($keys, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $keys;
    }

    /**
     * Get detailed info about a key.
     */
    public static function get(string $name): ?array
    {
        $keyPath = Config::sshKeysDir() . '/' . $name;
        if (!file_exists($keyPath)) {
            return null;
        }

        return self::getKeyInfo($name);
    }

    /**
     * Get key info (internal helper).
     */
    private static function getKeyInfo(string $name): array
    {
        $keysDir = Config::sshKeysDir();
        $keyPath = "{$keysDir}/{$name}";
        $pubPath = "{$keyPath}.pub";

        $info = [
            'name' => $name,
            'path' => $keyPath,
            'exists' => file_exists($keyPath),
            'has_pub' => file_exists($pubPath),
            'size' => file_exists($keyPath) ? filesize($keyPath) : 0,
            'modified' => file_exists($keyPath) ? date('Y-m-d H:i:s', filemtime($keyPath)) : null,
            'permissions' => file_exists($keyPath) ? substr(sprintf('%o', fileperms($keyPath)), -4) : null,
        ];

        // Extract key type and bits from public key
        if ($info['has_pub']) {
            $pubContent = file_get_contents($pubPath);
            $parts = explode(' ', $pubContent, 3);
            $info['type'] = $parts[0] ?? 'unknown';
            $info['comment'] = trim($parts[2] ?? '');

            // Get fingerprint
            $fingerprint = shell_exec('ssh-keygen -lf ' . escapeshellarg($pubPath) . ' 2>/dev/null');
            if ($fingerprint) {
                $fpParts = explode(' ', trim($fingerprint));
                $info['bits'] = (int) ($fpParts[0] ?? 0);
                $info['fingerprint'] = $fpParts[1] ?? '';
            }
        }

        return $info;
    }

    /**
     * Generate a new SSH key pair.
     */
    public static function generate(
        string $name,
        string $type = 'ed25519',
        ?string $comment = null,
        ?string $passphrase = null,
    ): array {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        if (!$name) {
            return ['success' => false, 'error' => 'Invalid key name'];
        }

        $keysDir = Config::sshKeysDir();

        // Ensure directory exists
        if (!is_dir($keysDir)) {
            mkdir($keysDir, 0700, true);
        }

        $keyPath = "{$keysDir}/{$name}";

        // Check if exists
        if (file_exists($keyPath)) {
            return ['success' => false, 'error' => "Key already exists: {$name}"];
        }

        // Validate type
        $validTypes = ['ed25519', 'rsa', 'ecdsa'];
        if (!in_array($type, $validTypes)) {
            return ['success' => false, 'error' => "Invalid key type: {$type}"];
        }

        // Build command
        $comment = $comment ?: "{$name}@" . gethostname();
        $cmd = sprintf(
            'ssh-keygen -t %s -f %s -C %s -N %s 2>&1',
            escapeshellarg($type),
            escapeshellarg($keyPath),
            escapeshellarg($comment),
            escapeshellarg($passphrase ?? ''),
        );

        // Add bits for RSA
        if ($type === 'rsa') {
            $cmd = sprintf(
                'ssh-keygen -t rsa -b 4096 -f %s -C %s -N %s 2>&1',
                escapeshellarg($keyPath),
                escapeshellarg($comment),
                escapeshellarg($passphrase ?? ''),
            );
        }

        $output = shell_exec($cmd);

        if (!file_exists($keyPath)) {
            return ['success' => false, 'error' => "Failed to generate key: {$output}"];
        }

        // Fix permissions
        chmod($keyPath, 0600);
        chmod("{$keyPath}.pub", 0644);

        return [
            'success' => true,
            'name' => $name,
            'type' => $type,
            'path' => $keyPath,
            'public_key' => trim(file_get_contents("{$keyPath}.pub")),
        ];
    }

    /**
     * Import an existing SSH key.
     */
    public static function import(string $name, string $privateKey, ?string $publicKey = null): array
    {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        if (!$name) {
            return ['success' => false, 'error' => 'Invalid key name'];
        }

        $keysDir = Config::sshKeysDir();

        // Ensure directory exists
        if (!is_dir($keysDir)) {
            mkdir($keysDir, 0700, true);
        }

        $keyPath = "{$keysDir}/{$name}";

        // Check if exists
        if (file_exists($keyPath)) {
            return ['success' => false, 'error' => "Key already exists: {$name}"];
        }

        // Validate private key format
        if (!str_contains($privateKey, 'PRIVATE KEY')) {
            return ['success' => false, 'error' => 'Invalid private key format'];
        }

        // Write private key
        file_put_contents($keyPath, $privateKey);
        chmod($keyPath, 0600);

        // Generate public key if not provided
        if (!$publicKey) {
            $cmd = sprintf('ssh-keygen -y -f %s 2>&1', escapeshellarg($keyPath));
            $publicKey = trim(shell_exec($cmd) ?? '');

            if (!$publicKey || str_contains($publicKey, 'error')) {
                unlink($keyPath);
                return [
                    'success' => false,
                    'error' => 'Failed to derive public key (invalid or passphrase-protected?)',
                ];
            }
        }

        // Write public key
        file_put_contents("{$keyPath}.pub", $publicKey . "\n");
        chmod("{$keyPath}.pub", 0644);

        return [
            'success' => true,
            'name' => $name,
            'path' => $keyPath,
            'public_key' => $publicKey,
        ];
    }

    /**
     * Delete an SSH key pair.
     */
    public static function del(string $name): array
    {
        $keysDir = Config::sshKeysDir();
        $keyPath = "{$keysDir}/{$name}";

        if (!file_exists($keyPath)) {
            return ['success' => false, 'error' => "Key not found: {$name}"];
        }

        // Check if key is in use by any vnode
        try {
            $db = new \PDO('sqlite:' . Config::sysadmDb(), null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $stmt = $db->prepare('SELECT name FROM vnodes WHERE ssh_key = ?');
            $stmt->execute([$name]);
            $users = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            if ($users) {
                return [
                    'success' => false,
                    'error' => 'Key in use by: ' . implode(', ', $users),
                ];
            }
        } catch (\Exception) {
            // DB not available, proceed with deletion
        }

        // Delete private key
        unlink($keyPath);

        // Delete public key if exists
        if (file_exists("{$keyPath}.pub")) {
            unlink("{$keyPath}.pub");
        }

        return ['success' => true, 'name' => $name];
    }

    /**
     * Rename an SSH key.
     */
    public static function rename(string $oldName, string $newName): array
    {
        $newName = preg_replace('/[^a-zA-Z0-9_-]/', '', $newName);
        if (!$newName) {
            return ['success' => false, 'error' => 'Invalid new key name'];
        }

        $keysDir = Config::sshKeysDir();
        $oldPath = "{$keysDir}/{$oldName}";
        $newPath = "{$keysDir}/{$newName}";

        if (!file_exists($oldPath)) {
            return ['success' => false, 'error' => "Key not found: {$oldName}"];
        }

        if (file_exists($newPath)) {
            return ['success' => false, 'error' => "Key already exists: {$newName}"];
        }

        // Rename private key
        rename($oldPath, $newPath);

        // Rename public key if exists
        if (file_exists("{$oldPath}.pub")) {
            rename("{$oldPath}.pub", "{$newPath}.pub");
        }

        // Update vnodes that use this key
        try {
            $db = new \PDO('sqlite:' . Config::sysadmDb(), null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $stmt = $db->prepare('UPDATE vnodes SET ssh_key = ? WHERE ssh_key = ?');
            $stmt->execute([$newName, $oldName]);

            // Regenerate SSH host files that use this key
            $stmt = $db->prepare('SELECT name FROM vnodes WHERE ssh_key = ?');
            $stmt->execute([$newName]);
            foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $vnode) {
                SshHostOps::generateHostFile($vnode);
            }
        } catch (\Exception) {
            // DB not available
        }

        return ['success' => true, 'old_name' => $oldName, 'new_name' => $newName];
    }

    /**
     * Get public key content.
     */
    public static function getPublicKey(string $name): ?string
    {
        $pubPath = Config::sshKeysDir() . "/{$name}.pub";
        if (!file_exists($pubPath)) {
            return null;
        }
        return trim(file_get_contents($pubPath));
    }

    /**
     * Copy public key to a remote host's authorized_keys.
     */
    public static function copyToHost(string $keyName, string $hostName): array
    {
        $pubKey = self::getPublicKey($keyName);
        if (!$pubKey) {
            return ['success' => false, 'error' => "Key not found: {$keyName}"];
        }

        $host = SshHostOps::get($hostName);
        if (!$host) {
            return ['success' => false, 'error' => "Host not found: {$hostName}"];
        }

        // Use ssh-copy-id style approach
        $cmd = sprintf(
            'ssh -o BatchMode=yes -o ConnectTimeout=10 %s "mkdir -p ~/.ssh && chmod 700 ~/.ssh && echo %s >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys" 2>&1',
            escapeshellarg($hostName),
            escapeshellarg($pubKey),
        );

        $output = trim(shell_exec($cmd) ?? '');

        // Check if successful
        if ($output && !str_contains($output, 'Permission denied')) {
            return ['success' => false, 'error' => $output];
        }

        return [
            'success' => true,
            'key' => $keyName,
            'host' => $hostName,
            'message' => "Public key copied to {$hostName}",
        ];
    }

    /**
     * List hosts using a specific key.
     */
    public static function getHostsUsingKey(string $keyName): array
    {
        try {
            $db = new \PDO('sqlite:' . Config::sysadmDb(), null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
            $stmt = $db->prepare('SELECT name, hostname FROM vnodes WHERE ssh_key = ?');
            $stmt->execute([$keyName]);
            return $stmt->fetchAll();
        } catch (\Exception) {
            return [];
        }
    }
}
