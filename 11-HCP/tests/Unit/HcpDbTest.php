<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

/**
 * Test the database wrapper functionality using a standalone test PDO
 * This avoids the complexity of HcpDb's constructor which depends on file paths.
 *
 * Note: QueryType enum tests are skipped if SPE\App is not available.
 */

beforeEach(function () {
    // Create a fresh in-memory database for each test
    $this->db = new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Create test table
    $this->db->exec('
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            login TEXT UNIQUE NOT NULL,
            fname TEXT DEFAULT "",
            lname TEXT DEFAULT "",
            acl INTEGER NOT NULL DEFAULT 2
        )
    ');
});

describe('QueryType enum', function () {
    it('has All case', function () {
        if (!class_exists('SPE\App\QueryType')) {
            $this->markTestSkipped('SPE\App\QueryType not available - run from project root');
        }
        $queryType = \SPE\App\QueryType::All;
        expect($queryType)->toBeInstanceOf(\SPE\App\QueryType::class);
        expect($queryType->value)->toBe('all');
    });

    it('has One case', function () {
        if (!class_exists('SPE\App\QueryType')) {
            $this->markTestSkipped('SPE\App\QueryType not available - run from project root');
        }
        $queryType = \SPE\App\QueryType::One;
        expect($queryType)->toBeInstanceOf(\SPE\App\QueryType::class);
        expect($queryType->value)->toBe('one');
    });

    it('has Col case', function () {
        if (!class_exists('SPE\App\QueryType')) {
            $this->markTestSkipped('SPE\App\QueryType not available - run from project root');
        }
        $queryType = \SPE\App\QueryType::Col;
        expect($queryType)->toBeInstanceOf(\SPE\App\QueryType::class);
        expect($queryType->value)->toBe('col');
    });
});

describe('Database CRUD operations', function () {
    it('can insert a record', function () {
        $stmt = $this->db->prepare('INSERT INTO users (login, fname, lname) VALUES (:login, :fname, :lname)');
        $stmt->execute(['login' => 'test@example.com', 'fname' => 'Test', 'lname' => 'User']);

        $id = (int) $this->db->lastInsertId();

        expect($id)->toBe(1);
    });

    it('can read all records', function () {
        // Insert test data
        $this->db->exec("INSERT INTO users (login, fname) VALUES ('a@test.com', 'Alice')");
        $this->db->exec("INSERT INTO users (login, fname) VALUES ('b@test.com', 'Bob')");

        $stmt = $this->db->query('SELECT * FROM users');
        $results = $stmt->fetchAll();

        expect($results)->toHaveCount(2);
        expect($results[0]['login'])->toBe('a@test.com');
        expect($results[1]['login'])->toBe('b@test.com');
    });

    it('can read a single record', function () {
        $this->db->exec("INSERT INTO users (login, fname) VALUES ('single@test.com', 'Single')");

        $stmt = $this->db->prepare('SELECT * FROM users WHERE login = :login');
        $stmt->execute(['login' => 'single@test.com']);
        $result = $stmt->fetch();

        expect($result)->toBeArray();
        expect($result['fname'])->toBe('Single');
    });

    it('can read a single column value', function () {
        $this->db->exec("INSERT INTO users (login, fname) VALUES ('col@test.com', 'Column')");

        $stmt = $this->db->prepare('SELECT fname FROM users WHERE login = :login');
        $stmt->execute(['login' => 'col@test.com']);
        $result = $stmt->fetchColumn();

        expect($result)->toBe('Column');
    });

    it('can update a record', function () {
        $this->db->exec("INSERT INTO users (login, fname) VALUES ('update@test.com', 'Before')");

        $stmt = $this->db->prepare('UPDATE users SET fname = :fname WHERE login = :login');
        $result = $stmt->execute(['fname' => 'After', 'login' => 'update@test.com']);

        expect($result)->toBeTrue();

        $stmt = $this->db->prepare('SELECT fname FROM users WHERE login = :login');
        $stmt->execute(['login' => 'update@test.com']);
        $fname = $stmt->fetchColumn();

        expect($fname)->toBe('After');
    });

    it('can delete a record', function () {
        $this->db->exec("INSERT INTO users (login, fname) VALUES ('delete@test.com', 'Delete')");

        $stmt = $this->db->prepare('DELETE FROM users WHERE login = :login');
        $result = $stmt->execute(['login' => 'delete@test.com']);

        expect($result)->toBeTrue();

        $stmt = $this->db->query('SELECT COUNT(*) FROM users');
        $count = $stmt->fetchColumn();

        expect((int) $count)->toBe(0);
    });
});

describe('Database parameter binding', function () {
    it('binds string parameters correctly', function () {
        $stmt = $this->db->prepare('INSERT INTO users (login, fname) VALUES (:login, :fname)');
        $stmt->execute(['login' => 'string@test.com', 'fname' => "O'Brien"]);

        $stmt = $this->db->prepare('SELECT fname FROM users WHERE login = :login');
        $stmt->execute(['login' => 'string@test.com']);

        expect($stmt->fetchColumn())->toBe("O'Brien");
    });

    it('binds integer parameters correctly', function () {
        $stmt = $this->db->prepare('INSERT INTO users (login, acl) VALUES (:login, :acl)');
        $stmt->bindValue(':login', 'int@test.com', PDO::PARAM_STR);
        $stmt->bindValue(':acl', 0, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->db->prepare('SELECT acl FROM users WHERE login = :login');
        $stmt->execute(['login' => 'int@test.com']);

        expect((int) $stmt->fetchColumn())->toBe(0);
    });
});

describe('Database constraints', function () {
    it('enforces unique constraint on login', function () {
        $this->db->exec("INSERT INTO users (login) VALUES ('unique@test.com')");

        expect(fn() => $this->db->exec("INSERT INTO users (login) VALUES ('unique@test.com')"))
            ->toThrow(PDOException::class);
    });

    it('enforces NOT NULL constraint on login', function () {
        expect(fn() => $this->db->exec("INSERT INTO users (fname) VALUES ('NoLogin')"))
            ->toThrow(PDOException::class);
    });
});
