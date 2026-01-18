<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

/**
 * Architecture Tests
 *
 * These tests enforce coding standards and architectural patterns
 * across the 11-HCP codebase.
 */

describe('Namespace conventions', function () {
    arch('Core classes use SPE\HCP\Core namespace')
        ->expect('SPE\HCP\Core')
        ->toBeClasses();

    arch('Lib classes use SPE\HCP\Lib namespace')
        ->expect('SPE\HCP\Lib')
        ->toBeClasses();
});

describe('Core class design', function () {
    arch('Concrete core classes are final')
        ->expect('SPE\HCP\Core\Ctx')
        ->toBeFinal();

    arch('HcpDb class is final')
        ->expect('SPE\HCP\Core\HcpDb')
        ->toBeFinal();

    arch('HcpDb class extends PDO')
        ->expect('SPE\HCP\Core\HcpDb')
        ->toExtend(PDO::class);

    arch('Shell class is final')
        ->expect('SPE\HCP\Core\Shell')
        ->toBeFinal();

    arch('Plugin is an abstract base class')
        ->expect('SPE\HCP\Core\Plugin')
        ->toBeAbstract();

    arch('Theme is an abstract base class')
        ->expect('SPE\HCP\Core\Theme')
        ->toBeAbstract();
});

describe('Lib class design', function () {
    arch('Config class is final')
        ->expect('SPE\HCP\Lib\Config')
        ->toBeFinal();
});

describe('Code quality', function () {
    arch('source files use strict types')
        ->expect('SPE\HCP')
        ->toUseStrictTypes();

    arch('no Laravel/Symfony debugging functions in source')
        ->expect('SPE\HCP')
        ->not->toUse(['dd', 'dump', 'var_dump']);
});

describe('Plugin architecture', function () {
    arch('Plugin models exist in Plugins namespace')
        ->expect('SPE\HCP\Plugins')
        ->toBeClasses();
});

describe('Theme architecture', function () {
    arch('Theme classes exist in Themes namespace')
        ->expect('SPE\HCP\Themes')
        ->toBeClasses();
});
