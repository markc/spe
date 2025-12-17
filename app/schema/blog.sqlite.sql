-- SPE Blog Schema (SQLite)
-- Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

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

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE,
    description TEXT
);

CREATE TABLE IF NOT EXISTS post_categories (
    post_id INTEGER,
    category_id INTEGER,
    PRIMARY KEY (post_id, category_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Seed data
INSERT INTO categories (name, slug, description) VALUES
    ('Main', 'main', 'Main navigation pages'),
    ('General', 'general', 'General posts');

INSERT INTO posts (title, slug, content, icon, type, author, created, updated) VALUES
    ('Home', 'home', 'Welcome to SPE!', '🏠', 'page', 'admin', datetime('now'), datetime('now')),
    ('About', 'about', 'About this project.', '📋', 'page', 'admin', datetime('now'), datetime('now')),
    ('Contact', 'contact', 'Contact information.', '✉️', 'page', 'admin', datetime('now'), datetime('now'));

INSERT INTO post_categories (post_id, category_id) VALUES (1, 1), (2, 1), (3, 1);
