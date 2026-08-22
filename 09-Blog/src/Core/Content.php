<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

/**
 * Shared CRUDL for content of one Type. Blog and Docs are the same code with a
 * different type(); the plugin that extends this only says which kind it manages.
 */
abstract class Content extends Plugin
{
    abstract protected function type(): Type;

    /** Reading is public; writing requires an admin. */
    #[\Override]
    public static function guard(string $m): Role
    {
        return in_array($m, ['create', 'update', 'delete'], true) ? Role::Admin : Role::Anon;
    }

    #[\Override]
    public function list(): array
    {
        $type = $this->type();
        $tag = $this->ctx->in['tag'];
        $where = 'p.type = :type';
        $params = ['type' => $type->value];
        if ($tag !== '') {
            $where .= ' AND t.slug = :tag';
            $params['tag'] = $tag;
        }
        $join = $tag !== '' ? 'JOIN post_tags pt ON pt.post_id = p.id JOIN tags t ON t.id = pt.tag_id' : '';

        $total = (int) $this->ctx->db->qry("SELECT COUNT(*) FROM posts p $join WHERE $where", $params, QueryType::Col);
        $pages = max(1, (int) ceil($total / $this->ctx->perPage));
        $page = min($this->ctx->in['page'], $pages);
        $offset = ($page - 1) * $this->ctx->perPage;

        $rows = $this->ctx->db->qry(
            "SELECT p.* FROM posts p $join WHERE $where ORDER BY p.id DESC LIMIT :limit OFFSET :offset",
            [...$params, 'limit' => $this->ctx->perPage, 'offset' => $offset],
        );
        $items = array_map(fn(array $r) => Post::fromRow($r, $this->tagsOf((int) $r['id'])), $rows);

        return [
            'title' => $type->label(),
            'type' => $type,
            'items' => $items,
            'page' => $page,
            'pages' => $pages,
            'tag' => $tag,
            'allTags' => $this->ctx->db->read('tags', 'name, slug', order: 'ORDER BY name'),
        ];
    }

    #[\Override]
    public function read(): array
    {
        $post = $this->find($this->ctx->in['i']);
        if (!$post) {
            http_response_code(404);
            return ['title' => 'Not found', 'body' => 'There is no such entry.'];
        }
        $ids = array_map(intval(...), $this->ctx->db->read('posts', 'id', 'type = :t', ['t' => $this->type()->value], QueryType::All, 'ORDER BY id')
            |> (static fn(array $rows) => array_column($rows, 'id')));
        $index = array_search($post->id, $ids, true);

        return [
            'title' => $post->title,
            'type' => $this->type(),
            'post' => $post,
            'prev' => $index > 0 ? $ids[$index - 1] : null,
            'next' => $index < count($ids) - 1 ? $ids[$index + 1] : null,
            'first' => array_first($ids),
            'last' => array_last($ids),
        ];
    }

    #[\Override]
    public function create(): array
    {
        if ($p = $this->ctx->post()) {
            $id = $this->ctx->db->create('posts', $this->fields($p));
            $this->syncTags($id, (string) ($p['tags'] ?? ''));
            $this->ctx->flash(Flash::Success, "{$this->type()->label()} entry created.");
            $this->redirect("?o={$this->ctx->in['o']}&m=read&i=$id");
        }
        return ['title' => "New {$this->type()->value}", 'form' => ['title' => '', 'body' => '', 'tags' => ''], 'action' => "?o={$this->ctx->in['o']}&m=create"];
    }

    #[\Override]
    public function update(): array
    {
        $post = $this->find($this->ctx->in['i']);
        if (!$post) {
            http_response_code(404);
            return ['title' => 'Not found', 'body' => 'There is no such entry.'];
        }
        if ($p = $this->ctx->post()) {
            (void) $this->ctx->db->update('posts', $this->fields($p), 'id = :id', ['id' => $post->id]);
            $this->syncTags($post->id, (string) ($p['tags'] ?? ''));
            $this->ctx->flash(Flash::Success, 'Saved.');
            $this->redirect("?o={$this->ctx->in['o']}&m=read&i={$post->id}");
        }
        $tags = implode(', ', array_column($post->tags, 'name'));
        return ['title' => "Edit: {$post->title}", 'form' => ['title' => $post->title, 'body' => $post->body, 'tags' => $tags], 'action' => "?o={$this->ctx->in['o']}&m=update&i={$post->id}"];
    }

    #[\Override]
    public function delete(): array
    {
        if ($this->ctx->post()) {
            $this->ctx->db->delete('posts', 'id = :id AND type = :t', ['id' => $this->ctx->in['i'], 't' => $this->type()->value])
                ? $this->ctx->flash(Flash::Success, 'Deleted.')
                : $this->ctx->flash(Flash::Warning, 'No such entry.');
        }
        $this->redirect("?o={$this->ctx->in['o']}");
    }

    private function find(int $id): ?Post
    {
        $row = $this->ctx->db->read('posts', '*', 'id = :id AND type = :t', ['id' => $id, 't' => $this->type()->value], QueryType::One);
        return $row ? Post::fromRow($row, $this->tagsOf($id)) : null;
    }

    /** @return list<array{id:int,name:string,slug:string}> */
    private function tagsOf(int $postId): array
    {
        return $this->ctx->db->qry(
            'SELECT t.id, t.name, t.slug FROM tags t JOIN post_tags pt ON pt.tag_id = t.id WHERE pt.post_id = :id ORDER BY t.name',
            ['id' => $postId],
        );
    }

    /** @return array{type:string,title:string,slug:string,body:string} */
    private function fields(array $p): array
    {
        $title = trim((string) ($p['title'] ?? '')) ?: 'Untitled';
        return ['type' => $this->type()->value, 'title' => $title, 'slug' => self::slugify($title, true), 'body' => trim((string) ($p['body'] ?? ''))];
    }

    private function syncTags(int $postId, string $csv): void
    {
        (void) $this->ctx->db->delete('post_tags', 'post_id = :id', ['id' => $postId]);
        // Deduplicate by slug, so "PHP, php" is one tag and one junction row.
        $bySlug = [];
        foreach (array_filter(array_map(trim(...), explode(',', $csv))) as $name) {
            $bySlug[self::slugify($name, false)] ??= $name;
        }
        foreach ($bySlug as $slug => $name) {
            $id = (int) $this->ctx->db->read('tags', 'id', 'slug = :s', ['s' => $slug], QueryType::Col)
                ?: $this->ctx->db->create('tags', ['name' => $name, 'slug' => $slug]);
            (void) $this->ctx->db->create('post_tags', ['post_id' => $postId, 'tag_id' => $id]);
        }
    }

    private static function slugify(string $text, bool $unique): string
    {
        $slug = $text |> strtolower(...) |> (static fn(string $s) => preg_replace('/[^a-z0-9]+/', '-', $s)) |> (static fn(string $s) => trim($s, '-'));
        $slug = $slug ?: 'entry';
        return $unique ? $slug . '-' . substr(bin2hex(random_bytes(3)), 0, 6) : $slug;
    }
}
