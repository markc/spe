<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure passed to your test functions is always bound to a specific
| PHPUnit test case class. By default, that class is "PHPUnit\Framework\TestCase".
| If you need to change this, use the "pest()->extend()" function.
|
*/

// pest()->extend(Tests\TestCase::class);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| Add custom expectations here. You can create custom expectations using
| the "expect()->extend()" function.
|
*/

// expect()->extend('toBeOne', function () {
//     return $this->toBe(1);
// });

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| Add global test helper functions here. Functions defined here are
| automatically available in all test files.
|
*/

/**
 * Create an in-memory SQLite database with HCP schema.
 */
function createTestDatabase(): PDO
{
    $db = new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Create users table
    $db->exec('
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            grp INTEGER NOT NULL DEFAULT 0,
            acl INTEGER NOT NULL DEFAULT 2,
            login TEXT UNIQUE NOT NULL,
            fname TEXT DEFAULT "",
            lname TEXT DEFAULT "",
            altemail TEXT DEFAULT "",
            webpw TEXT DEFAULT "",
            otp TEXT DEFAULT "",
            otpttl INTEGER DEFAULT 0,
            cookie TEXT DEFAULT "",
            anote TEXT DEFAULT "",
            updated DATETIME,
            created DATETIME
        )
    ');

    // Create posts table
    $db->exec('
        CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT UNIQUE,
            content TEXT,
            excerpt TEXT,
            featured_image TEXT,
            icon TEXT,
            author TEXT,
            author_id INTEGER,
            type TEXT DEFAULT "post",
            created DATETIME,
            updated DATETIME
        )
    ');

    // Create categories table
    $db->exec('
        CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT UNIQUE,
            description TEXT
        )
    ');

    return $db;
}

/**
 * Get the path to a test fixture file.
 */
function fixture(string $name): string
{
    return __DIR__ . '/tests/fixtures/' . $name;
}
