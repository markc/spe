-- Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
-- Passwords below are the bcrypt hashes of "admin" and "user" — demo credentials only.

CREATE TABLE users (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    email    TEXT NOT NULL UNIQUE,
    name     TEXT NOT NULL,
    password TEXT NOT NULL,
    role     TEXT NOT NULL DEFAULT 'User',
    created  TEXT NOT NULL DEFAULT (datetime('now'))
);

INSERT INTO users (email, name, password, role) VALUES
    ('admin@example.com', 'Admin', '$2y$12$/oqiNQleGdNGh9/gyo4CI.2jxVqB1VizlepogV7eThbffzKRmBtV6', 'Admin'),
    ('user@example.com',  'User',  '$2y$12$6sE80QPHbL8U4IxU3CslC.Yqdmaocq/Wa9fqt/o4YjVvytnmC4.Rm', 'User');

CREATE TABLE user_tokens (
    selector       TEXT PRIMARY KEY,
    validator_hash TEXT NOT NULL,
    user_id        INTEGER NOT NULL,
    expires        TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE posts (
    id      INTEGER PRIMARY KEY AUTOINCREMENT,
    title   TEXT NOT NULL,
    slug    TEXT NOT NULL UNIQUE,
    body    TEXT NOT NULL DEFAULT '',
    created TEXT NOT NULL DEFAULT (datetime('now'))
);

INSERT INTO posts (title, slug, body) VALUES
    ('Welcome to chapter 08', 'welcome', 'Reading these posts is public. Signing in as an admin adds the New, Edit and Delete controls.'),
    ('Roles', 'roles', 'Role is an ordered enum; can() compares levels. Posts writes require Admin, the Users page requires Admin, reading is open to everyone.'),
    ('Sessions', 'sessions', 'Logging in regenerates the session id; remember-me stores a hashed validator that rotates on every use.');
