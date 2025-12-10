<?php
// Router script for PHP's built-in server
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Handle root URL
if ($path === '/')
{
    require __DIR__ . '/index.php';
    exit;
}

$ext = pathinfo($path, PATHINFO_EXTENSION);

// Handle static files
if (is_file(__DIR__ . $path))
{
    // Set appropriate content type for different file extensions
    switch ($ext)
    {
        case 'js':
            header('Content-Type: application/javascript');
            readfile(__DIR__ . $path);
            exit;
        case 'css':
            header('Content-Type: text/css');
            readfile(__DIR__ . $path);
            exit;
        default:
            return false; // Let the server handle other files
    }
}

// Otherwise, route to index.php
require __DIR__ . '/index.php';
