<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Users\Plugins\Blog;

use SPE\App\QueryType;
use SPE\App\Util;
use SPE\Users\Core\Ctx;

final class BlogModel
{
    private array $f = ['i' => 0, 'title' => '', 'slug' => '', 'content' => '', 'type' => 'post', 'icon' => ''];

    public function __construct(
        private Ctx $ctx,
    ) {
        foreach ($this->f as $k => &$v)
            $v = $_REQUEST[$k] ?? $v;
    }

    private function slug(string $t): string
    {
        return $t
            |> strtolower(...)
            |> (static fn($s) => preg_replace('/[^a-z0-9]+/', '-', $s))
            |> (static fn($s) => trim($s, '-'));
    }

    public function create(): array
    {
        if (Util::is_post()) {
            $content = str_replace("\r\n", "\n", $this->f['content']);
            $this->ctx->db->create('posts', [
                'title' => $this->f['title'],
                'slug' => $this->f['slug'] ?: $this->slug($this->f['title']),
                'content' => $content,
                'type' => $this->f['type'],
                'icon' => $this->f['icon'],
                'author' => 'admin',
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ]);
            Util::log('Post created successfully', 'success');
            header('Location: ?o=Blog&edit');
            exit();
        }
        return [];
    }

    public function read(): array
    {
        return $this->ctx->db->read('posts', '*', 'id=:id', ['id' => (int) $this->f['i']], QueryType::One);
    }

    public function update(): array
    {
        $id = (int) $this->f['i'];
        if (Util::is_post()) {
            $content = str_replace("\r\n", "\n", $this->f['content']);
            $this->ctx->db->update(
                'posts',
                [
                    'title' => $this->f['title'],
                    'slug' => $this->f['slug'] ?: $this->slug($this->f['title']),
                    'content' => $content,
                    'type' => $this->f['type'],
                    'icon' => $this->f['icon'],
                    'updated' => date('Y-m-d H:i:s'),
                ],
                'id=:id',
                ['id' => $id],
            );
            Util::log('Post updated successfully', 'success');
            header('Location: ?o=Blog&edit');
            exit();
        }
        return $this->ctx->db->read('posts', '*', 'id=:id', ['id' => $id], QueryType::One);
    }

    public function delete(): array
    {
        $this->ctx->db->delete('posts', 'id=:id', ['id' => (int) $this->f['i']]);
        Util::log('Post deleted', 'success');
        header('Location: ?o=Blog&edit');
        exit();
    }

    public function list(): array
    {
        $pg = (int) ($_REQUEST['page'] ?? 1) ?: 1;
        $pp = $this->ctx->perp;
        $q = trim($_GET['q'] ?? '');
        $ed = isset($_GET['edit']);
        $w = array_filter([$ed ? '' : "type='post'", $q ? '(title LIKE :s OR content LIKE :s)' : '']);
        $where = $w ? implode(' AND ', $w) : '1=1';
        $p = $q ? ['s' => "%$q%"] : [];
        $total = $this->ctx->db->read('posts', 'COUNT(*)', $where, $p, QueryType::Col);
        return [
            'edit' => $ed,
            'items' => $this->ctx->db->read('posts', '*', "$where ORDER BY updated DESC LIMIT :l OFFSET :o", [
                ...$p,
                'l' => $pp,
                'o' => ($pg - 1) * $pp,
            ]),
            'pagination' => ['page' => $pg, 'perPage' => $pp, 'total' => $total, 'pages' => (int) ceil($total / $pp)],
        ];
    }
}
