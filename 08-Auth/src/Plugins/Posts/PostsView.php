<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Auth\Plugins\Posts;

use SPE\Auth\Core\{Role, View};

final class PostsView extends View
{
    private function isAdmin(): bool
    {
        return $this->ctx->role()->can(Role::Admin);
    }

    #[\Override]
    public function list(): string
    {
        $admin = $this->isAdmin();
        $rows = array_map(function (array $p) use ($admin) {
            $actions = $admin ? <<<HTML
<a class="btn btn-sm" href="?o=Posts&m=update&i={$this->e($p['id'])}"><i data-lucide="pencil"></i></a>
<form method="post" action="?o=Posts&m=delete&i={$this->e($p['id'])}" style="display:inline" onsubmit="return confirm('Delete this post?')">{$this->csrf()}<button type="submit" class="btn btn-sm btn-danger"><i data-lucide="trash-2"></i></button></form>
HTML : '';
            return <<<HTML
<tr>
    <td><a href="?o=Posts&m=read&i={$this->e($p['id'])}">{$this->e($p['title'])}</a></td>
    <td><code>{$this->e($p['slug'])}</code></td>
    <td>{$this->e($p['created'])}</td>
    <td class="text-right">$actions</td>
</tr>
HTML;
        }, $this->data['items']);
        $body = implode('', $rows) ?: '<tr><td colspan="4">No posts yet.</td></tr>';
        $new = $admin ? '<a class="btn" href="?o=Posts&m=create"><i data-lucide="plus"></i> New post</a>' : '';

        return <<<HTML
<div class="card">
    <div class="list-header"><h2>Posts</h2>$new</div>
    <table class="data-table">
        <thead><tr><th>Title</th><th>Slug</th><th>Created</th><th></th></tr></thead>
        <tbody>$body</tbody>
    </table>
</div>
HTML;
    }

    #[\Override]
    public function read(): string
    {
        if (!isset($this->data['slug'])) {
            return $this->card();
        }
        $edit = $this->isAdmin() ? '<a class="btn" href="?o=Posts&m=update&i=' . $this->e($this->data['id']) . '"><i data-lucide="pencil"></i> Edit</a>' : '';
        return <<<HTML
<article class="card">
    <h2>{$this->e($this->data['title'])}</h2>
    <p class="text-muted"><code>{$this->e($this->data['slug'])}</code> · {$this->e($this->data['created'])}</p>
    <p>{$this->e($this->data['body'])}</p>
    <div class="btn-group mt-4"><a class="btn" href="?o=Posts"><i data-lucide="arrow-left"></i> Back</a>$edit</div>
</article>
HTML;
    }

    #[\Override]
    public function create(): string { return $this->form(); }

    #[\Override]
    public function update(): string { return isset($this->data['post']) ? $this->form() : $this->card(); }

    private function form(): string
    {
        $post = $this->data['post'];
        return <<<HTML
<div class="card">
    <h2>{$this->e($this->data['title'])}</h2>
    <form method="post" action="{$this->e($this->data['action'])}">
        {$this->csrf()}
        <div class="form-group"><label for="title">Title</label><input type="text" id="title" name="title" value="{$this->e($post['title'])}" required></div>
        <div class="form-group"><label for="body">Body</label><textarea id="body" name="body" rows="6">{$this->e($post['body'])}</textarea></div>
        <div class="btn-group"><button type="submit" class="btn">Save</button><a class="btn btn-ghost" href="?o=Posts">Cancel</a></div>
    </form>
</div>
HTML;
    }
}
