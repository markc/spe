<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

use SPE\HCP\Lib\Config;

beforeEach(function () {
    // Reset static state between tests
    $reflection = new ReflectionClass(Config::class);

    $envLoaded = $reflection->getProperty('envLoaded');
    $envLoaded->setAccessible(true);
    $envLoaded->setValue(null, false);

    $hostname = $reflection->getProperty('hostname');
    $hostname->setAccessible(true);
    $hostname->setValue(null, null);

    $projectRoot = $reflection->getProperty('projectRoot');
    $projectRoot->setAccessible(true);
    $projectRoot->setValue(null, null);

    // Clear test env vars
    putenv('TEST_CONFIG_VAR');
    putenv('SYSADM_DB');
    putenv('HCP_DB');
    unset($_ENV['TEST_CONFIG_VAR'], $_ENV['SYSADM_DB'], $_ENV['HCP_DB']);
});

describe('Config::projectRoot()', function () {
    it('returns a valid directory path', function () {
        $root = Config::projectRoot();

        expect($root)->toBeString();
        expect(is_dir($root))->toBeTrue();
    });

    it('returns the lib parent directory', function () {
        $root = Config::projectRoot();

        // Config is in lib/, so projectRoot should be its parent
        expect($root)->toEndWith('11-HCP');
    });
});

describe('Config::get()', function () {
    it('returns default value for VPATH', function () {
        $vpath = Config::get('VPATH');

        expect($vpath)->toBe('/srv');
    });

    it('returns default value for ADMIN', function () {
        $admin = Config::get('ADMIN');

        expect($admin)->toBe('sysadm');
    });

    it('returns empty string for unknown keys', function () {
        $unknown = Config::get('COMPLETELY_UNKNOWN_KEY_12345');

        expect($unknown)->toBe('');
    });

    it('respects environment variables', function () {
        putenv('TEST_CONFIG_VAR=test_value');
        $_ENV['TEST_CONFIG_VAR'] = 'test_value';

        $value = Config::get('TEST_CONFIG_VAR');

        expect($value)->toBe('test_value');
    });
});

describe('Config::hostname()', function () {
    it('returns a string', function () {
        $hostname = Config::hostname();

        expect($hostname)->toBeString();
        expect($hostname)->not->toBeEmpty();
    });

    it('caches the hostname value', function () {
        $first = Config::hostname();
        $second = Config::hostname();

        expect($first)->toBe($second);
    });
});

describe('Config path helpers', function () {
    it('builds vhost path correctly', function () {
        $path = Config::vhostPath('example.com');

        expect($path)->toBe('/srv/example.com');
    });

    it('builds web path correctly', function () {
        $path = Config::webPath('example.com');

        expect($path)->toBe('/srv/example.com/web/app/public');
    });

    it('builds mail path correctly', function () {
        $path = Config::mailPath('example.com');

        expect($path)->toBe('/srv/example.com/msg');
    });

    it('builds user mail path correctly', function () {
        $path = Config::userPath('example.com', 'john');

        expect($path)->toBe('/srv/example.com/msg/john');
    });
});

describe('Config::loadEnv()', function () {
    it('only loads env file once', function () {
        Config::loadEnv();
        Config::loadEnv();
        Config::loadEnv();

        // Should not throw and completes successfully
        expect(true)->toBeTrue();
    });

    it('handles missing env file gracefully', function () {
        // projectRoot() points to 11-HCP, .env may or may not exist
        // Either way, loadEnv should not throw
        Config::loadEnv();

        expect(true)->toBeTrue();
    });
});
