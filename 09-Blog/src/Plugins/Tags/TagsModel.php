<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Tags;

use SPE\Blog\Core\{Flash, Plugin, QueryType, Role};

/** Managing tags is admin-only. */
final class TagsModel extends Plugin
{
    #[\Override]
    public static function guard(string $m): Role
    {
        return Role::Admin;
    }

    #[\Override]
    public function list(): array
    {
        return ['title' => 'Tags', 'items' => $this->ctx->db->qry(
            'SELECT t.id, t.name, t.slug, COUNT(pt.post_id) AS posts
             FROM tags t LEFT JOIN post_tags pt ON pt.tag_id = t.id
             GROUP BY t.id ORDER BY t.name',
        )];
    }

    #[\Override]
    public function update(): array
    {
        $tag = $this->ctx->db->read('tags', '*', 'id = :id', ['id' => $this->ctx->in['i']], QueryType::One);
        if (!$tag) {
            return ['title' => 'Not found', 'body' => 'There is no such tag.'];
        }
        if ($p = $this->ctx->post()) {
            $name = trim((string) ($p['name'] ?? '')) ?: $tag['name'];
            $this->ctx->db->update('tags', ['name' => $name, 'slug' => $this->slug($name)], 'id = :id', ['id' => $tag['id']]);
            $this->ctx->flash(Flash::Success, 'Tag renamed.');
            $this->redirect('?o=Tags');
        }
        return ['title' => "Edit: {$tag['name']}", 'tag' => $tag, 'action' => "?o=Tags&m=update&i={$tag['id']}"];
    }

    #[\Override]
    public function delete(): array
    {
        if ($this->ctx->post()) {
            $this->ctx->db->delete('tags', 'id = :id', ['id' => $this->ctx->in['i']])
                ? $this->ctx->flash(Flash::Success, 'Tag deleted.')
                : $this->ctx->flash(Flash::Warning, 'No such tag.');
        }
        $this->redirect('?o=Tags');
    }

    private function slug(string $name): string
    {
        $slug = $name |> strtolower(...) |> (static fn(string $s) => preg_replace('/[^a-z0-9]+/', '-', $s)) |> (static fn(string $s) => trim($s, '-'));
        return $slug ?: 'tag';
    }
}
