<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250209
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\PDO\Plugins\News;

use SPE\PDO\Core\Plugin;
use SPE\PDO\Core\Util;
use SPE\PDO\Core\Db;
use SPE\PDO\Core\QueryType;
use SPE\PDO\Core\Cfg;
use SPE\PDO\Core\Ctx;

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
    private const OPTIONAL_FIELDS = ['id', 'created', 'updated'];
    private const DEFAULT_PER_PAGE = 10;

    private ?Db $dbh = null;

    public function __construct(Cfg $cfg, Ctx $ctx)
    {
        parent::__construct($cfg, $ctx);
        Util::elog(__METHOD__ . ' ' . var_export($this, true));

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

            $lid = $this->dbh->create('news', $data);
            Util::elog(__METHOD__ . ' ' . var_export($lid, true));
            Util::ses('p', '', '1');
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

            $this->dbh->update('news', $data, 'id = :id', ['id' => $id]);
            Util::ses('p', '', '1');
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
        $page = filter_var($this->ctx->in['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
        $perPage = filter_var($this->ctx->in['perpage'] ?? self::DEFAULT_PER_PAGE, FILTER_VALIDATE_INT) ?: self::DEFAULT_PER_PAGE;

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        // Get total count for pagination
        $total = $this->dbh->read('news', 'COUNT(*)', '1=1', [], QueryType::Column);

        // Get paginated results
        $this->ctx->ary = [
            'items' => $this->dbh->read(
                'news',
                '*',
                '1=1 ORDER BY updated DESC, created DESC LIMIT :limit OFFSET :offset',
                ['limit' => $perPage, 'offset' => $offset],
                QueryType::All
            ),
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'pages' => ceil($total / $perPage)
            ]
        ];
    }
}
