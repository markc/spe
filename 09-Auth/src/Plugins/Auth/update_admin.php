<?php
$password = 'admin123'; // Default admin password
$hash = password_hash($password, PASSWORD_DEFAULT);

$db = new SQLite3('auth.db');
$stmt = $db->prepare('UPDATE accounts SET webpw = :hash WHERE login = :login');
$stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
$stmt->bindValue(':login', 'admin@example.com', SQLITE3_TEXT);
$stmt->execute();
$db->close();

echo "Admin password updated successfully\n";
