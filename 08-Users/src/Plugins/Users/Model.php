<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250210
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Users\Plugins\Users;

use SPE\Users\Core\Plugin;
use SPE\Users\Core\Util;
use SPE\Users\Core\Db;
use SPE\Users\Core\QueryType;
use SPE\Users\Core\Cfg;
use SPE\Users\Core\Ctx;

final class Model extends Plugin
{
    /**
     * Expected URI/Form Variables:
     * - i: Record ID for read/update/delete operations
     * - title: User title (create/update)
     * - content: User item content (create/update)
     * - page: Page number for list pagination
     * - perpage: Items per page for list pagination
     */
    private const REQUIRED_FIELDS = ['title', 'content'];
    private const OPTIONAL_FIELDS = ['id', 'created', 'updated', 'author'];
    private const DEFAULT_PER_PAGE = 10; // Default number of records per page for server-side pagination

    private ?Db $dbh = null;

    public function __construct(Cfg $cfg, Ctx $ctx)
    {
        parent::__construct($cfg, $ctx);

        Util::elog(__METHOD__);

        if (is_null($this->dbh))
        {
            $this->dbh = new Db([
                'type' => 'sqlite',
                'path' => __DIR__ . '/users.db',
                'name' => 'users'
            ]);

            $this->createTable();
        }
    }

