#!/usr/bin/env php
<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Imports the chapter READMEs from docs/ into the blog database as type=doc entries,
// so the finished app serves the very tutorial that describes it.

require_once __DIR__ . '/../../vendor/autoload.php';

use SPE\Blog\Core\{Db, QueryType};

$db = new Db(__DIR__ . '/../data/spe.db', __DIR__ . '/../schema.sql');
$docs = glob(__DIR__ . '/../../docs/0*-*/README.md') ?: [];
$count = 0;

foreach ($docs as $path) {
    $chapter = basename(dirname($path));           // e.g. 05-Autoload
    $slug = strtolower($chapter);
    $body = (string) file_get_contents($path);
    preg_match('/^#\s*(.+)$/m', $body, $m);
    $title = $m[1] ?? $chapter;

    $id = $db->read('posts', 'id', 'slug = :s', ['s' => $slug], QueryType::Col);
    if ($id === false) {
        (void) $db->create('posts', ['type' => 'doc', 'title' => $title, 'slug' => $slug, 'body' => $body]);
    } else {
        $db->update('posts', ['title' => $title, 'body' => $body], 'id = :id', ['id' => (int) $id]);
    }
    $count++;
    echo "imported $chapter\n";
}

echo "$count docs imported\n";
