<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250219
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\PDO\Plugins\News;

use SPE\PDO\Core\{Db, Ctx, Plugin, QueryType, Util};

final class NewsModel extends Plugin
{
    /**
     * Expected URI/Form Variables:
     * - i: Record ID for read/update/delete operations
     * - title: News item title (create/update)
     * - content: News item content (create/update)
     * - page: Page number for list pagination
     * - perpage: Items per page for list pagination
     */
    //private const REQUIRED_FIELDS = ['title', 'content'];
    //private const OPTIONAL_FIELDS = ['id', 'created', 'updated', 'author'];
    private const DEFAULT_PER_PAGE = 6;

    private ?Db $dbh = null;
    private $in = [
        'id' => 1,
        'title' => '',
        'content' => '',
        'author' => '',
        'created' => null,
        'updated' => null,
        'page' => 0,
        'perpage' => 6
    ];

    public function __construct(protected Ctx $ctx)
    {
        parent::__construct($ctx);

        Util::elog(__METHOD__);

        foreach ($this->in as $k => &$v) $v = Util::ses($k, $v);

        //Util::elog(__METHOD__ . ' this->in=' . var_export($this->in, true));

        if (is_null($this->dbh))
        {
            $this->dbh = new Db([
                'type' => 'sqlite',
                'path' => __DIR__ . '/news.db',
                'name' => 'news'
            ]);
        }
    }

    public function create(): array
    {
        Util::elog(__METHOD__);

        if ($_POST)
        {
            $in = [
                'title' => '',
                'content' => '',
            ];

            foreach ($in as $k => $v)
            {
                $data[$k] = $_POST[$k] ?? $v;
                if (isset($_POST[$k]))
                {
                    $data[$k] = htmlentities(trim($_POST[$k]));
                }
            }

            $data['updated'] = date('Y-m-d H:i:s');
            $data['created'] = date('Y-m-d H:i:s');
            $data['author'] = 'admin'; // Set default author

            $lid = $this->dbh->create('news', $data);
            Util::elog(__METHOD__ . ' ' . var_export($lid, true));

            // After create, redirect to read view of the new article
            Util::ses('m', 'read');
            Util::ses('id', $lid);

            // Perform redirect to clear request parameters
            $baseUrl = dirname($_SERVER['PHP_SELF']);
            header("Location: {$baseUrl}?o=News&m=read&id={$lid}");
            exit();
        }
        return [];
    }

    public function read(): array
    {
        Util::elog(__METHOD__);

        //Util::elog(__METHOD__ . ' this->in=' . var_export($this->in, true));

        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);

        $result = $this->dbh->read('news', '*', 'id = :id', ['id' => $id], QueryType::One);

        // Set session variables for proper view handling
        //Util::ses('m', 'read');
        //Util::ses('id', $id);
        //Util::elog(__METHOD__ . ' result=' . var_export($result, true));

        return $result;
    }

    public function update(): array
    {
        Util::elog(__METHOD__);

        if ($_POST)
        {
            $in = [
                'id' => 0,
                'title' => '',
                'content' => '',
            ];

            foreach ($in as $k => $v)
            {
                $data[$k] = $_POST[$k] ?? $v;
                if (isset($_POST[$k]))
                {
                    $data[$k] = htmlentities(trim($_POST[$k]));
                }
            }

            $data['updated'] = date('Y-m-d H:i:s');

            Util::elog(__METHOD__ . ' data=' . var_export($data, true));

            $this->dbh->update('news', $data, 'id = :id', ['id' => $data['id']]);
            // After update, redirect to read view of the updated article
            Util::ses('m', 'read');
            Util::ses('id', $data['id']);

            // Perform redirect to clear request parameters
            $baseUrl = dirname($_SERVER['PHP_SELF']);
            Util::elog(__METHOD__ . ' redirect=' . "Location: {$baseUrl}?o=News&m=read&id={$data['id']}");
            header("Location: {$baseUrl}?o=News&m=read&id={$data['id']}");
            exit();
        }
        return [];
    }

    public function delete(): array
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
        Util::ses('page', '', '1');
        return [];
    }

    public function list(): array
    {
        Util::elog(__METHOD__);

        // Get pagination parameters
        $page = filter_var($this->in['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
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
        return [
            'items' => $this->dbh->read(
                'news',
                '*',
                $where . ' ORDER BY updated DESC, created DESC LIMIT :limit OFFSET :offset',
                $params,
                QueryType::All
            ),
            'pagination' => [
                'page'      => $page,
                'perPage'   => $perPage,
                'total'     => $total,
                'pages'     => ceil($total / $perPage)
            ]
        ];
    }
}
