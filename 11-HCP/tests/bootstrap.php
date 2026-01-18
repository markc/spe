<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

/**
 * Test Bootstrap
 *
 * Loads both the parent SPE framework autoloader (for SPE\App classes)
 * and the local 11-HCP autoloader.
 */

// Set test environment variables first
$_ENV['HCP_DB'] = ':memory:';
$_ENV['SYSADM_DB'] = ':memory:';
putenv('HCP_DB=:memory:');
putenv('SYSADM_DB=:memory:');

// Load local autoloader first
$localAutoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($localAutoloader)) {
    require_once $localAutoloader;
}

// Load parent SPE autoloader (provides SPE\App\* classes)
$parentAutoloader = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($parentAutoloader)) {
    require_once $parentAutoloader;
}
