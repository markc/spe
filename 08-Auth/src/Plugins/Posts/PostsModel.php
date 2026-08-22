<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Auth\Plugins\Posts;

use SPE\Auth\Core\{Flash, Plugin, QueryType, Role};

final class PostsModel extends Plugin
{
    /** Reading is public; writing requires an admin. */
    #[\Override]
    public static function guard(string $m): Role
    {
        return in_array($m, ['create', 'update', 'delete'], true) ? Role::Admin : Role::Anon;
    }

    #[\Override]
    public function list(): array
    {
        return ['title' => 'Posts', 'items' => $this->ctx->db->read('posts', 'id, title, slug, created', order: 'ORDER BY id DESC')];
    }

    #[\Override]
    public function read(): array
    {
        return $this->find($this->ctx->in['i']) ?: ['title' => 'Not found', 'body' => 'There is no such post.'];
    }

    #[\Override]
    public function create(): array
    {
        if ($p = $this->ctx->post()) {
            $id = $this->ctx->db->create('posts', $this->fields($p));
            $this->ctx->flash(Flash::Success, 'Post created.');
            $this->redirect("?o=Posts&m=read&i=$id");
        }
        return ['title' => 'New post', 'post' => ['title' => '', 'body' => ''], 'action' => '?o=Posts&m=create'];
    }

    #[\Override]
    public function update(): array
    {
        $post = $this->find($this->ctx->in['i']);
        if (!$post) {
            http_response_code(404);
            return ['title' => 'Not found', 'body' => 'There is no such post.'];
        }
        if ($p = $this->ctx->post()) {
            (void) $this->ctx->db->update('posts', $this->fields($p), 'id = :id', ['id' => $post['id']]);
            $this->ctx->flash(Flash::Success, 'Post updated.');
            $this->redirect("?o=Posts&m=read&i={$post['id']}");
        }
        return ['title' => "Edit: {$post['title']}", 'post' => $post, 'action' => "?o=Posts&m=update&i={$post['id']}"];
    }

    #[\Override]
    public function delete(): array
    {
        if ($this->ctx->post()) {
            $this->ctx->db->delete('posts', 'id = :id', ['id' => $this->ctx->in['i']])
                ? $this->ctx->flash(Flash::Success, 'Post deleted.')
                : $this->ctx->flash(Flash::Warning, 'No such post.');
        }
        $this->redirect('?o=Posts');
    }

    private function find(int $id): array|false
    {
        return $this->ctx->db->read('posts', '*', 'id = :id', ['id' => $id], QueryType::One);
    }

    /** @return array{title:string,slug:string,body:string} */
    private function fields(array $p): array
    {
        $title = trim((string) ($p['title'] ?? '')) ?: 'Untitled';
        $slug = $title |> strtolower(...) |> (static fn(string $s) => preg_replace('/[^a-z0-9]+/', '-', $s)) |> (static fn(string $s) => trim($s, '-'));
        return ['title' => $title, 'slug' => ($slug ?: 'post') . '-' . substr(bin2hex(random_bytes(3)), 0, 6), 'body' => trim((string) ($p['body'] ?? ''))];
    }
}
