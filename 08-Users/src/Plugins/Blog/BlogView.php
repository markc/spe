<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Users\Plugins\Blog;

use SPE\App\Util;
use SPE\Users\Core\{Ctx, Theme};

final class BlogView extends Theme {

    private function t(): string {
        return '&t=' . $this->ctx->in['t'];
    }

    public function create(): string {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') return '';
        return $this->form();
    }

    public function read(): string {
        $a = $this->ctx->ary;
        if (empty($a)) return '<div class="card"><p>Blog item not found.</p><a href="?o=Blog' . $this->t() . '" class="btn">« Back</a></div>';
        $t = $this->t();
        $content = Util::md($a['content'] ?? '');
        return <<<HTML
        <div class="card">
            <h2>{$a['title']}</h2>
            <p class="text-muted"><small>By {$a['author']} | Published: {$a['created']} | Updated: {$a['updated']}</small></p>
            <div class="prose mt-2">$content</div>
            <div class="flex mt-3" style="gap:0.5rem">
                <a href="?o=Blog$t" class="btn">« Back</a>
                <a href="?o=Blog&m=update&id={$a['id']}$t" class="btn">Edit</a>
                <a href="?o=Blog&m=delete&id={$a['id']}$t" class="btn btn-danger" onclick="return confirm('Delete this item?')">Delete</a>
            </div>
        </div>
        HTML;
    }

    public function update(): string {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') return '';
        return $this->form($this->ctx->ary);
    }

    public function delete(): string {
        return '';
    }

    public function list(): string {
        $a = $this->ctx->ary;
        $t = $this->t();
        $q = htmlspecialchars($_GET['q'] ?? '');
        $clear = $q ? "<a href=\"?o=Blog$t\" class=\"btn\">Clear</a>" : '';

        $html = <<<HTML
        <div class="card">
            <div class="flex" style="justify-content:space-between;align-items:center;margin-bottom:1rem">
                <form class="flex" style="gap:0.5rem">
                    <input type="hidden" name="o" value="Blog">
                    <input type="hidden" name="t" value="{$this->ctx->in['t']}">
                    <input type="search" name="q" placeholder="Search..." value="$q" style="width:200px">
                    <button type="submit" class="btn">Search</button>
                    $clear
                </form>
                <a href="?o=Blog&m=create$t" class="btn">+ Create New</a>
            </div>
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="border-bottom:2px solid var(--border)">
                        <th style="text-align:left;padding:0.5rem">Title</th>
                        <th style="text-align:left;padding:0.5rem">Created</th>
                        <th style="text-align:left;padding:0.5rem">Updated</th>
                        <th style="text-align:right;padding:0.5rem">Actions</th>
                    </tr>
                </thead>
                <tbody>
        HTML;

        foreach ($a['items'] as $item) {
            $title = htmlspecialchars($item['title']);
            $html .= <<<HTML
                <tr style="border-bottom:1px solid var(--border)">
                    <td style="padding:0.5rem"><a href="?o=Blog&m=read&id={$item['id']}$t">$title</a></td>
                    <td style="padding:0.5rem"><small>{$item['created']}</small></td>
                    <td style="padding:0.5rem"><small>{$item['updated']}</small></td>
                    <td style="padding:0.5rem;text-align:right">
                        <a href="?o=Blog&m=update&id={$item['id']}$t" title="Edit" class="icon">✏️</a>
                        <a href="?o=Blog&m=delete&id={$item['id']}$t" title="Delete" class="icon" onclick="return confirm('Delete this item?')">🗑️</a>
                    </td>
                </tr>
            HTML;
        }

        $html .= '</tbody></table>';

        // Pagination
        $p = $a['pagination'];
        if ($p['pages'] > 1) {
            $sq = $q ? "&q=$q" : '';
            $html .= '<div class="flex mt-2" style="justify-content:center;gap:0.5rem">';
            if ($p['page'] > 1)
                $html .= "<a href=\"?o=Blog&page=" . ($p['page'] - 1) . "$sq$t\" class=\"btn\">« Prev</a>";
            $html .= "<span style=\"padding:0.5rem\">Page {$p['page']} of {$p['pages']}</span>";
            if ($p['page'] < $p['pages'])
                $html .= "<a href=\"?o=Blog&page=" . ($p['page'] + 1) . "$sq$t\" class=\"btn\">Next »</a>";
            $html .= '</div>';
        }

        return $html . '</div>';
    }

    private function form(array $data = []): string {
        $id = $data['id'] ?? 0;
        $t = $this->t();
        $title = htmlspecialchars($data['title'] ?? '');
        $content = htmlspecialchars($data['content'] ?? '');
        $action = $id ? "?o=Blog&m=update&id=$id$t" : "?o=Blog&m=create$t";
        $heading = $id ? 'Edit Blog' : 'Create Blog';
        $btnText = $id ? 'Update' : 'Create';

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
                    <label for="content">Content</label>
                    <textarea id="content" name="content" rows="10" required>$content</textarea>
                </div>
                <div class="text-right">
                    <a href="?o=Blog$t" class="btn btn-muted">Cancel</a>
                    <button type="submit" class="btn">$btnText</button>
                </div>
            </form>
        </div>
        HTML;
    }
}