    private function createTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `users` (
            `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT,
            `grp` integer NOT NULL DEFAULT '0',
            `acl` integer NOT NULL DEFAULT '0',
            `login` varchar(64) NOT NULL,
            `fname` varchar(64) NOT NULL DEFAULT '',
            `lname` varchar(64) NOT NULL DEFAULT '',
            `altemail` varchar(64) NOT NULL DEFAULT '',
            `webpw` varchar(64) NOT NULL DEFAULT '',
            `otp` varchar(64) NOT NULL DEFAULT '',
            `otpttl` integer NOT NULL DEFAULT '0',
            `cookie` varchar(64) NOT NULL DEFAULT '',
            `anote` text NOT NULL,
            `updated` datetime NOT NULL,
            `created` datetime NOT NULL
        );";

        $this->dbh->exec($sql);
    }

    public function create(): void
    {
        Util::elog(__METHOD__);

        if ($_POST)
        {
            // Validate required fields
            $requiredFields = ['login'];
            foreach ($requiredFields as $field)
            {
                if (empty($this->ctx->in[$field]))
                {
                    throw new \InvalidArgumentException("Missing required field: $field");
                }
            }

            // Only include valid fields
            $data = array_intersect_key(
                $this->ctx->in,
                array_flip(['grp', 'acl', 'login', 'fname', 'lname', 'altemail', 'webpw', 'otp', 'otpttl', 'cookie', 'anote'])
            );

            $data['updated'] = date('Y-m-d H:i:s');
            $data['created'] = date('Y-m-d H:i:s');

            $lid = $this->dbh->create('users', $data);
            Util::elog(__METHOD__ . ' ' . var_export($lid, true));

            // After create, redirect to read view of the new article
            Util::ses('m', 'read');
            Util::ses('i', $lid);

            // Perform redirect to clear request parameters
            $baseUrl = dirname($_SERVER['PHP_SELF']);
            header("Location: {$baseUrl}?o=Users&m=read&i={$lid}");
            exit();
        }
    }

    public function read(): void
    {
        Util::elog(__METHOD__);

        if (empty($this->ctx->in['i']))
        {
            throw new \InvalidArgumentException("Missing required parameter: i (record ID)");
        }

        $id = filter_var($this->ctx->in['i'], FILTER_VALIDATE_INT);
        if ($id === false)
        {
            throw new \InvalidArgumentException("Invalid record ID format");
        }

        $this->ctx->ary = $this->dbh->read('users', '*', 'id = :id', ['id' => $id], QueryType::One);

        if (!$this->ctx->ary)
        {
            throw new \RuntimeException("Record not found: $id");
        }

        // Set session variables for proper view handling
        Util::ses('m', 'read');
        // Set session variables for proper view handling
        Util::ses('m', 'read');
        Util::ses('i', $id);
    }

    public function update(): void
    {
        Util::elog(__METHOD__);

        if (empty($this->ctx->in['i']))
        {
            throw new \InvalidArgumentException("Missing required parameter: i (record ID)");
        }

        $id = filter_var($this->ctx->in['i'], FILTER_VALIDATE_INT);
        if ($id === false)
        {
            throw new \InvalidArgumentException("Invalid record ID format");
        }

        // If not a POST request, fetch the current data for the form
        if (!$_POST)
        {
            $this->ctx->ary = $this->dbh->read('users', '*', 'id = :id', ['id' => $id], QueryType::One);
            if (!$this->ctx->ary)
            {
                throw new \RuntimeException("Record not found: $id");
            }
            return;
        }

        // Handle POST request for update
        {
            // Validate required fields
            $requiredFields = ['login'];
            foreach ($requiredFields as $field)
            {
                if (empty($this->ctx->in[$field]))
                {
                    throw new \InvalidArgumentException("Missing required field: $field");
                }
            }

            // Only include valid fields
            $data = array_intersect_key(
                $this->ctx->in,
                array_flip(['grp', 'acl', 'login', 'fname', 'lname', 'altemail', 'webpw', 'otp', 'otpttl', 'cookie', 'anote'])
            );

            $data['updated'] = date('Y-m-d H:i:s');

            $this->dbh->update('users', $data, 'id = :id', ['id' => $id]);
            // After update, redirect to read view of the updated article
            Util::ses('m', 'read');
            Util::ses('i', $id);

            // Perform redirect to clear request parameters
            $baseUrl = dirname($_SERVER['PHP_SELF']);
            header("Location: {$baseUrl}?o=Users&m=read&i={$id}");
            exit();
        }
    }

    public function delete(): void
    {
        Util::elog(__METHOD__);

        if (empty($this->ctx->in['i']))
        {
            throw new \InvalidArgumentException("Missing required parameter: i (record ID)");
        }

        $id = filter_var($this->ctx->in['i'], FILTER_VALIDATE_INT);
        if ($id === false)
        {
            throw new \InvalidArgumentException("Invalid record ID format");
        }

        $this->dbh->delete('users', 'id = :id', ['id' => $id]);
        Util::ses('p', '', '1');
    }

    public function list(): void
    {
        Util::elog(__METHOD__);

        // Get pagination parameters
        $page = filter_var($this->ctx->in['p'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
        // If perpage is explicitly set, use it, otherwise use a large number to show all
        $perPage = isset($this->ctx->in['perpage'])
            ? (filter_var($this->ctx->in['perpage'], FILTER_VALIDATE_INT) ?: self::DEFAULT_PER_PAGE)
            : self::DEFAULT_PER_PAGE;

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        // Handle search query from $_GET since it's not in ctx->in
        $searchQuery = trim($_GET['q'] ?? '');
        $where = '1=1';
        $params = [];

        if ($searchQuery !== '')
        {
            $where = '(login LIKE :search OR fname LIKE :search OR lname LIKE :search)';
            $params['search'] = '%' . $searchQuery . '%';
        }

        // Get total count for pagination
        $total = $this->dbh->read('users', 'COUNT(*)', $where, $params, QueryType::Column);

        // Add pagination parameters
        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        // Handle sorting
        $sortField = $_GET['sort'] ?? 'updated';
        $sortDir = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $validSortFields = ['id', 'login', 'fname', 'lname', 'created', 'updated'];
        $sortField = in_array($sortField, $validSortFields) ? $sortField : 'updated';

        // Get paginated results
        $this->ctx->ary = [
            'items' => $this->dbh->read(
                'users',
                '*',
                $where . ' ORDER BY ' . $sortField . ' ' . $sortDir . ' LIMIT :limit OFFSET :offset',
                $params,
                QueryType::All
            ),
            'pagination' => [
                'p'      => $page,
                'perPage'   => $perPage,
                'total'     => $total,
                'pages'     => ceil($total / $perPage)
            ]
        ];
    }
}
