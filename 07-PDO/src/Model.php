<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO;

use SPE\App\{Db, QueryType};

final class Model {
    private Db $db;
    private array $f = ['id' => 0, 'title' => '', 'slug' => '', 'content' => '', 'type' => 'post', 'icon' => ''];

    public function __construct(private App $c) {
        foreach ($this->f as $k => &$v) $v = $_REQUEST[$k] ?? $v;
        $this->db = new Db('blog');
    }

    private function slug(string $t): string {
        return $t |> strtolower(...) |> (fn($s) => preg_replace('/[^a-z0-9]+/', '-', $s)) |> (fn($s) => trim($s, '-'));
    }

    public function create(): array {
        if ($_POST) {
            $content = str_replace("\r\n", "\n", $this->f['content']);
            $this->db->create('posts', ['title' => $this->f['title'], 'slug' => $this->f['slug'] ?: $this->slug($this->f['title']),
                'content' => $content, 'type' => $this->f['type'], 'icon' => $this->f['icon'],
                'author' => 'admin', 'created' => date('Y-m-d H:i:s'), 'updated' => date('Y-m-d H:i:s')]);
            header('Location: ?o=Blog&edit&t=' . $this->c->in['t']); exit;
        }
        return [];
    }

    public function read(): array {
        return $this->db->read('posts', '*', 'id=:id', ['id' => (int)$this->f['id']], QueryType::One);
    }

    public function update(): array {
        $id = (int)$this->f['id'];
        if ($_POST) {
            $content = str_replace("\r\n", "\n", $this->f['content']);
            $this->db->update('posts', ['title' => $this->f['title'], 'slug' => $this->f['slug'] ?: $this->slug($this->f['title']),
                'content' => $content, 'type' => $this->f['type'], 'icon' => $this->f['icon'],
                'updated' => date('Y-m-d H:i:s')], 'id=:id', ['id' => $id]);
            header('Location: ?o=Blog&edit&t=' . $this->c->in['t']); exit;
        }
        return $this->db->read('posts', '*', 'id=:id', ['id' => $id], QueryType::One);
    }

    public function delete(): array {
        $this->db->delete('posts', 'id=:id', ['id' => (int)$this->f['id']]);
        header('Location: ?o=Blog&edit&t=' . $this->c->in['t']); exit;
    }

    public function list(): array {
        $pg = (int)($_REQUEST['page'] ?? 1) ?: 1;
        $pp = 6;
        $q = trim($_GET['q'] ?? '');
        $ed = isset($_GET['edit']);
        $w = array_filter([$ed ? '' : "type='post'", $q ? "(title LIKE :s OR content LIKE :s)" : '']);
        $where = $w ? implode(' AND ', $w) : '1=1';
        $p = $q ? ['s' => "%$q%"] : [];
        $total = $this->db->read('posts', 'COUNT(*)', $where, $p, QueryType::Col);
        return ['edit' => $ed, 'items' => $this->db->read('posts', '*', "$where ORDER BY updated DESC LIMIT :l OFFSET :o",
            [...$p, 'l' => $pp, 'o' => ($pg - 1) * $pp]),
            'pagination' => ['page' => $pg, 'perPage' => $pp, 'total' => $total, 'pages' => (int)ceil($total / $pp)]];
    }
}
