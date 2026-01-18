<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

/*
|--------------------------------------------------------------------------
| Test Suite Bindings
|--------------------------------------------------------------------------
|
| You may bind custom test case classes here for different test suites.
| For example, Feature tests can use a TestCase with app bootstrapping.
|
*/

// pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| Custom expectations for the HCP test suite.
|
*/

expect()->extend('toBeValidEmail', function () {
    return $this->toMatch('/^[^@\s]+@[^@\s]+\.[^@\s]+$/');
});

expect()->extend('toBeValidDomain', function () {
    return $this->toMatch('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/i');
});
