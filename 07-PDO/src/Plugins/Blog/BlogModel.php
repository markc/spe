<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Plugins\Blog;

use SPE\PDO\Core\Ctx;
use SPE\App\QueryType;

final class BlogModel {
    private array $f = ['id' => 0, 'title' => '', 'slug' => '', 'content' => '', 'type' => 'post', 'icon' => ''];

    public function __construct(private Ctx $ctx) {
        foreach ($this->f as $k => &$v) $v = $_REQUEST[$k] ?? $v;
    }

    private function slug(string $t): string {
        return $t |> strtolower(...) |> (fn($s) => preg_replace('/[^a-z0-9]+/', '-', $s)) |> (fn($s) => trim($s, '-'));
    }

    public function create(): array {
        if ($_POST) {
            $content = str_replace("\r\n", "\n", $this->f['content']);
            $this->ctx->db->create('posts', [
                'title' => $this->f['title'],
                'slug' => $this->f['slug'] ?: $this->slug($this->f['title']),
                'content' => $content,
                'type' => $this->f['type'],
                'icon' => $this->f['icon'],
                'author' => 'admin',
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s')
            ]);
            $this->ctx->flash('msg', 'Post created successfully');
            $this->ctx->flash('type', 'success');
            header('Location: ?o=Blog&edit');
            exit;
        }
        return [];
    }

    public function read(): array {
        return $this->ctx->db->read('posts', '*', 'id=:id', ['id' => (int)$this->f['id']], QueryType::One);
    }

    public function update(): array {
        $id = (int)$this->f['id'];
        if ($_POST) {
            $content = str_replace("\r\n", "\n", $this->f['content']);
            $this->ctx->db->update('posts', [
                'title' => $this->f['title'],
                'slug' => $this->f['slug'] ?: $this->slug($this->f['title']),
                'content' => $content,
                'type' => $this->f['type'],
                'icon' => $this->f['icon'],
                'updated' => date('Y-m-d H:i:s')
            ], 'id=:id', ['id' => $id]);
            $this->ctx->flash('msg', 'Post updated successfully');
            $this->ctx->flash('type', 'success');
            header('Location: ?o=Blog&edit');
            exit;
        }
        return $this->ctx->db->read('posts', '*', 'id=:id', ['id' => $id], QueryType::One);
    }

    public function delete(): array {
        $this->ctx->db->delete('posts', 'id=:id', ['id' => (int)$this->f['id']]);
        $this->ctx->flash('msg', 'Post deleted');
        $this->ctx->flash('type', 'success');
        header('Location: ?o=Blog&edit');
        exit;
    }

    public function list(): array {
        $pg = (int)($_REQUEST['page'] ?? 1) ?: 1;
        $pp = 6;
        $q = trim($_GET['q'] ?? '');
        $ed = isset($_GET['edit']);
        $w = array_filter([$ed ? '' : "type='post'", $q ? "(title LIKE :s OR content LIKE :s)" : '']);
        $where = $w ? implode(' AND ', $w) : '1=1';
        $p = $q ? ['s' => "%$q%"] : [];
        $total = $this->ctx->db->read('posts', 'COUNT(*)', $where, $p, QueryType::Col);
        return [
            'edit' => $ed,
            'items' => $this->ctx->db->read('posts', '*', "$where ORDER BY updated DESC LIMIT :l OFFSET :o",
                [...$p, 'l' => $pp, 'o' => ($pg - 1) * $pp]),
            'pagination' => ['page' => $pg, 'perPage' => $pp, 'total' => $total, 'pages' => (int)ceil($total / $pp)]
        ];
    }
}
