<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Core;

use PDO;
use PDOStatement;

final class Db extends PDO
{
    private const array OPTIONS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    public function __construct(string $path, string $schema)
    {
        $fresh = !is_file($path);
        if ($fresh && !is_dir(dirname($path))) {
            mkdir(dirname($path), 0o755, true);
        }
        parent::__construct("sqlite:$path", options: self::OPTIONS);
        $this->exec('PRAGMA foreign_keys = ON');
        if ($fresh) {
            $this->exec(file_get_contents($schema));
        }
    }

    /** @return list<array<string,mixed>>|array<string,mixed>|string|false */
    public function read(string $table, string $cols, string $where = '', array $params = [], QueryType $type = QueryType::All, string $order = ''): mixed
    {
        $sql = "SELECT $cols FROM $table" . ($where ? " WHERE $where" : '') . ($order ? " $order" : '');
        $stmt = $this->run($sql, $params);
        return match ($type) {
            QueryType::All => $stmt->fetchAll(),
            QueryType::One => $stmt->fetch(),
            QueryType::Col => $stmt->fetchColumn(),
        };
    }

    #[\NoDiscard('the new row id tells you the insert succeeded')]
    public function create(string $table, array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $binds = implode(', ', array_map(static fn(string $k) => ":$k", array_keys($data)));
        $this->run("INSERT INTO $table ($cols) VALUES ($binds)", $data);
        return (int) $this->lastInsertId();
    }

    #[\NoDiscard('check whether the update matched a row')]
    public function update(string $table, array $data, string $where, array $params = []): bool
    {
        $set = implode(', ', array_map(static fn(string $k) => "$k = :$k", array_keys($data)));
        return $this->run("UPDATE $table SET $set WHERE $where", [...$data, ...$params])->rowCount() > 0;
    }

    #[\NoDiscard('check whether the delete matched a row')]
    public function delete(string $table, string $where, array $params = []): bool
    {
        return $this->run("DELETE FROM $table WHERE $where", $params)->rowCount() > 0;
    }

    private function run(string $sql, array $params): PDOStatement
    {
        $stmt = $this->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value, match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            });
        }
        $stmt->execute();
        return $stmt;
    }
}
