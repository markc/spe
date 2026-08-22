-- Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
-- Passwords are the bcrypt hashes of "admin" and "user" — demo credentials only.

CREATE TABLE users (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    email    TEXT NOT NULL UNIQUE,
    name     TEXT NOT NULL,
    password TEXT NOT NULL,
    role     TEXT NOT NULL DEFAULT 'User',
    created  TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE user_tokens (
    selector       TEXT PRIMARY KEY,
    validator_hash TEXT NOT NULL,
    user_id        INTEGER NOT NULL,
    expires        TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE posts (
    id      INTEGER PRIMARY KEY AUTOINCREMENT,
    type    TEXT NOT NULL DEFAULT 'post',
    title   TEXT NOT NULL,
    slug    TEXT NOT NULL UNIQUE,
    body    TEXT NOT NULL DEFAULT '',
    created TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE tags (
    id   INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE
);

CREATE TABLE post_tags (
    post_id INTEGER NOT NULL,
    tag_id  INTEGER NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id)  REFERENCES tags(id)  ON DELETE CASCADE
);

INSERT INTO users (email, name, password, role) VALUES
    ('admin@example.com', 'Admin', '$2y$12$/oqiNQleGdNGh9/gyo4CI.2jxVqB1VizlepogV7eThbffzKRmBtV6', 'Admin'),
    ('user@example.com',  'User',  '$2y$12$6sE80QPHbL8U4IxU3CslC.Yqdmaocq/Wa9fqt/o4YjVvytnmC4.Rm', 'User');

INSERT INTO posts (type, title, slug, body) VALUES
    ('post', 'Hello, Markdown', 'hello-markdown', '# Hello

This blog stores **Markdown** and renders it safely. Try *emphasis*, `inline code`, and a [link](https://www.php.net).

- one
- two
- three'),
    ('post', 'Tags and pagination', 'tags-and-pagination', 'Posts carry **tags** through a junction table, and the list **paginates** five at a time. Click a tag to filter.'),
    ('post', 'Property hooks', 'property-hooks', 'The `excerpt` you see on the list and the `html` you read here are *property hooks* on the Post object — computed when read, never stored.'),
    ('post', 'One table, two types', 'one-table-two-types', 'Blog and Docs are the same code with a different `Type`. This is a post; the Docs section is the same engine showing `type = doc`.'),
    ('post', 'No framework needed', 'no-framework-needed', 'Everything here is plain PHP 8.5: a front controller, plugins, PDO, sessions and an enum-based ACL. No Laravel, no Composer packages beyond the dev tools.'),
    ('post', 'Sixth post', 'sixth-post', 'A sixth post so the blog list spills onto a second page.'),
    ('doc', 'Reading the docs', 'reading-the-docs', '# Reading the docs

These pages are `type = doc` served by the very engine they describe. Run `php bin/seed-docs.php` to import the chapter READMEs from `docs/` as docs.');

INSERT INTO tags (name, slug) VALUES ('PHP 8.5', 'php-8-5'), ('Markdown', 'markdown'), ('Architecture', 'architecture');

INSERT INTO post_tags (post_id, tag_id) VALUES (1, 2), (3, 1), (4, 3), (5, 3), (5, 1);
