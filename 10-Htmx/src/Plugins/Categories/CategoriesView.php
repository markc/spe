<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Htmx\Plugins\Categories;

use SPE\App\Util;
use SPE\Htmx\Core\Theme;

final class CategoriesView extends Theme
{
    private function t(): string
    {
        return '&t=' . $this->ctx->in['t'];
    }

    public function create(): string
    {
        if (Util::is_post())
            return '';
        return $this->form();
    }

    public function read(): string
    {
        $a = $this->ctx->ary;
        if (empty($a))
            return '<div class="card"><p>Category not found.</p></div>';
        $t = $this->t();

        $name = htmlspecialchars($a['name']);
        $desc = htmlspecialchars($a['description']);
        $postCount = count($a['posts'] ?? []);

        $postList = '';
        if ($postCount > 0) {
            $postList = '<h3 class="mt-2">Posts in this category</h3><ul>';
            foreach ($a['posts'] as $post) {
                $title = htmlspecialchars($post['title']);
                $type = $post['type'] === 'page' ? '📄' : '📝';
                $link = $post['type'] === 'page' ? "?p={$post['slug']}" : "?o=Blog&m=read&id={$post['id']}";
                $postList .= "<li>$type <a href=\"$link$t\">$title</a></li>";
            }
            $postList .= '</ul>';
        }

        return <<<HTML
        <div class="card">
            <h2>🏷️ $name</h2>
            <p class="text-muted">$desc</p>
            <p><strong>$postCount</strong> posts in this category</p>
            $postList
            <div class="flex mt-3" style="gap:0.5rem">
                <a href="?o=Categories$t" class="btn">« Back</a>
                <a href="?o=Categories&m=update&id={$a['id']}$t" class="btn">Edit</a>
            </div>
        </div>
        HTML;
    }

    public function update(): string
    {
        if (Util::is_post())
            return '';
        return $this->form($this->ctx->ary);
    }

    public function delete(): string
    {
        return '';
    }

    public function list(): string
    {
        $a = $this->ctx->ary;
        $t = $this->t();

        $html = <<<HTML
        <div class="card">
            <div class="flex" style="justify-content:space-between;align-items:center;margin-bottom:1rem">
                <h2>🏷️ Categories</h2>
                <a href="?o=Categories&m=create$t" class="btn">+ New Category</a>
            </div>
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="border-bottom:2px solid var(--border)">
                        <th style="text-align:left;padding:0.5rem">Name</th>
                        <th style="text-align:left;padding:0.5rem">Slug</th>
                        <th style="text-align:center;padding:0.5rem">Posts</th>
                        <th style="text-align:right;padding:0.5rem">Actions</th>
                    </tr>
                </thead>
                <tbody>
        HTML;

        $protected = ['uncategorized', 'main'];
        foreach ($a['items'] as $item) {
            $name = htmlspecialchars($item['name']);
            $slug = htmlspecialchars($item['slug']);
            $isProtected = in_array($slug, $protected);
            $deleteBtn = $isProtected
                ? '<span class="text-muted" title="Protected">🔒</span>'
                : "<a href=\"?o=Categories&m=delete&id={$item['id']}$t\" title=\"Delete\" class=\"icon\" onclick=\"return confirm('Delete this category?')\">🗑️</a>";

            $html .= <<<HTML
                <tr style="border-bottom:1px solid var(--border)">
                    <td style="padding:0.5rem"><a href="?o=Categories&m=read&id={$item['id']}$t">$name</a></td>
                    <td style="padding:0.5rem"><code>$slug</code></td>
                    <td style="padding:0.5rem;text-align:center">{$item['post_count']}</td>
                    <td style="padding:0.5rem;text-align:right">
                        <a href="?o=Categories&m=update&id={$item['id']}$t" title="Edit" class="icon">✏️</a>
                        $deleteBtn
                    </td>
                </tr>
            HTML;
        }

        return $html . '</tbody></table></div>';
    }

    private function form(array $data = []): string
    {
        $id = $data['id'] ?? 0;
        $t = $this->t();
        $name = htmlspecialchars($data['name'] ?? '');
        $slug = htmlspecialchars($data['slug'] ?? '');
        $description = htmlspecialchars($data['description'] ?? '');
        $action = $id ? "?o=Categories&m=update&id=$id$t" : "?o=Categories&m=create$t";
        $heading = $id ? 'Edit Category' : 'New Category';
        $btnText = $id ? 'Update' : 'Create';

        return <<<HTML
        <div class="card" style="max-width:500px;margin:2rem auto">
            <h2>$heading</h2>
            <form method="post" action="$action">
                <input type="hidden" name="id" value="$id">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="$name" required>
                </div>
                <div class="form-group">
                    <label for="slug">Slug (URL)</label>
                    <input type="text" id="slug" name="slug" value="$slug" placeholder="auto-generated from name">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3">$description</textarea>
                </div>
                <div class="text-right">
                    <a href="?o=Categories$t" class="btn btn-muted">Cancel</a>
                    <button type="submit" class="btn">$btnText</button>
                </div>
            </form>
        </div>
        HTML;
    }
}
