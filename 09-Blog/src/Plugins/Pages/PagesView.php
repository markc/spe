<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Pages;

use SPE\App\Util;
use SPE\Blog\Core\Theme;

final class PagesView extends Theme
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
            return '<div class="card"><p>Page not found.</p></div>';

        $content = Util::md($a['content'] ?? '');
        return <<<HTML
        <div class="prose">$content</div>
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
            <div class="list-header">
                <h2><i data-lucide="file-text" class="inline-icon"></i> Pages</h2>
                <a href="?o=Pages&m=create$t" class="btn">+ New Page</a>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="text-center">Icon</th>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Updated</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
        HTML;

        foreach ($a['items'] as $item) {
            $title = htmlspecialchars($item['title']);
            $slug = htmlspecialchars($item['slug']);
            $icon = htmlspecialchars($item['icon'] ?? '<i data-lucide="file-text" class="inline-icon"></i>');
            $isCore = in_array($slug, ['home', 'about', 'contact']);
            $deleteBtn = $isCore
                ? '<span class="text-muted" title="Core page"><i data-lucide="lock" class="inline-icon"></i></span>'
                : "<a href=\"?o=Pages&m=delete&id={$item['id']}$t\" title=\"Delete\" class=\"icon\" onclick=\"return confirm('Delete this page?')\"><i data-lucide=\"trash-2\" class=\"inline-icon\"></i></a>";

            $html .= <<<HTML
                <tr>
                    <td class="text-center">$icon</td>
                    <td><a href="?p=$slug$t">$title</a></td>
                    <td><code>$slug</code></td>
                    <td><small>{$item['updated']}</small></td>
                    <td class="text-right">
                        <a href="?o=Pages&m=update&id={$item['id']}$t" title="Edit" class="icon"><i data-lucide="edit" class="inline-icon"></i></a>
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
        $title = htmlspecialchars($data['title'] ?? '');
        $slug = htmlspecialchars($data['slug'] ?? '');
        $content = htmlspecialchars($data['content'] ?? '');
        $icon = htmlspecialchars($data['icon'] ?? '');
        $action = $id ? "?o=Pages&m=update&id=$id$t" : "?o=Pages&m=create$t";
        $heading = $id ? 'Edit Page' : 'New Page';
        $btnText = $id ? 'Update' : 'Create';

        // Build category checkboxes
        $allCategories = $data['all_categories'] ?? [];
        $postCategories = $data['post_categories'] ?? [];
        $postCatIds = array_column($postCategories, 'id');

        $catCheckboxes = '';
        if (!empty($allCategories)) {
            $catCheckboxes = '<div class="form-group"><label>Categories (for nav grouping)</label><div class="checkbox-group">';
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
                <div class="grid-3col">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" value="$title" required>
                    </div>
                    <div class="form-group">
                        <label for="slug">Slug (URL)</label>
                        <input type="text" id="slug" name="slug" value="$slug" placeholder="auto-generated from title">
                    </div>
                    <div class="form-group">
                        <label for="icon">Icon (emoji or heroicon class)</label>
                        <input type="text" id="icon" name="icon" value="$icon" placeholder="<i data-lucide="home" class="inline-icon"></i> or hero-home">
                    </div>
                </div>
                <div class="form-group">
                    <label for="content">Content (Markdown supported)</label>
                    <textarea id="content" name="content" rows="15" required>$content</textarea>
                </div>
                $catCheckboxes
                <div class="text-right">
                    <a href="?o=Pages$t" class="btn btn-muted">Cancel</a>
                    <button type="submit" class="btn">$btnText</button>
                </div>
            </form>
        </div>
        HTML;
    }
}
