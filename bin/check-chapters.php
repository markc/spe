<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
// Verifies chapters.json against the tree, the READMEs and the chapter tables. Exit 1 on any mismatch.

$root = dirname(__DIR__);
$manifest = json_decode(file_get_contents("$root/chapters.json"), true, flags: JSON_THROW_ON_ERROR);
$errors = [];
$fail = static function (string $msg) use (&$errors): void { $errors[] = $msg; };

$index = file_get_contents("$root/index.php");
$readme = file_get_contents("$root/docs/README.md");

foreach ($manifest['chapters'] as $c) {
    ['id' => $id, 'name' => $name, 'dir' => $dir] = $c;
    $dir === "$id-$name" || $fail("$id: dir '$dir' should be '$id-$name'");
    is_dir("$root/$dir") || $fail("$id: directory $dir missing");
    is_file("$root/{$c['entry']}") || $fail("$id: entry {$c['entry']} missing");
    is_file("$root/docs/$dir/README.md") || $fail("$id: docs/$dir/README.md missing");
    is_link("$root/$dir/README.md") && readlink("$root/$dir/README.md") === "../docs/$dir/README.md"
        || $fail("$id: $dir/README.md must be a symlink to ../docs/$dir/README.md");
    foreach ($c['adds'] as $f) {
        is_file("$root/$dir/$f") || $fail("$id: manifest says it adds $f but $dir/$f does not exist");
    }
    str_contains($index, "['$id', '$name',") || $fail("$id: root index.php chapter table has no ['$id', '$name', ...] row");
    preg_match('/^\| ' . $id . ' \| \[' . $name . '\]\(' . preg_quote($dir, '/') . '\/README\.md\)/m', $readme)
        || $fail("$id: docs/README.md chapter table has no row '| $id | [$name]($dir/README.md) ...'");
    $heading = strtok((string) file_get_contents("$root/docs/$dir/README.md"), "\n");
    $heading === "# SPE::$id $name" || $fail("$id: docs/$dir/README.md first line is '$heading', expected '# SPE::$id $name'");
}

$listed = array_column($manifest['chapters'], 'dir');
foreach (glob("$root/[0-9][0-9]-*", GLOB_ONLYDIR) as $d) {
    $d = basename($d);
    $d === '00-Tutorial' || in_array($d, $listed, true) || $fail("directory $d is not in chapters.json");
}

if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}
echo count($manifest['chapters']) . " chapters OK\n";
