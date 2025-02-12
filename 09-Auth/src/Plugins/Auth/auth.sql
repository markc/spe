CREATE TABLE IF NOT EXISTS accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    grp INTEGER DEFAULT 1,
    acl INTEGER DEFAULT 1,
    login TEXT UNIQUE NOT NULL,
    fname TEXT DEFAULT '',
    lname TEXT DEFAULT '',
    webpw TEXT NOT NULL,
    cookie TEXT DEFAULT '',
    otp TEXT DEFAULT '',
    otpttl INTEGER DEFAULT 0,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create default admin account
INSERT OR IGNORE INTO accounts (id, grp, acl, login, fname, lname, webpw) 
VALUES (1, 0, 0, 'admin@example.com', 'Admin', 'User', '$2y$10$YourHashedPasswordHere');
