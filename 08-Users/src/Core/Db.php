<?php

namespace SPE\Users\Core;

use \PDO;
use \PDOStatement;

enum QueryType: string
{
    case All = 'all';
    case One = 'one';
    case Column = 'column';
}

class Db extends PDO
{
    public function __construct(array $config)
    {
        Util::elog(__METHOD__);

        $dsn = $config['type'] === 'mysql'
            ? "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']}"
            : "sqlite:{$config['path']}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        parent::__construct($dsn, $config['user'] ?? '', $config['pass'] ?? '', $options);
    }

    public function create(string $table, array $data): int
    {
        Util::elog(__METHOD__);

        $fields = implode(', ', array_keys($data));
        $values = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));

        $sql = "INSERT INTO `$table` ($fields) VALUES ($values)";

        $stmt = $this->prepare($sql);
        $this->bindValues($stmt, $data);

        $stmt->execute();
        return $this->lastInsertId();
    }

    public function read(
        string $table,
        string $field = '*',
        string $where = '',
        array $params = [],
        QueryType $type = QueryType::All
    )
    {
        Util::elog(__METHOD__);

        $sql = "SELECT $field FROM `$table`";
        if ($where) $sql .= " WHERE $where";

        $stmt = $this->prepare($sql);
        if ($params) $this->bindValues($stmt, $params);

        $stmt->execute();

        return match ($type)
        {
            QueryType::All => $stmt->fetchAll(),
            QueryType::One => $stmt->fetch(),
            QueryType::Column => $stmt->fetchColumn(),
        };
    }

    public function update(string $table, array $data, string $where, array $params = []): bool
    {
        Util::elog(__METHOD__);

        $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
        $sql = "UPDATE `$table` SET $set WHERE $where";

        $stmt = $this->prepare($sql);
        $this->bindValues($stmt, array_merge($data, $params));

        return $stmt->execute();
    }

    public function delete(string $table, string $where, array $params = []): bool
    {
        Util::elog(__METHOD__);

        $sql = "DELETE FROM `$table` WHERE $where";
        $stmt = $this->prepare($sql);
        $this->bindValues($stmt, $params);

        return $stmt->execute();
    }

    private function bindValues(PDOStatement $stmt, array $params): void
    {
        Util::elog(__METHOD__);

        foreach ($params as $key => $value)
        {
            $stmt->bindValue(":$key", $value);
        }
    }
}

/* Usage examples

$config = [
    'type' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'mydatabase',
    'user' => 'root',
    'pass' => 'password',
];

$db = new Db($config);

// Create
$db->create('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Read
$user = $db->read('users', 'name, email', 'id = :id', ['id' => 1], QueryType::One);

// Update
$db->update('users', ['name' => 'Jane Doe'], 'id = :id', ['id' => 1]);

// Delete
$db->delete('users', 'id = :id', ['id' => 1]);

*/
