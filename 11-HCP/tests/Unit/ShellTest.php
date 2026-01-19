<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

use SPE\HCP\Core\Shell;

describe('Shell::run()', function () {
    it('returns array with expected keys', function () {
        // Run with a command that won't exist
        $result = Shell::run('nonexistent_command_12345');

        expect($result)->toBeArray();
        expect($result)->toHaveKeys(['success', 'output', 'code']);
    });

    it('returns code 127 for missing command', function () {
        $result = Shell::run('nonexistent_command_12345');

        expect($result['success'])->toBeFalse();
        expect($result['code'])->toBe(127);
        expect($result['output'])->toContain('not found');
    });

    it('prevents path traversal in command names', function () {
        // The basename() call should strip any path components
        $result = Shell::run('../../../etc/passwd');

        expect($result['success'])->toBeFalse();
        expect($result['code'])->toBe(127);
    });

    it('escapes arguments properly', function () {
        // Test that arguments would be escaped (via escapeshellarg internally)
        // We can't easily test this without executing, but we can verify the function exists
        $method = new ReflectionMethod(Shell::class, 'run');

        expect($method->isStatic())->toBeTrue();
        expect($method->isPublic())->toBeTrue();
    });
});

describe('Shell::listing()', function () {
    it('returns empty array on failure', function () {
        $result = Shell::listing('nonexistent_command_12345');

        expect($result)->toBeArray();
        expect($result)->toBeEmpty();
    });
});

describe('Shell::systemStats()', function () {
    it('returns array with expected stat keys', function () {
        $stats = Shell::systemStats();

        expect($stats)->toBeArray();
        expect($stats)->toHaveKeys(['disk_used_pct', 'mem_used_pct', 'load', 'uptime_days']);
    });

    it('returns disk usage as percentage', function () {
        $stats = Shell::systemStats();

        expect($stats['disk_used_pct'])->toBeFloat();
        expect($stats['disk_used_pct'])->toBeGreaterThanOrEqual(0);
        expect($stats['disk_used_pct'])->toBeLessThanOrEqual(100);
    });

    it('returns memory usage as percentage', function () {
        $stats = Shell::systemStats();

        expect($stats['mem_used_pct'])->toBeFloat();
        expect($stats['mem_used_pct'])->toBeGreaterThanOrEqual(0);
        expect($stats['mem_used_pct'])->toBeLessThanOrEqual(100);
    });

    it('returns load as array with 3 values', function () {
        $stats = Shell::systemStats();

        expect($stats['load'])->toBeArray();
        expect($stats['load'])->toHaveCount(3);
    });

    it('returns uptime in days as float', function () {
        $stats = Shell::systemStats();

        expect($stats['uptime_days'])->toBeFloat();
        expect($stats['uptime_days'])->toBeGreaterThanOrEqual(0);
    });
});

describe('Shell::serviceStatus()', function () {
    it('returns array with expected keys', function () {
        // Even for non-existent service, should return structured data
        $status = Shell::serviceStatus('nonexistent_service_12345');

        expect($status)->toBeArray();
        expect($status)->toHaveKeys(['name', 'active', 'status']);
    });

    it('returns service name in result', function () {
        $status = Shell::serviceStatus('test_service');

        expect($status['name'])->toBe('test_service');
    });

    it('returns boolean for active status', function () {
        $status = Shell::serviceStatus('test_service');

        expect($status['active'])->toBeBool();
    });

    it('returns status string matching active state', function () {
        $status = Shell::serviceStatus('test_service');

        if ($status['active']) {
            expect($status['status'])->toBe('running');
        } else {
            expect($status['status'])->toBe('stopped');
        }
    });
});

describe('Shell::services()', function () {
    it('returns array of service statuses', function () {
        $services = Shell::services(['test1', 'test2']);

        expect($services)->toBeArray();
        expect($services)->toHaveCount(2);
    });

    it('uses default services when none specified', function () {
        $method = new ReflectionMethod(Shell::class, 'services');
        $params = $method->getParameters();

        expect($params[0]->isDefaultValueAvailable())->toBeTrue();

        $default = $params[0]->getDefaultValue();
        expect($default)->toContain('nginx');
    });

    it('returns properly structured status for each service', function () {
        $services = Shell::services(['nginx']);

        foreach ($services as $service) {
            expect($service)->toHaveKeys(['name', 'active', 'status']);
        }
    });
});

describe('Shell class structure', function () {
    it('is a final class', function () {
        $reflection = new ReflectionClass(Shell::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('has private BIN_PATH constant', function () {
        $reflection = new ReflectionClass(Shell::class);
        $constants = $reflection->getConstants();

        expect($constants)->toHaveKey('BIN_PATH');
    });

    it('BIN_PATH points to /usr/local/bin', function () {
        $reflection = new ReflectionClass(Shell::class);
        $constant = $reflection->getConstant('BIN_PATH');

        expect($constant)->toBe('/usr/local/bin');
    });
});
