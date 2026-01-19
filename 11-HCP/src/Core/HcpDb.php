<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Core;

use PDO;
use PDOStatement;
use SPE\App\Db; // Load this first to get QueryType
use SPE\App\QueryType;

/**
 * HCP Database wrapper for local hcp.db
 * Provides same interface as SPE\App\Db but uses local database path.
 */
final class HcpDb extends PDO
{
    private const array OPTS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    private static ?self $instance = null;
    private static bool $dbLoaded = false;

    public function __construct()
    {
        // Ensure SPE\App\Db is loaded so QueryType enum is available
        if (!self::$dbLoaded) {
            class_exists(Db::class);
            self::$dbLoaded = true;
        }
        $dbPath = $_ENV['HCP_DB'] ?? getenv('HCP_DB') ?: __DIR__ . '/../../hcp.db';
        parent::__construct('sqlite:' . $dbPath, null, null, self::OPTS);
    }

    /**
     * Get singleton instance for use by Acl/Util.
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    // === CRUD Methods (same as SPE\App\Db) ===

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
}
