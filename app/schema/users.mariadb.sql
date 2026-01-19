-- SPE Users Schema (MariaDB)
-- Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grp INT NOT NULL DEFAULT 0,
    acl INT NOT NULL DEFAULT 2,
    login VARCHAR(255) UNIQUE NOT NULL,
    fname VARCHAR(100) DEFAULT '',
    lname VARCHAR(100) DEFAULT '',
    altemail VARCHAR(255) DEFAULT '',
    webpw VARCHAR(255) DEFAULT '',
    otp VARCHAR(100) DEFAULT '',
    otpttl INT DEFAULT 0,
    cookie VARCHAR(100) DEFAULT '',
    anote TEXT,
    updated DATETIME,
    created DATETIME,
    INDEX idx_login (login),
    INDEX idx_acl (acl)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data (passwords are empty, set on first login)
INSERT INTO users (grp, acl, login, fname, lname, created, updated) VALUES
    (1, 0, 'admin@example.org', 'Admin', 'User', NOW(), NOW()),
    (1, 2, 'user@example.org', 'Normal', 'User', NOW(), NOW());
