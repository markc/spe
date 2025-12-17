<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\App;

use PDO;
use PDOStatement;

enum QueryType: string { case All = 'all'; case One = 'one'; case Col = 'col'; }

final class Db extends PDO
{
    private const array OPTS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    public function __construct(string $name = 'blog')
    {
        $type = Env::get('DB_TYPE', 'sqlite');

        // Auto-create SQLite database if missing
        if ($type === 'sqlite' && !Schema::exists($name)) {
            $this->ensureDir($name);
            Schema::init($name);
        }

        $dsn = match ($type) {
            'sqlite' => 'sqlite:' . Schema::path($name),
            default => sprintf('mysql:host=%s;port=%s;dbname=%s',
                Env::get('DB_HOST', 'localhost'),
                Env::get('DB_PORT', '3306'),
                Env::get("DB_{$name}_NAME", $name)),
        };

        parent::__construct($dsn, Env::get('DB_USER'), Env::get('DB_PASS'), self::OPTS);
    }

    public function create(string $tbl, array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));
        $stmt = $this->prepare("INSERT INTO `$tbl` ($cols) VALUES ($vals)");
        $this->bind($stmt, $data);
        $stmt->execute();
        return (int)$this->lastInsertId();
    }

    public function read(string $tbl, string $cols = '*', string $where = '', array $params = [], QueryType $type = QueryType::All): mixed
    {
        $sql = "SELECT $cols FROM `$tbl`" . ($where ? " WHERE $where" : '');
        $stmt = $this->prepare($sql);
        if ($params) $this->bind($stmt, $params);
        $stmt->execute();

        return match ($type) {
            QueryType::All => $stmt->fetchAll(),
            QueryType::One => $stmt->fetch(),
            QueryType::Col => $stmt->fetchColumn(),
        };
    }

    public function update(string $tbl, array $data, string $where, array $params = []): bool
    {
        $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
        $stmt = $this->prepare("UPDATE `$tbl` SET $set WHERE $where");
        $this->bind($stmt, [...$data, ...$params]);
        return $stmt->execute();
    }

    public function delete(string $tbl, string $where, array $params = []): bool
    {
        $stmt = $this->prepare("DELETE FROM `$tbl` WHERE $where");
        $this->bind($stmt, $params);
        return $stmt->execute();
    }

    private function bind(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $k => $v) $stmt->bindValue(":$k", $v);
    }

    private function ensureDir(string $name): void
    {
        $dir = dirname(Schema::path($name));
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }
}
