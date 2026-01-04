<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Core;

/**
 * Shell command wrapper for hosting management scripts.
 * All commands run via sudo from /usr/local/bin/
 */
final class Shell
{
    private const string BIN_PATH = '/usr/local/bin';

    /**
     * Execute a shell command with arguments.
     *
     * @param string $cmd Command name (e.g., 'addvhost', 'delvmail')
     * @param array $args Arguments to pass to command
     * @return array{success: bool, output: string, code: int}
     */
    public static function run(string $cmd, array $args = []): array
    {
        $cmdPath = self::BIN_PATH . '/' . basename($cmd); // Prevent path traversal

        if (!file_exists($cmdPath)) {
            return ['success' => false, 'output' => "Command not found: {$cmd}", 'code' => 127];
        }

        $escaped = array_map('escapeshellarg', $args);
        $fullCmd = "sudo {$cmdPath} " . implode(' ', $escaped) . ' 2>&1';

        $output = [];
        $code = 0;
        exec($fullCmd, $output, $code);

        return [
            'success' => $code === 0,
            'output' => implode("\n", $output),
            'code' => $code
        ];
    }

    /**
     * List items using a 'sh*' command (shvhost, shvmail, etc.)
     * Parses tabular output into array of arrays.
     */
    public static function listing(string $cmd, array $args = []): array
    {
        $result = self::run($cmd, $args);
        if (!$result['success']) {
            return [];
        }

        $lines = array_filter(explode("\n", trim($result['output'])));
        $items = [];

        foreach ($lines as $line) {
            // Most sh* commands output tab or space-separated values
            $items[] = preg_split('/\s+/', trim($line));
        }

        return $items;
    }

    /**
     * Get system stats (disk, memory, load, uptime).
     */
    public static function systemStats(): array
    {
        $disk = disk_free_space('/') / disk_total_space('/') * 100;

        $meminfo = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
        preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $avail);
        $memUsed = 100 - (($avail[1] ?? 0) / ($total[1] ?? 1) * 100);

        $load = sys_getloadavg();
        $uptime = (int)(file_get_contents('/proc/uptime') ?: 0);

        return [
            'disk_used_pct' => round(100 - $disk, 1),
            'mem_used_pct' => round($memUsed, 1),
            'load' => $load,
            'uptime_days' => round($uptime / 86400, 1),
        ];
    }

    /**
     * Get service status via systemctl.
     */
    public static function serviceStatus(string $service): array
    {
        $result = self::run('systemctl', ['is-active', $service]);
        $active = trim($result['output']) === 'active';

        return [
            'name' => $service,
            'active' => $active,
            'status' => $active ? 'running' : 'stopped'
        ];
    }

    /**
     * Get multiple service statuses.
     */
    public static function services(array $names = ['nginx', 'php8.3-fpm', 'mariadb', 'postfix', 'dovecot']): array
    {
        return array_map(fn($s) => self::serviceStatus($s), $names);
    }
}
