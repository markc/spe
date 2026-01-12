<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Lib;

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

/**
 * Privilege Escalation via SSH
 *
 * Executes commands as root via SSH - works for both local and remote hosts.
 * Uses phpseclib3 for SSH connections with key-based authentication.
 *
 * Usage:
 *   Exec::run('addvmail', ['user@domain.tld', 'password']);
 *   Exec::run('shvhost', ['domain.tld'], 'remote.server.com');
 */
final class Exec
{
    private const string BIN_PATH = '/usr/local/bin';
    private const string SSH_KEY_PATH = '/root/.ssh/id_ed25519';
    private const string SSH_KEY_FALLBACK = '/.ssh/keys/lan';
    private const string SSH_USER = 'root';
    private const int SSH_PORT = 22;
    private const int SSH_TIMEOUT = 30;

    private static array $connections = [];

    /**
     * Execute a command with appropriate privilege escalation.
     *
     * @param string $cmd Command name (e.g., 'addvmail')
     * @param array $args Arguments to pass
     * @param string|null $host Target host (null = localhost)
     * @return array{success: bool, output: string, code: int}
     */
    public static function run(string $cmd, array $args = [], ?string $host = null): array
    {
        // CLI as root - direct execution (for testing/cron)
        if (php_sapi_name() === 'cli' && posix_geteuid() === 0) {
            return self::directExec($cmd, $args);
        }

        // SSH execution (web UI or non-root CLI)
        $host = $host ?? Config::targetHost();
        return self::sshExec($host, $cmd, $args);
    }

    /**
     * Execute command directly (when already root).
     */
    private static function directExec(string $cmd, array $args): array
    {
        $cmdPath = self::BIN_PATH . '/' . basename($cmd);

        if (!file_exists($cmdPath)) {
            return ['success' => false, 'output' => "Command not found: {$cmd}", 'code' => 127];
        }

        $escaped = array_map('escapeshellarg', $args);
        $fullCmd = $cmdPath . ' ' . implode(' ', $escaped) . ' 2>&1';

        $output = [];
        $code = 0;
        exec($fullCmd, $output, $code);

        return [
            'success' => $code === 0,
            'output' => implode("\n", $output),
            'code' => $code,
        ];
    }

    /**
     * Execute command via SSH.
     */
    private static function sshExec(string $host, string $cmd, array $args): array
    {
        try {
            $ssh = self::getConnection($host);

            $cmdPath = self::BIN_PATH . '/' . basename($cmd);
            $escaped = array_map('escapeshellarg', $args);
            $fullCmd = $cmdPath . ' ' . implode(' ', $escaped);

            $output = $ssh->exec($fullCmd);
            $code = $ssh->getExitStatus() ?? 0;

            return [
                'success' => $code === 0,
                'output' => trim($output),
                'code' => $code,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => 'SSH error: ' . $e->getMessage(),
                'code' => 255,
            ];
        }
    }

    /**
     * Get or create SSH connection (connection pooling).
     */
    private static function getConnection(string $host): SSH2
    {
        $key = "{$host}:" . self::SSH_PORT;

        if (isset(self::$connections[$key]) && self::$connections[$key]->isConnected()) {
            return self::$connections[$key];
        }

        $ssh = new SSH2($host, self::SSH_PORT, self::SSH_TIMEOUT);

        // Load private key
        $keyPath = self::SSH_KEY_PATH;
        if (!file_exists($keyPath)) {
            // Fallback to user's key
            $keyPath = getenv('HOME') . self::SSH_KEY_FALLBACK;
        }

        if (!file_exists($keyPath)) {
            throw new \RuntimeException("SSH key not found: {$keyPath}");
        }

        $privateKey = PublicKeyLoader::load(file_get_contents($keyPath));

        if (!$ssh->login(self::SSH_USER, $privateKey)) {
            throw new \RuntimeException("SSH authentication failed for {$host}");
        }

        self::$connections[$key] = $ssh;
        return $ssh;
    }

    /**
     * Close all SSH connections.
     */
    public static function disconnect(): void
    {
        foreach (self::$connections as $ssh) {
            if ($ssh->isConnected()) {
                $ssh->disconnect();
            }
        }
        self::$connections = [];
    }

    /**
     * Execute multiple commands in sequence.
     */
    public static function batch(array $commands, ?string $host = null): array
    {
        $results = [];
        foreach ($commands as $name => $cmdArgs) {
            [$cmd, $args] = $cmdArgs;
            $results[$name] = self::run($cmd, $args, $host);

            // Stop on first failure
            if (!$results[$name]['success']) {
                break;
            }
        }
        return $results;
    }

    /**
     * Check if host is reachable via SSH.
     */
    public static function ping(string $host): bool
    {
        try {
            $ssh = self::getConnection($host);
            $output = $ssh->exec('echo ok');
            return trim($output) === 'ok';
        } catch (\Exception $e) {
            return false;
        }
    }
}
