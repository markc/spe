<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250210
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Auth\Plugins\News;

use SPE\Auth\Core\{Plugin, Util, Db, QueryType, Ctx};

final class Model extends Plugin
{
    /**
     * Expected URI/Form Variables:
     * - id: Record ID for read/update/delete operations
     * - title: News item title (create/update)
     * - content: News item content (create/update)
     * - page: Page number for list pagination
     * - perpage: Items per page for list pagination
     */
    private const DEFAULT_PER_PAGE = 6;

    private ?Db $dbh = null;
    private array $in = [
        'id' => 1,
        'title' => null,
        'content' => null,
        'author' => null,
        'created' => null,
        'updated' => null
    ];

    public function __construct(Ctx $ctx)
    {
        parent::__construct($ctx);

        foreach (array_keys($this->in) as $key)
        {
            $this->in[$key] = isset($_REQUEST[$key])
                ? htmlspecialchars($_REQUEST[$key], ENT_QUOTES | ENT_HTML5, 'UTF-8')
                : null;
        }

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
            if (empty($this->in['title']) || empty($this->in['content']))
            {
                throw new \InvalidArgumentException("Title and content are required");
            }

            // Input is already encoded by constructor, safe to store in database
            $lid = $this->dbh->create('news', [
                'title' => $this->in['title'],
                'content' => $this->in['content'],
                'updated' => date('Y-m-d H:i:s'),
                'created' => date('Y-m-d H:i:s'),
                'author' => 'admin'
            ]);

            Util::elog(__METHOD__ . ' lid=' . var_export($lid, true));

            // After create, redirect to read view of the new article
            Util::ses('m', 'read');
            Util::ses('id', $lid);

            // Perform redirect to clear request parameters
            $baseUrl = dirname($_SERVER['PHP_SELF']);
            header("Location: {$baseUrl}?o=News&m=read&id={$lid}");
            exit();
        }
    }

    public function read(): void
    {
        Util::elog(__METHOD__);

        if (empty($this->in['id']))
        {
            throw new \InvalidArgumentException("Missing required parameter: id (record ID)");
        }

        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        Util::elog(__METHOD__ . ' id=' . $id);
        if ($id === false)
        {
            throw new \InvalidArgumentException("Invalid record ID format");
        }

        $result = $this->dbh->read('news', '*', 'id = :id', ['id' => $id], QueryType::One);

        if (!$result)
        {
            throw new \RuntimeException("Record not found: $id");
        }

        $this->ctx->ary = $result;

        // Set session variables for proper view handling
        Util::ses('m', 'read');
        Util::ses('id', $id);
    }

    public function update(): void
    {
        Util::elog(__METHOD__);

        if (empty($this->in['id']))
        {
            throw new \InvalidArgumentException("Missing required parameter: id (record ID)");
        }

        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
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
            if (empty($this->in['title']) || empty($this->in['content']))
            {
                throw new \InvalidArgumentException("Title and content are required");
            }

            // Input is already encoded by constructor, safe to store in database
            $data = [
                'title' => $this->in['title'],
                'content' => $this->in['content'],
                'updated' => date('Y-m-d H:i:s')
            ];

            $this->dbh->update('news', $data, 'id = :id', ['id' => $id]);
            // After update, redirect to read view of the updated article
            Util::ses('m', 'read');
            Util::ses('id', $id);

            // Perform redirect to clear request parameters
            $baseUrl = dirname($_SERVER['PHP_SELF']);
            header("Location: {$baseUrl}?o=News&m=read&id={$id}");
            exit();
        }
    }

    public function delete(): void
    {
        Util::elog(__METHOD__);

        if (empty($this->in['id']))
        {
            throw new \InvalidArgumentException("Missing required parameter: id (record ID)");
        }

        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        if ($id === false)
        {
            throw new \InvalidArgumentException("Invalid record ID format");
        }

        $this->dbh->delete('news', 'id = :id', ['id' => $id]);
        //Util::ses('p', '', '1');
    }

    public function list(): void
    {
        Util::elog(__METHOD__);

        // Get pagination parameters
        $page = filter_var($this->in['p'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
        $perPage = filter_var($this->in['perpage'] ?? self::DEFAULT_PER_PAGE, FILTER_VALIDATE_INT) ?: self::DEFAULT_PER_PAGE;

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
