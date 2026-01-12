<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Lib;

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

/**
 * SSH Transport Layer
 *
 * Executes commands on remote hosts via SSH.
 * Uses connection pooling for fast sequential commands.
 */
final class Remote
{
    private static ?SSH2 $ssh = null;
    private static ?string $host = null;

    /**
     * Connect to remote host.
     */
    public static function connect(?string $host = null): void
    {
        $host = $host ?? Config::targetHost();

        if (self::$ssh !== null && self::$host === $host && self::$ssh->isConnected()) {
            return; // Already connected
        }

        self::$ssh = new SSH2($host, 22, 30);
        self::$host = $host;

        // Find SSH key
        $keyPath = getenv('HOME') . '/.ssh/keys/lan';
        if (!file_exists($keyPath)) {
            $keyPath = getenv('HOME') . '/.ssh/id_ed25519';
        }

        $key = PublicKeyLoader::load(file_get_contents($keyPath));

        if (!self::$ssh->login('root', $key)) {
            throw new \RuntimeException("SSH auth failed for {$host}");
        }
    }

    /**
     * Execute a command, return output.
     */
    public static function exec(string $cmd): string
    {
        self::connect();
        return trim(self::$ssh->exec($cmd) ?? '');
    }

    /**
     * Execute command, return success boolean.
     */
    public static function run(string $cmd): bool
    {
        self::exec($cmd);
        return (self::$ssh->getExitStatus() ?? 0) === 0;
    }

    /**
     * Check if path exists on remote.
     */
    public static function exists(string $path): bool
    {
        return self::run("[ -e \"{$path}\" ]");
    }

    /**
     * Get UID/GID of a path.
     */
    public static function stat(string $path): array
    {
        $result = self::exec("stat -c '%u %g' \"{$path}\" 2>/dev/null");
        if (!$result) {
            return ['uid' => 0, 'gid' => 0];
        }
        [$uid, $gid] = explode(' ', $result);
        return ['uid' => (int)$uid, 'gid' => (int)$gid];
    }

    /**
     * Get next available UID in range.
     */
    public static function nextUid(int $min = 1001, int $max = 1999): ?int
    {
        $result = self::exec("for i in \$(seq {$min} {$max}); do getent passwd \$i >/dev/null 2>&1 || { echo \$i; break; }; done");
        return $result ? (int)$result : null;
    }

    /**
     * Close connection.
     */
    public static function disconnect(): void
    {
        if (self::$ssh !== null) {
            self::$ssh->disconnect();
            self::$ssh = null;
            self::$host = null;
        }
    }
}
