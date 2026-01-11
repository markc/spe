-- NetServa 3.0 sysadm schema for SQLite
-- Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

CREATE TABLE IF NOT EXISTS vhosts (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    domain      TEXT NOT NULL UNIQUE,
    uname       TEXT NOT NULL DEFAULT '',
    uid         INTEGER NOT NULL,
    gid         INTEGER NOT NULL,
    aliases     TEXT DEFAULT '',
    active      INTEGER DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_vhosts_active ON vhosts(active);

CREATE TRIGGER IF NOT EXISTS vhosts_updated_at
    AFTER UPDATE ON vhosts
    FOR EACH ROW
    BEGIN
        UPDATE vhosts SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
    END;

CREATE TABLE IF NOT EXISTS vmails (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user        TEXT NOT NULL UNIQUE,
    pass        TEXT NOT NULL,
    home        TEXT NOT NULL,
    uid         INTEGER NOT NULL,
    gid         INTEGER NOT NULL,
    backend     TEXT,
    active      INTEGER DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_vmails_backend ON vmails(backend);
CREATE INDEX IF NOT EXISTS idx_vmails_active ON vmails(active);

CREATE TRIGGER IF NOT EXISTS vmails_updated_at
    AFTER UPDATE ON vmails
    FOR EACH ROW
    BEGIN
        UPDATE vmails SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
    END;

CREATE TABLE IF NOT EXISTS valias (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    source      TEXT NOT NULL UNIQUE,
    target      TEXT NOT NULL,
    active      INTEGER DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_valias_active ON valias(active);

CREATE TRIGGER IF NOT EXISTS valias_updated_at
    AFTER UPDATE ON valias
    FOR EACH ROW
    BEGIN
        UPDATE valias SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
    END;
