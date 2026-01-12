<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\App;

use PDO;
use PDOStatement;

enum QueryType: string
{
    case All = 'all';
    case One = 'one';
    case Col = 'col';
}

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

        // Password from file or direct value
        $pass = Env::get('DB_PASS', '');
        $pass = file_exists($pass) ? trim(file_get_contents($pass)) : $pass;

        $dsn = match ($type) {
            'sqlite' => 'sqlite:' . Schema::path($name),
            'mariadb' => 'mysql:'
                . (
                    ($sock = Env::get('DB_SOCK'))
                        ? "unix_socket=$sock"
                        : 'host='
                        . Env::get('DB_HOST', 'localhost')
                        . ';port='
                        . Env::get('DB_PORT', '3306')
                )
                . ';dbname='
                . Env::get("DB_{$name}_NAME", $name),
            default => throw new \RuntimeException("Unsupported DB type: $type"),
        };

        parent::__construct($dsn, Env::get('DB_USER'), $pass, self::OPTS);
    }

    // === CRUD Methods ===

    public function create(string $tbl, array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $vals = implode(', ', array_map(static fn($k) => ":$k", array_keys($data)));
        $stmt = $this->prepare("INSERT INTO `$tbl` ($cols) VALUES ($vals)");
        $this->bind($stmt, $data);
        $stmt->execute();
        return (int) $this->lastInsertId();
    }

    public function read(
        string $tbl,
        string $cols = '*',
        string $where = '',
        array $params = [],
        QueryType $type = QueryType::All,
    ): mixed {
        $sql = "SELECT $cols FROM `$tbl`" . ($where ? " WHERE $where" : '');
        $stmt = $this->prepare($sql);
        if ($params)
            $this->bind($stmt, $params);
        $stmt->execute();

        return match ($type) {
            QueryType::All => $stmt->fetchAll(),
            QueryType::One => $stmt->fetch(),
            QueryType::Col => $stmt->fetchColumn(),
        };
    }

    public function update(string $tbl, array $data, string $where, array $params = []): bool
    {
        $set = implode(', ', array_map(static fn($k) => "$k = :$k", array_keys($data)));
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

    // === Generic Query (for complex/raw SQL) ===

    public function qry(string $sql, array $params = [], QueryType $type = QueryType::All): mixed
    {
        $stmt = $this->prepare($sql);
        if ($params)
            $this->bind($stmt, $params);
        $stmt->execute();

        return match ($type) {
            QueryType::All => $stmt->fetchAll(),
            QueryType::One => $stmt->fetch(),
            QueryType::Col => $stmt->fetchColumn(),
        };
    }

    // === Type-aware Parameter Binding ===

    private function bind(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $k => $v) {
            $type = match (true) {
                is_int($v) => PDO::PARAM_INT,
                is_bool($v) => PDO::PARAM_BOOL,
                is_null($v) => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $stmt->bindValue(":$k", $v, $type);
        }
    }

    private function ensureDir(string $name): void
    {
        $dir = dirname(Schema::path($name));
        if (!is_dir($dir))
            mkdir($dir, 0o755, true);
    }
}
