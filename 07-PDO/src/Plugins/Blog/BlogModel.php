<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Plugins\Blog;

use SPE\PDO\Core\{Db, Ctx, Plugin, QueryType};

final class BlogModel extends Plugin {
    private const int DEFAULT_PER_PAGE = 6;
    private ?Db $dbh = null;
    private array $in = ['id' => 0, 'title' => '', 'content' => '', 'author' => '', 'created' => null, 'updated' => null];

    #[\Override]
    public function __construct(protected Ctx $ctx) {
        parent::__construct($ctx);
        foreach ($this->in as $k => &$v) $v = $_REQUEST[$k] ?? $v;
        if (is_null($this->dbh)) {
            $this->dbh = new Db(['type' => 'sqlite', 'path' => __DIR__ . '/blog.db', 'name' => 'blog']);
        }
    }

    #[\Override] public function create(): array {
        if ($_POST) {
            $data = [
                'title' => $this->in['title'],
                'content' => $this->in['content'],
                'author' => 'admin',
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s')
            ];
            $this->dbh->create('posts', $data);
            header('Location: ?o=Blog&t=' . $this->ctx->in['t']);
            exit;
        }
        return [];
    }

    #[\Override] public function read(): array {
        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        return $this->dbh->read('posts', '*', 'id = :id', ['id' => $id], QueryType::One);
    }

    #[\Override] public function update(): array {
        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        if ($_POST) {
            $this->in['updated'] = date('Y-m-d H:i:s');
            $data = ['title' => $this->in['title'], 'content' => $this->in['content'], 'updated' => $this->in['updated']];
            $this->dbh->update('posts', $data, 'id = :id', ['id' => $id]);
            header('Location: ?o=Blog&m=read&id=' . $id . '&t=' . $this->ctx->in['t']);
            exit;
        }
        return $this->dbh->read('posts', '*', 'id = :id', ['id' => $id], QueryType::One);
    }

    #[\Override] public function delete(): array {
        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        $this->dbh->delete('posts', 'id = :id', ['id' => $id]);
        header('Location: ?o=Blog&t=' . $this->ctx->in['t']);
        exit;
    }

    #[\Override] public function list(): array {
        $page = filter_var($_REQUEST['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
        $perPage = filter_var($_REQUEST['perpage'] ?? self::DEFAULT_PER_PAGE, FILTER_VALIDATE_INT) ?: self::DEFAULT_PER_PAGE;
        $offset = ($page - 1) * $perPage;
        $searchQuery = trim($_GET['q'] ?? '');

        [$where, $params] = match ($searchQuery !== '') {
            true => ['(title LIKE :search OR content LIKE :search)', ['search' => '%' . $searchQuery . '%']],
            false => ['1=1', []]
        };

        $total = $this->dbh->read('posts', 'COUNT(*)', $where, $params, QueryType::Column);
        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        return [
            'items' => $this->dbh->read('posts', '*', $where . ' ORDER BY updated DESC, created DESC LIMIT :limit OFFSET :offset', $params, QueryType::All),
            'pagination' => ['page' => $page, 'perPage' => $perPage, 'total' => $total, 'pages' => ceil($total / $perPage)]
        ];
    }
}
