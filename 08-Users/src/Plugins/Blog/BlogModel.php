<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Users\Plugins\Blog;

use SPE\App\{Db, QueryType};
use SPE\Users\Core\{Ctx, Plugin};

final class BlogModel extends Plugin {
    private const int PER_PAGE = 6;
    private ?Db $dbh = null;
    private array $in = ['id' => 0, 'title' => '', 'content' => '', 'author' => '', 'created' => null, 'updated' => null];

    #[\Override]
    public function __construct(protected Ctx $ctx) {
        parent::__construct($ctx);
        foreach ($this->in as $k => &$v) $v = $_REQUEST[$k] ?? $v;
        $this->dbh ??= new Db('blog');
    }

    #[\Override] public function create(): array {
        if ($_POST) {
            $this->dbh->create('posts', [
                'title' => $this->in['title'], 'content' => $this->in['content'], 'author' => 'admin',
                'created' => date('Y-m-d H:i:s'), 'updated' => date('Y-m-d H:i:s')
            ]);
            header('Location: ?o=Blog&t=' . $this->ctx->in['t']);
            exit;
        }
        return [];
    }

    #[\Override] public function read(): array {
        return $this->dbh->read('posts', '*', 'id = :id', ['id' => (int)$this->in['id']], QueryType::One) ?: [];
    }

    #[\Override] public function update(): array {
        $id = (int)$this->in['id'];
        if ($_POST) {
            $this->dbh->update('posts', [
                'title' => $this->in['title'], 'content' => $this->in['content'], 'updated' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $id]);
            header("Location: ?o=Blog&m=read&id=$id&t=" . $this->ctx->in['t']);
            exit;
        }
        return $this->dbh->read('posts', '*', 'id = :id', ['id' => $id], QueryType::One) ?: [];
    }

    #[\Override] public function delete(): array {
        $this->dbh->delete('posts', 'id = :id', ['id' => (int)$this->in['id']]);
        header('Location: ?o=Blog&t=' . $this->ctx->in['t']);
        exit;
    }

    #[\Override] public function list(): array {
        $page = filter_var($_REQUEST['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
        $pp = filter_var($_REQUEST['perpage'] ?? self::PER_PAGE, FILTER_VALIDATE_INT) ?: self::PER_PAGE;
        $q = trim($_GET['q'] ?? '');

        [$where, $params] = $q ? ['(title LIKE :s OR content LIKE :s)', ['s' => "%$q%"]] : ['1=1', []];
        $total = $this->dbh->read('posts', 'COUNT(*)', $where, $params, QueryType::Col);

        return [
            'items' => $this->dbh->read('posts', '*', "$where ORDER BY updated DESC LIMIT :l OFFSET :o", [...$params, 'l' => $pp, 'o' => ($page - 1) * $pp]),
            'pagination' => ['page' => $page, 'perPage' => $pp, 'total' => $total, 'pages' => ceil($total / $pp)]
        ];
    }
}
