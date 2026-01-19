-- HCP Application Schema (SQLite)
-- Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
-- Based on SPE 09-Blog users and posts schemas

-- Users table for HCP authentication
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

-- Posts table for HCP documentation/help pages
CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT UNIQUE,
    content TEXT,
    excerpt TEXT,
    featured_image TEXT,
    icon TEXT,
    author TEXT,
    author_id INTEGER,
    type TEXT DEFAULT 'post',
    created DATETIME,
    updated DATETIME
);

-- Categories for organizing posts
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE,
    description TEXT
);

-- Junction table for post-category relationships
CREATE TABLE IF NOT EXISTS post_categories (
    post_id INTEGER,
    category_id INTEGER,
    PRIMARY KEY (post_id, category_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Seed data: Default admin user (password set on first login via forgot password)
-- ACL levels: 0=Admin, 1=Manager, 2=User, 3=Suspended, 9=Anonymous
INSERT INTO users (grp, acl, login, fname, lname, created, updated) VALUES
    (1, 0, 'admin@localhost', 'System', 'Admin', datetime('now'), datetime('now'));

-- Seed data: Default categories
INSERT INTO categories (name, slug, description) VALUES
    ('Help', 'help', 'HCP help documentation'),
    ('News', 'news', 'HCP announcements');

-- Seed data: Default help pages
INSERT INTO posts (title, slug, content, icon, type, author, created, updated) VALUES
    ('Dashboard', 'dashboard', 'The dashboard shows system overview including disk usage, memory, load average, and service status.', '📊', 'doc', 'admin', datetime('now'), datetime('now')),
    ('Virtual Hosts', 'vhosts', 'Manage web hosting domains. Each vhost gets its own directory, nginx config, and optional SSL certificate.', '🌐', 'doc', 'admin', datetime('now'), datetime('now')),
    ('Mailboxes', 'vmails', 'Create and manage email accounts. Mailboxes are stored in Maildir format under each vhost home directory.', '📧', 'doc', 'admin', datetime('now'), datetime('now')),
    ('Mail Aliases', 'valias', 'Configure mail forwarding and catch-all addresses. Aliases can forward to multiple recipients.', '🔗', 'doc', 'admin', datetime('now'), datetime('now'));

INSERT INTO post_categories (post_id, category_id) VALUES (1, 1), (2, 1), (3, 1), (4, 1);
