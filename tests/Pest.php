<?php
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

/**
 * Execute a PHP file with mocked $_REQUEST and capture output
 */
function renderPage(string $path, array $request = []): string
{
    // Save and clear superglobals
    $savedRequest = $_REQUEST;
    $savedGet = $_GET;
    $savedPost = $_POST;

    $_REQUEST = $request;
    $_GET = $request;
    $_POST = [];

    ob_start();
    include $path;
    $output = ob_get_clean();

    // Restore superglobals
    $_REQUEST = $savedRequest;
    $_GET = $savedGet;
    $_POST = $savedPost;

    return $output;
}

/**
 * Get the 01-Simple index.php path
 */
function simpleIndexPath(): string
{
    return dirname(__DIR__) . '/01-Simple/public/index.php';
}

/**
 * Get the 02-Styled index.php path
 */
function styledIndexPath(): string
{
    return dirname(__DIR__) . '/02-Styled/public/index.php';
}

/**
 * Get the 03-Plugins index.php path
 */
function pluginsIndexPath(): string
{
    return dirname(__DIR__) . '/03-Plugins/public/index.php';
}
