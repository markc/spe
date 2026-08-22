-- Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
-- Loaded automatically the first time Db opens a database that does not exist.

CREATE TABLE posts (
    id      INTEGER PRIMARY KEY AUTOINCREMENT,
    title   TEXT NOT NULL,
    slug    TEXT NOT NULL UNIQUE,
    body    TEXT NOT NULL DEFAULT '',
    created TEXT NOT NULL DEFAULT (datetime('now'))
);

INSERT INTO posts (title, slug, body) VALUES
    ('Welcome to chapter 07', 'welcome', 'This post lives in a SQLite database. Every query that touches it is a prepared statement.'),
    ('The Db class', 'the-db-class', 'Db is a thin subclass of PDO with create, read, update and delete helpers and a QueryType enum for the fetch shape.'),
    ('Try the CRUDL', 'try-the-crudl', 'Add, edit and delete posts from the Posts page. Writes only happen on a POST that carries the CSRF token.');
