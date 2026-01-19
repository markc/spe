-- SPE Blog Schema (MariaDB)
-- Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

CREATE TABLE IF NOT EXISTS posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE,
    content TEXT,
    excerpt TEXT,
    featured_image VARCHAR(500),
    icon VARCHAR(10),
    author VARCHAR(100),
    author_id INT,
    type VARCHAR(20) DEFAULT 'post',
    created DATETIME,
    updated DATETIME,
    INDEX idx_type (type),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_categories (
    post_id INT,
    category_id INT,
    PRIMARY KEY (post_id, category_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data
INSERT INTO categories (name, slug, description) VALUES
    ('Main', 'main', 'Main navigation pages'),
    ('General', 'general', 'General posts');

INSERT INTO posts (title, slug, content, icon, type, author, created, updated) VALUES
    ('Home', 'home', 'Welcome to SPE!', '🏠', 'page', 'admin', NOW(), NOW()),
    ('About', 'about', 'About this project.', '📋', 'page', 'admin', NOW(), NOW()),
    ('Contact', 'contact', 'Contact information.', '✉️', 'page', 'admin', NOW(), NOW());

INSERT INTO post_categories (post_id, category_id) VALUES (1, 1), (2, 1), (3, 1);
