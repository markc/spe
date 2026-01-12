<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Docs;

use SPE\App\Util;
use SPE\Blog\Core\Theme;

final class DocsView extends Theme
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
        $t = $this->t();

        if (empty($a)) {
            return '<div class="card"><p>Doc not found.</p></div>';
        }

        if (!$a['file_exists']) {
            $path = htmlspecialchars($a['content']);
            return <<<HTML
            <div class="card">
                <p><a href="?o=Docs$t">← Back to Docs</a></p>
                <h2>File Not Found</h2>
                <p>The markdown file could not be read:</p>
                <code>$path</code>
                <p class="mt-2"><a href="?o=Docs&m=update&id={$a['id']}$t" class="btn">Edit Doc Entry</a></p>
            </div>
            HTML;
        }

        $title = htmlspecialchars($a['title']);
        $icon = htmlspecialchars($a['icon'] ?: '📚');
        $content = Util::md($a['file_content']);
        $author = htmlspecialchars($a['author']);
        $date = date('M j, Y', strtotime($a['created']));
        $updated = date('M j, Y', strtotime($a['updated']));

        // Categories
        $cats = '';
        if (!empty($a['categories'])) {
            $cats = implode('', array_map(
                static fn($c) => '<span class="tag">' . htmlspecialchars($c['name']) . '</span>',
                $a['categories'],
            ));
            $cats = "<div class=\"blog-categories\">$cats</div>";
        }

        // Featured image
        $featuredImg = '';
        if (!empty($a['featured_image'])) {
            $img = htmlspecialchars($a['featured_image']);
            $featuredImg = "<img src=\"$img\" alt=\"$title\" class=\"blog-featured-image\">";
        }

        // Prev/Next navigation
        $prevNext = $this->buildPrevNext($a['prev'], $a['next']);

        return <<<HTML
        <article class="blog-single">
            <header class="blog-single-header">
                <h1><a href="?o=Docs$t" class="back-arrow">«</a> $icon $title</h1>
                <div class="blog-single-meta">
                    <span>By $author</span> · <span>$date</span>
                    <span class="text-muted">(updated $updated)</span>
                </div>
                $cats
            </header>
            $featuredImg
            <div class="prose">$content</div>
            $prevNext
        </article>
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
                <h2>📚 Documentation</h2>
                <a href="?o=Docs&m=create$t" class="btn">+ New Doc</a>
            </div>
        HTML;

        if (empty($a['grouped'])) {
            $html .= '<p class="text-muted">No documentation entries yet.</p>';
        } else {
            foreach ($a['grouped'] as $category => $docs) {
                $catName = htmlspecialchars($category);
                $html .= "<div class=\"sidebar-group\"><div class=\"sidebar-group-title\">$catName</div>";
                $html .= '<div class="docs-list">';

                foreach ($docs as $doc) {
                    $icon = htmlspecialchars($doc['icon'] ?: '📄');
                    $title = htmlspecialchars($doc['title']);
                    $slug = htmlspecialchars($doc['slug']);
                    $excerpt = htmlspecialchars($doc['excerpt'] ?: '');
                    $path = htmlspecialchars($doc['content']);

                    // File existence warning
                    $warning = '';
                    if (!$doc['file_exists']) {
                        $warning = '<span class="tag" style="background:var(--danger);color:#fff" title="File not found">⚠️ Missing</span>';
                    }

                    // Actions
                    $actions = <<<ACT
                    <span class="docs-actions">
                        <a href="?o=Docs&m=update&id={$doc['id']}$t" title="Edit">✏️</a>
                        <a href="?o=Docs&m=delete&id={$doc['id']}$t" title="Delete" onclick="return confirm('Delete this doc?')">🗑️</a>
                    </span>
                    ACT;

                    $html .= <<<ITEM
                    <div class="docs-item">
                        <div class="docs-item-header">
                            <a href="?o=Docs&m=read&slug=$slug$t" class="docs-item-title">$icon $title</a>
                            $warning
                            $actions
                        </div>
                        <div class="docs-item-meta">
                            <code class="text-muted" style="font-size:0.8em">$path</code>
                        </div>
                        <div class="docs-item-excerpt text-muted">$excerpt</div>
                    </div>
                    ITEM;
                }

                $html .= '</div></div>';
            }
        }

        return $html . '</div>';
    }

    private function form(array $data = []): string
    {
        $id = $data['id'] ?? 0;
        $t = $this->t();
        $title = htmlspecialchars($data['title'] ?? '');
        $slug = htmlspecialchars($data['slug'] ?? '');
        $content = htmlspecialchars($data['content'] ?? ''); // This is the PATH
        $excerpt = htmlspecialchars($data['excerpt'] ?? '');
        $featuredImage = htmlspecialchars($data['featured_image'] ?? '');
        $icon = htmlspecialchars($data['icon'] ?? '');
        $action = $id ? "?o=Docs&m=update&id=$id$t" : "?o=Docs&m=create$t";
        $heading = $id ? 'Edit Doc' : 'New Doc';
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
                $catCheckboxes .= <<<CB
                    <label class="checkbox-label">
                        <input type="checkbox" name="categories[]" value="$catId"$checked> $catName
                    </label>
                CB;
            }
            $catCheckboxes .= '</div></div>';
        }

        return <<<HTML
        <div class="card">
            <h2>$heading</h2>
            <form method="post" action="$action">
                <input type="hidden" name="id" value="$id">
                <div class="grid-2col">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" value="$title" required>
                    </div>
                    <div class="form-group">
                        <label for="slug">Slug (URL)</label>
                        <input type="text" id="slug" name="slug" value="$slug" placeholder="auto-generated from title">
                    </div>
                </div>
                <div class="grid-3col">
                    <div class="form-group">
                        <label for="content">Markdown File Path</label>
                        <input type="text" id="content" name="content" value="$content" placeholder="docs/README.md" required>
                        <small class="text-muted">Relative to project root, or absolute path</small>
                    </div>
                    <div class="form-group">
                        <label for="icon">Icon (emoji)</label>
                        <input type="text" id="icon" name="icon" value="$icon" placeholder="📚">
                    </div>
                    <div class="form-group">
                        <label for="featured_image">Featured Image URL</label>
                        <input type="text" id="featured_image" name="featured_image" value="$featuredImage" placeholder="https://...">
                    </div>
                </div>
                <div class="form-group">
                    <label for="excerpt">Description/Excerpt</label>
                    <textarea id="excerpt" name="excerpt" rows="2" placeholder="Brief description of this document">$excerpt</textarea>
                </div>
                $catCheckboxes
                <div class="text-right">
                    <a href="?o=Docs$t" class="btn btn-muted">Cancel</a>
                    <button type="submit" class="btn">$btnText</button>
                </div>
            </form>
        </div>
        HTML;
    }

    private function buildPrevNext(array|false|null $prev, array|false|null $next): string
    {
        $t = $this->t();
        $prevHtml = $prev
            ? "<a href=\"?o=Docs&m=read&slug={$prev['slug']}$t\" class=\"blog-nav-prev\"><span>← Previous</span><strong>{$prev['title']}</strong></a>"
            : '<span></span>';
        $nextHtml = $next
            ? "<a href=\"?o=Docs&m=read&slug={$next['slug']}$t\" class=\"blog-nav-next\"><span>Next →</span><strong>{$next['title']}</strong></a>"
            : '<span></span>';

        return <<<HTML
        <nav class="blog-nav">
            $prevHtml
            $nextHtml
        </nav>
        HTML;
    }
}
