<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250219
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\PDO\Plugins\News;

use SPE\PDO\Core\{Db, Ctx, Plugin, QueryType, Util};

final class NewsModel extends Plugin
{
    private const DEFAULT_PER_PAGE = 6;

    private ?Db $dbh = null;

    private $in = [
        'id' => 0,
        'title' => '',
        'content' => '',
        'author' => '',
        'created' => null,
        'updated' => null,
    ];

    public function __construct(protected Ctx $ctx)
    {
        Util::elog(__METHOD__);

        //parent::__construct($ctx);

        foreach ($this->in as $k => &$v) $v = Util::ses($k, $v);

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
            // Remove id for new records since it's auto-increment
            $data = $this->in;
            unset($data['id']);

            $data['created'] = date('Y-m-d H:i:s');
            $data['updated'] = date('Y-m-d H:i:s');
            $data['author'] = 'admin';

            $lid = $this->dbh->create('news', $data);
            Util::ses('id', $lid);
        }
        Util::ses('m', 'list');
        header('Access-Control-Expose-Headers: X-Redirect-Method');
        header('X-Redirect-Method: list');
        return []; // Return empty array to trigger client-side redirect
    }

    public function read(): array
    {
        Util::elog(__METHOD__);

        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        return $this->dbh->read('news', '*', 'id = :id', ['id' => $id], QueryType::One);
    }

    public function update(): array
    {
        Util::elog(__METHOD__);

        if ($_POST)
        {
            $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
            $this->in['created'] = date('Y-m-d H:i:s');
            $this->in['updated'] = date('Y-m-d H:i:s');
            $this->in['author'] = 'admin';

            $this->dbh->update('news', $this->in, 'id = :id', ['id' => $id]);
        }
        Util::ses('m', 'read');
        header('Access-Control-Expose-Headers: X-Redirect-Method');
        header('X-Redirect-Method: read');
        return []; // Return empty array to trigger client-side redirect
    }

    public function delete(): array
    {
        Util::elog(__METHOD__);

        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        $this->dbh->delete('news', 'id = :id', ['id' => $id]);
        Util::ses('page', '', '1');
        Util::ses('m', 'list');
        header('Access-Control-Expose-Headers: X-Redirect-Method');
        header('X-Redirect-Method: list');
        return []; // Return empty array to trigger client-side redirect
    }

    public function list(): array
    {
        Util::elog(__METHOD__);

        // Get pagination parameters
        $page = filter_var($_REQUEST['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
        $perPage = filter_var($_REQUEST['perpage'] ?? self::DEFAULT_PER_PAGE, FILTER_VALIDATE_INT) ?: self::DEFAULT_PER_PAGE;

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
