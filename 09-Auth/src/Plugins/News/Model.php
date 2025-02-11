<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250210
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Auth\Plugins\News;

use SPE\Auth\Core\Plugin;
use SPE\Auth\Core\Util;
use SPE\Auth\Core\Db;
use SPE\Auth\Core\QueryType;
use SPE\Auth\Core\Cfg;
use SPE\Auth\Core\Ctx;

final class Model extends Plugin
{
    /**
     * Expected URI/Form Variables:
     * - i: Record ID for read/update/delete operations
     * - title: News item title (create/update)
     * - content: News item content (create/update)
     * - page: Page number for list pagination
     * - perpage: Items per page for list pagination
     */
    private const REQUIRED_FIELDS = ['title', 'content'];
    private const OPTIONAL_FIELDS = ['id', 'created', 'updated', 'author'];
    private const DEFAULT_PER_PAGE = 6;

    private ?Db $dbh = null;

    public function __construct(Cfg $cfg, Ctx $ctx)
    {
        parent::__construct($cfg, $ctx);

        Util::elog(__METHOD__);

        if (is_null($this->dbh))
        {
            $this->dbh = new Db([
                'type' => 'sqlite',
                'path' => __DIR__ . '/news.db',
                'name' => 'news'
            ]);
        }
    }

    public function create(): void
    {
        Util::elog(__METHOD__);

        if ($_POST)
        {
            // Validate required fields
            foreach (self::REQUIRED_FIELDS as $field)
            {
                if (empty($this->ctx->in[$field]))
                {
                    throw new \InvalidArgumentException("Missing required field: $field");
                }
            }

            // Only include valid fields
            $data = array_intersect_key(
                $this->ctx->in,
                array_flip(array_merge(self::REQUIRED_FIELDS, self::OPTIONAL_FIELDS))
            );

            $data['updated'] = date('Y-m-d H:i:s');
            $data['created'] = date('Y-m-d H:i:s');
            $data['author'] = 'admin'; // Set default author

            $lid = $this->dbh->create('news', $data);
            Util::elog(__METHOD__ . ' ' . var_export($lid, true));

            // After create, redirect to read view of the new article
            Util::ses('m', 'read');
            Util::ses('i', $lid);

            // Perform redirect to clear request parameters
            $baseUrl = dirname($_SERVER['PHP_SELF']);
            header("Location: {$baseUrl}?o=News&m=read&i={$lid}");
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

        $this->ctx->ary = $this->dbh->read('news', '*', 'id = :id', ['id' => $id], QueryType::One);

        if (!$this->ctx->ary)
        {
            throw new \RuntimeException("Record not found: $id");
        }

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
            $this->ctx->ary = $this->dbh->read('news', '*', 'id = :id', ['id' => $id], QueryType::One);
            if (!$this->ctx->ary)
            {
                throw new \RuntimeException("Record not found: $id");
            }
            return;
        }

        // Handle POST request for update
        {
            // Validate required fields
            foreach (self::REQUIRED_FIELDS as $field)
            {
                if (empty($this->ctx->in[$field]))
                {
                    throw new \InvalidArgumentException("Missing required field: $field");
                }
            }

            // Only include valid fields
            $data = array_intersect_key(
                $this->ctx->in,
                array_flip(array_merge(self::REQUIRED_FIELDS, self::OPTIONAL_FIELDS))
            );

            $data['updated'] = date('Y-m-d H:i:s');

            $this->dbh->update('news', $data, 'id = :id', ['id' => $id]);
            // After update, redirect to read view of the updated article
            Util::ses('m', 'read');
            Util::ses('i', $id);

            // Perform redirect to clear request parameters
            $baseUrl = dirname($_SERVER['PHP_SELF']);
            header("Location: {$baseUrl}?o=News&m=read&i={$id}");
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

        $this->dbh->delete('news', 'id = :id', ['id' => $id]);
        Util::ses('p', '', '1');
    }

    public function list(): void
    {
        Util::elog(__METHOD__);

        // Get pagination parameters
        $page = filter_var($this->ctx->in['p'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
        $perPage = filter_var($this->ctx->in['perpage'] ?? self::DEFAULT_PER_PAGE, FILTER_VALIDATE_INT) ?: self::DEFAULT_PER_PAGE;

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        // Handle search query from $_GET since it's not in ctx->in
        $searchQuery = trim($_GET['q'] ?? '');
        $where = '1=1';
        $params = [];

        if ($searchQuery !== '')
        {
            $where = '(title LIKE :search OR content LIKE :search)';
            $params['search'] = '%' . $searchQuery . '%';
        }

        // Get total count for pagination
        $total = $this->dbh->read('news', 'COUNT(*)', $where, $params, QueryType::Column);

        // Add pagination parameters
        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        // Get paginated results
        $this->ctx->ary = [
            'items' => $this->dbh->read(
                'news',
                '*',
                $where . ' ORDER BY updated DESC, created DESC LIMIT :limit OFFSET :offset',
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
