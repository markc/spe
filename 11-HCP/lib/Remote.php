<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Lib;

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

/**
 * SSH Transport Layer
 *
 * Executes commands on remote hosts via SSH.
 * Features connection pooling for multiple hosts.
 */
final class Remote
{
    /** @var array<string, SSH2> Connection pool indexed by host name */
    private static array $pool = [];

    /** @var string|null Current active host */
    private static ?string $current = null;

    /**
     * Get or create connection to host.
     *
     * Uses SshHostOps to look up host details from database,
     * falls back to Config for default target.
     */
    public static function connect(?string $hostName = null): SSH2
    {
        // Resolve host name
        if ($hostName === null) {
            $hostName = Config::activeVnode() ?? 'default';
        }

        // Return pooled connection if valid
        if (isset(self::$pool[$hostName]) && self::$pool[$hostName]->isConnected()) {
            self::$current = $hostName;
            return self::$pool[$hostName];
        }

        // Look up host details from database
        $host = SshHostOps::get($hostName);

        if ($host) {
            $hostname = $host['hostname'];
            $port = (int)$host['port'];
            $user = $host['user'];
            $keyName = $host['ssh_key'];
        } else {
            // Fallback to direct hostname (IP or FQDN)
            $hostname = $hostName === 'default' ? Config::targetHost() : $hostName;
            $port = 22;
            $user = 'root';
            $keyName = null;
        }

        // Create connection
        $ssh = new SSH2($hostname, $port, 30);

        // Find SSH key
        $keyPath = null;
        if ($keyName) {
            $keyPath = Config::sshKeysDir() . '/' . $keyName;
        }
        if (!$keyPath || !file_exists($keyPath)) {
            $keyPath = Config::sshKeysDir() . '/lan';
        }
        if (!file_exists($keyPath)) {
            $keyPath = getenv('HOME') . '/.ssh/id_ed25519';
        }
        if (!file_exists($keyPath)) {
            $keyPath = getenv('HOME') . '/.ssh/id_rsa';
        }

        if (!file_exists($keyPath)) {
            throw new \RuntimeException("No SSH key found for {$hostName}");
        }

        $key = PublicKeyLoader::load(file_get_contents($keyPath));

        if (!$ssh->login($user, $key)) {
            throw new \RuntimeException("SSH auth failed for {$user}@{$hostname}:{$port}");
        }

        // Store in pool
        self::$pool[$hostName] = $ssh;
        self::$current = $hostName;

        return $ssh;
    }

    /**
     * Execute a command on current or specified host.
     */
    public static function exec(string $cmd, ?string $host = null): string
    {
        $ssh = self::connect($host);
        return trim($ssh->exec($cmd) ?? '');
    }

    /**
     * Execute command, return success boolean.
     */
    public static function run(string $cmd, ?string $host = null): bool
    {
        $ssh = self::connect($host);
        $ssh->exec($cmd);
        return ($ssh->getExitStatus() ?? 0) === 0;
    }

    /**
     * Execute command on multiple hosts in parallel.
     * Returns array of results indexed by host name.
     */
    public static function execMulti(string $cmd, array $hostNames): array
    {
        $results = [];

        // For now, sequential execution
        // TODO: Use Fiber or parallel extension for true parallel execution
        foreach ($hostNames as $name) {
            try {
                $results[$name] = [
                    'success' => true,
                    'output' => self::exec($cmd, $name),
                ];
            } catch (\Exception $e) {
                $results[$name] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Check if path exists on remote.
     */
    public static function exists(string $path, ?string $host = null): bool
    {
        return self::run("[ -e \"{$path}\" ]", $host);
    }

    /**
     * Get UID/GID of a path.
     */
    public static function stat(string $path, ?string $host = null): array
    {
        $result = self::exec("stat -c '%u %g' \"{$path}\" 2>/dev/null", $host);
        if (!$result) {
            return ['uid' => 0, 'gid' => 0];
        }
        [$uid, $gid] = explode(' ', $result);
        return ['uid' => (int)$uid, 'gid' => (int)$gid];
    }

    /**
     * Get next available UID in range.
     */
    public static function nextUid(int $min = 1001, int $max = 1999, ?string $host = null): ?int
    {
        $result = self::exec(
            "for i in \$(seq {$min} {$max}); do getent passwd \$i >/dev/null 2>&1 || { echo \$i; break; }; done",
            $host
        );
        return $result ? (int)$result : null;
    }

    /**
     * Get current active host name.
     */
    public static function currentHost(): ?string
    {
        return self::$current;
    }

    /**
     * Get connection pool status.
     */
    public static function poolStatus(): array
    {
        $status = [];
        foreach (self::$pool as $name => $ssh) {
            $status[$name] = $ssh->isConnected();
        }
        return $status;
    }

    /**
     * Disconnect a specific host.
     */
    public static function disconnect(?string $host = null): void
    {
        if ($host === null) {
            $host = self::$current;
        }

        if ($host && isset(self::$pool[$host])) {
            self::$pool[$host]->disconnect();
            unset(self::$pool[$host]);

            if (self::$current === $host) {
                self::$current = null;
            }
        }
    }

    /**
     * Disconnect all hosts.
     */
    public static function disconnectAll(): void
    {
        foreach (self::$pool as $ssh) {
            $ssh->disconnect();
        }
        self::$pool = [];
        self::$current = null;
    }
}
