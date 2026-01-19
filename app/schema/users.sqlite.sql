-- SPE Users Schema (SQLite)
-- Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    grp INTEGER NOT NULL DEFAULT 0,
    acl INTEGER NOT NULL DEFAULT 2,
    login TEXT UNIQUE NOT NULL,
    fname TEXT DEFAULT '',
    lname TEXT DEFAULT '',
    altemail TEXT DEFAULT '',
    webpw TEXT DEFAULT '',
    otp TEXT DEFAULT '',
    otpttl INTEGER DEFAULT 0,
    cookie TEXT DEFAULT '',
    anote TEXT DEFAULT '',
    updated DATETIME,
    created DATETIME
);

-- Seed data (passwords are empty, set on first login)
INSERT INTO users (grp, acl, login, fname, lname, created, updated) VALUES
    (1, 0, 'admin@example.org', 'Admin', 'User', datetime('now'), datetime('now')),
    (1, 2, 'user@example.org', 'Normal', 'User', datetime('now'), datetime('now'));
