<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Htmx\Plugins\Posts;

use SPE\App\Util;
use SPE\Htmx\Core\Ctx;

final class PostsView
{
    public function __construct(
        private Ctx $ctx,
        private array $a,
    ) {}

    private function t(): string
    {
        return '&t=' . $this->ctx->in['t'];
    }

    public function create(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
            return '';
        return $this->form();
    }

    public function read(): string
    {
        $a = $this->a;
        if (empty($a))
            return (
                '<div class="card"><p>Post not found.</p><a href="?o=Posts'
                . $this->t()
                . '" class="btn">« Back</a></div>'
            );
        $t = $this->t();
        $content = Util::md($a['content'] ?? '');
        $canEdit = $a['can_edit'] ?? false;
        $editBtns = $canEdit ? <<<HTML
                    <a href="?o=Posts&m=update&id={$a['id']}$t" class="btn">Edit</a>
                    <a href="?o=Posts&m=delete&id={$a['id']}$t" class="btn btn-danger" onclick="return confirm('Delete this post?')">Delete</a>
            HTML : '';

        // Build category tags
        $categories = $a['categories'] ?? [];
        $catTags = '';
        if (!empty($categories)) {
            $catTags = '<p class="mt-1">';
            foreach ($categories as $cat) {
                $name = htmlspecialchars($cat['name']);
                $catTags .= "<span class=\"tag\">🏷️ $name</span> ";
            }
            $catTags .= '</p>';
        }

        return <<<HTML
        <div class="card">
            <h2>{$a['title']}</h2>
            <p class="text-muted"><small>By {$a['author']} | Published: {$a['created']} | Updated: {$a['updated']}</small></p>
            $catTags
            <div class="prose mt-2">$content</div>
            <div class="btn-group-end mt-3">
                <a href="?o=Posts$t" class="btn">« Back</a>
                $editBtns
            </div>
        </div>
        HTML;
    }

    public function update(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
            return '';
        return $this->form($this->a);
    }

    public function delete(): string
    {
        return '';
    }

    public function list(): string
    {
        $a = $this->a;
        $t = $this->t();
        $q = htmlspecialchars($_GET['q'] ?? '');
        $clear = $q ? "<a href=\"?o=Posts$t\" class=\"btn\">Clear</a>" : '';
        $canCreate = $a['can_create'] ?? false;
        $createBtn = $canCreate ? "<a href=\"?o=Posts&m=create$t\" class=\"btn\">+ New Post</a>" : '';

        $html = <<<HTML
        <div class="card">
            <div class="list-header">
                <h2>📝 Posts</h2>
                <form class="search-form">
                    <input type="hidden" name="o" value="Posts">
                    <input type="hidden" name="t" value="{$this->ctx->in['t']}">
                    <input type="search" name="q" placeholder="Search..." value="$q" class="search-input">
                    <button type="submit" class="btn">Search</button>
                    $clear
                </form>
                $createBtn
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Updated</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
        HTML;

        $isAdmin = Util::is_adm();
        $userId = $_SESSION['usr']['id'] ?? 0;

        foreach ($a['items'] as $item) {
            $title = htmlspecialchars($item['title']);
            $author = htmlspecialchars($item['author']);
            $canEdit = $isAdmin || (int) $item['author_id'] === (int) $userId;
            $actions = $canEdit
                ? "<a href=\"?o=Posts&m=update&id={$item['id']}$t\" title=\"Edit\" class=\"icon\">✏️</a> <a href=\"?o=Posts&m=delete&id={$item['id']}$t\" title=\"Delete\" class=\"icon\" onclick=\"return confirm('Delete this post?')\">🗑️</a>"
                : '';
            $html .= <<<HTML
                <tr>
                    <td><a href="?o=Posts&m=read&id={$item['id']}$t">$title</a></td>
                    <td><small>$author</small></td>
                    <td><small>{$item['updated']}</small></td>
                    <td class="text-right">$actions</td>
                </tr>
            HTML;
        }

        $html .= '</tbody></table>';

        // Pagination
        $p = $a['pagination'];
        if ($p['pages'] > 1) {
            $sq = $q ? "&q=$q" : '';
            $html .= '<div class="btn-group-center mt-4">';
            if ($p['page'] > 1)
                $html .= "<a href=\"?o=Posts&page=" . ($p['page'] - 1) . "$sq$t\" class=\"btn\">« Prev</a>";
            $html .= "<span class=\"p-2\">Page {$p['page']} of {$p['pages']}</span>";
            if ($p['page'] < $p['pages'])
                $html .= "<a href=\"?o=Posts&page=" . ($p['page'] + 1) . "$sq$t\" class=\"btn\">Next »</a>";
            $html .= '</div>';
        }

        return $html . '</div>';
    }

    private function form(array $data = []): string
    {
        $id = $data['id'] ?? 0;
        $t = $this->t();
        $title = htmlspecialchars($data['title'] ?? '');
        $content = htmlspecialchars($data['content'] ?? '');
        $excerpt = htmlspecialchars($data['excerpt'] ?? '');
        $featuredImage = htmlspecialchars($data['featured_image'] ?? '');
        $action = $id ? "?o=Posts&m=update&id=$id$t" : "?o=Posts&m=create$t";
        $heading = $id ? 'Edit Post' : 'New Post';
        $btnText = $id ? 'Update' : 'Create';

        // Build category checkboxes
        $allCategories = $data['all_categories'] ?? [];
        $postCategories = $data['post_categories'] ?? [];
        $postCatIds = array_column($postCategories, 'id');

        $catCheckboxes = '';
        if (!empty($allCategories)) {
            $catCheckboxes = '<div class="form-group"><label>Categories</label><div class="checkbox-group">';
            foreach ($allCategories as $cat) {
                $catId = (int) $cat['id'];
                $catName = htmlspecialchars($cat['name']);
                $checked = in_array($catId, $postCatIds) ? ' checked' : '';
                $catCheckboxes .= <<<HTML
                    <label class="checkbox-label">
                        <input type="checkbox" name="categories[]" value="$catId"$checked> $catName
                    </label>
                HTML;
            }
            $catCheckboxes .= '</div></div>';
        }

        return <<<HTML
        <div class="card">
            <h2>$heading</h2>
            <form method="post" action="$action">
                <input type="hidden" name="id" value="$id">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="$title" required>
                </div>
                <div class="form-group">
                    <label for="featured_image">Featured Image URL</label>
                    <input type="url" id="featured_image" name="featured_image" value="$featuredImage" placeholder="https://example.com/image.jpg">
                </div>
                <div class="form-group">
                    <label for="excerpt">Excerpt (optional, auto-generated if empty)</label>
                    <textarea id="excerpt" name="excerpt" rows="2" placeholder="Brief summary of the post...">$excerpt</textarea>
                </div>
                <div class="form-group">
                    <label for="content">Content (Markdown supported)</label>
                    <textarea id="content" name="content" rows="12" required>$content</textarea>
                </div>
                $catCheckboxes
                <div class="text-right">
                    <a href="?o=Posts$t" class="btn btn-muted">Cancel</a>
                    <button type="submit" class="btn">$btnText</button>
                </div>
            </form>
        </div>
        HTML;
    }
}
