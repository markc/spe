-- HCP Unified Database Schema
-- Supports SQLite and MariaDB/MySQL

-- Users table (authentication & authorization)
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    acl INTEGER NOT NULL DEFAULT 2,          -- 0=SuperAdmin, 1=Admin, 2=User, 3=Suspended, 9=Anonymous
    login TEXT UNIQUE NOT NULL,              -- Email address
    fname TEXT DEFAULT '',
    lname TEXT DEFAULT '',
    altemail TEXT DEFAULT '',
    webpw TEXT DEFAULT '',                   -- Password hash
    otp TEXT DEFAULT '',                     -- One-time password token
    otpttl INTEGER DEFAULT 0,                -- OTP time-to-live
    cookie TEXT DEFAULT '',                  -- Remember me cookie
    anote TEXT DEFAULT '',                   -- Admin notes
    updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    created DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Categories for posts/docs
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    slug TEXT NOT NULL UNIQUE,
    description TEXT DEFAULT '',
    created DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Posts table (pages, blog posts, docs)
CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    author_id INTEGER NOT NULL,
    type TEXT NOT NULL DEFAULT 'post',       -- 'page', 'post', 'doc'
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    icon TEXT DEFAULT '',                    -- Lucide icon name
    excerpt TEXT DEFAULT '',
    content TEXT DEFAULT '',
    featured_image TEXT DEFAULT '',
    status TEXT DEFAULT 'published',         -- 'draft', 'published', 'archived'
    updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Post-Category junction table
CREATE TABLE IF NOT EXISTS post_categories (
    post_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    PRIMARY KEY (post_id, category_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Virtual Hosts
CREATE TABLE IF NOT EXISTS vhosts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,                         -- Owner (for multi-tenant)
    domain TEXT NOT NULL UNIQUE,
    aliases TEXT DEFAULT '',                 -- Space-separated alias domains
    docroot TEXT DEFAULT '',
    php_version TEXT DEFAULT '8.5',
    ssl_enabled INTEGER DEFAULT 0,
    active INTEGER DEFAULT 1,
    updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Virtual Mail accounts
CREATE TABLE IF NOT EXISTS vmails (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,                         -- Owner
    email TEXT NOT NULL UNIQUE,              -- Full email address
    password TEXT NOT NULL,                  -- Dovecot-compatible hash
    quota INTEGER DEFAULT 0,                 -- MB, 0 = unlimited
    active INTEGER DEFAULT 1,
    updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Mail aliases
CREATE TABLE IF NOT EXISTS valias (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    source TEXT NOT NULL,                    -- Alias address
    target TEXT NOT NULL,                    -- Destination(s)
    active INTEGER DEFAULT 1,
    updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(source, target)
);

-- DNS records
CREATE TABLE IF NOT EXISTS vdns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vhost_id INTEGER,                        -- Related vhost
    name TEXT NOT NULL,                      -- Record name
    type TEXT NOT NULL DEFAULT 'A',          -- A, AAAA, CNAME, MX, TXT, etc.
    content TEXT NOT NULL,                   -- Record value
    ttl INTEGER DEFAULT 3600,
    priority INTEGER DEFAULT 0,              -- For MX records
    active INTEGER DEFAULT 1,
    updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vhost_id) REFERENCES vhosts(id) ON DELETE CASCADE
);

-- SSL certificates
CREATE TABLE IF NOT EXISTS ssl_certs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vhost_id INTEGER NOT NULL,
    provider TEXT DEFAULT 'letsencrypt',     -- 'letsencrypt', 'manual', 'selfsigned'
    expires DATETIME,
    auto_renew INTEGER DEFAULT 1,
    updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vhost_id) REFERENCES vhosts(id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_posts_type ON posts(type);
CREATE INDEX IF NOT EXISTS idx_posts_slug ON posts(slug);
CREATE INDEX IF NOT EXISTS idx_posts_author ON posts(author_id);
CREATE INDEX IF NOT EXISTS idx_vhosts_active ON vhosts(active);
CREATE INDEX IF NOT EXISTS idx_vmails_active ON vmails(active);
CREATE INDEX IF NOT EXISTS idx_users_acl ON users(acl);

-- Default super admin (password: Admin@123456)
INSERT OR IGNORE INTO users (id, acl, login, fname, webpw, created, updated)
VALUES (1, 0, 'admin@localhost', 'Admin', '$2y$10$ZletPP5fW0qHIhWCvbQGpOx4VXBH4NC0Y5HI42dkkV/jMBvY4wbxO', datetime('now'), datetime('now'));

-- Default categories
INSERT OR IGNORE INTO categories (id, name, slug) VALUES
(1, 'General', 'general'),
(2, 'Documentation', 'documentation'),
(3, 'Tutorials', 'tutorials');

-- Default pages
INSERT OR IGNORE INTO posts (id, author_id, type, title, slug, icon, content, created, updated) VALUES
(1, 1, 'page', 'Home', 'home', 'home', '# Welcome to HCP

This is your **Hosting Control Panel** homepage. Edit this page to customize your landing content.

## Features

- Manage virtual hosts
- Configure email accounts
- SSL certificate management
- DNS zone editing
- User administration', datetime('now'), datetime('now')),

(2, 1, 'page', 'About', 'about', 'book-open', '# About HCP

**HCP** (Hosting Control Panel) is a lightweight, self-hosted control panel built with PHP 8.5.

## Technology Stack

- PHP 8.5 with pipe operator
- SQLite or MariaDB
- HTMX for dynamic updates
- Lucide icons', datetime('now'), datetime('now')),

(3, 1, 'page', 'Contact', 'contact', 'mail', '# Contact

For support, please email: **support@example.com**', datetime('now'), datetime('now'));

-- Sample blog post
INSERT OR IGNORE INTO posts (id, author_id, type, title, slug, icon, excerpt, content, created, updated) VALUES
(4, 1, 'post', 'Getting Started with HCP', 'getting-started', 'rocket', 'Learn how to set up and configure your Hosting Control Panel.', '# Getting Started with HCP

Welcome to HCP! This guide will help you get started.

## First Steps

1. Log in with your admin credentials
2. Configure your first virtual host
3. Set up email accounts
4. Enable SSL certificates

## Need Help?

Check out the **Docs** section for detailed documentation.', datetime('now'), datetime('now'));
