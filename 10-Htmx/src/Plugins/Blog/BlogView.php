<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Htmx\Plugins\Blog;

use SPE\App\Util;
use SPE\Htmx\Core\Theme;

final class BlogView extends Theme
{
    private function t(): string
    {
        return '&t=' . $this->ctx->in['t'];
    }

    // Public blog index - 3x3 card grid
    public function list(): string
    {
        $a = $this->ctx->ary;
        $t = $this->t();

        $html = <<<HTML
        <div class="blog-header">
            <h1>Blog</h1>
            <p class="text-muted">Latest posts and updates</p>
        </div>
        <div class="blog-grid">
        HTML;

        foreach ($a['items'] as $post) {
            $title = htmlspecialchars($post['title']);
            $excerpt = htmlspecialchars($post['excerpt']);
            $author = htmlspecialchars($post['author']);
            $date = date('M j, Y', strtotime($post['created']));
            $image = $post['featured_image'] ?: 'https://picsum.photos/seed/' . $post['id'] . '/400/200';
            $url = "?o=Blog&m=read&id={$post['id']}$t";

            $html .= <<<HTML
            <article class="blog-card">
                <a href="$url" hx-get="$url" hx-target="#main" hx-push-url="true" class="blog-card-image">
                    <img src="$image" alt="$title" loading="lazy">
                </a>
                <div class="blog-card-content">
                    <h3><a href="$url" hx-get="$url" hx-target="#main" hx-push-url="true">$title</a></h3>
                    <p class="blog-card-excerpt">$excerpt</p>
                    <p class="blog-card-meta">
                        <span>$author</span> &bull; <span>$date</span>
                    </p>
                </div>
            </article>
            HTML;
        }

        $html .= '</div>';

        // Pagination with htmx
        $p = $a['pagination'];
        if ($p['pages'] > 1) {
            $html .= '<div class="blog-pagination">';
            if ($p['page'] > 1) {
                $prevUrl = "?o=Blog&page=" . ($p['page'] - 1) . $t;
                $html .= "<a href=\"$prevUrl\" hx-get=\"$prevUrl\" hx-target=\"#main\" hx-push-url=\"true\" class=\"btn\">« Newer</a>";
            }
            $html .= "<span>Page {$p['page']} of {$p['pages']}</span>";
            if ($p['page'] < $p['pages']) {
                $nextUrl = "?o=Blog&page=" . ($p['page'] + 1) . $t;
                $html .= "<a href=\"$nextUrl\" hx-get=\"$nextUrl\" hx-target=\"#main\" hx-push-url=\"true\" class=\"btn\">Older »</a>";
            }
            $html .= '</div>';
        }

        return $html;
    }

    // Single post view with prev/next navigation
    public function read(): string
    {
        $a = $this->ctx->ary;
        if (empty($a))
            return (
                '<div class="card"><p>Post not found.</p><a href="?o=Blog'
                . $this->t()
                . '" class="btn">« Back to Blog</a></div>'
            );

        $t = $this->t();
        $title = htmlspecialchars($a['title']);
        $content = Util::md($a['content'] ?? '');
        $author = htmlspecialchars($a['author']);
        $date = date('F j, Y', strtotime($a['created']));
        $image = $a['featured_image']
            ? "<img src=\"{$a['featured_image']}\" alt=\"$title\" class=\"blog-featured-image\">"
            : '';

        // Build category tags
        $categories = $a['categories'] ?? [];
        $catTags = '';
        if (!empty($categories)) {
            $catTags = '<div class="blog-categories">';
            foreach ($categories as $cat) {
                $name = htmlspecialchars($cat['name']);
                $catTags .= "<span class=\"tag\">$name</span>";
            }
            $catTags .= '</div>';
        }

        // Prev/Next navigation with htmx
        $prevNext = '<div class="blog-nav">';
        if ($a['prev']) {
            $prevTitle = htmlspecialchars($a['prev']['title']);
            $prevUrl = "?o=Blog&m=read&id={$a['prev']['id']}$t";
            $prevNext .= "<a href=\"$prevUrl\" hx-get=\"$prevUrl\" hx-target=\"#main\" hx-push-url=\"true\" class=\"blog-nav-prev\"><span>« Previous</span><strong>$prevTitle</strong></a>";
        } else {
            $prevNext .= '<span></span>';
        }
        if ($a['next']) {
            $nextTitle = htmlspecialchars($a['next']['title']);
            $nextUrl = "?o=Blog&m=read&id={$a['next']['id']}$t";
            $prevNext .= "<a href=\"$nextUrl\" hx-get=\"$nextUrl\" hx-target=\"#main\" hx-push-url=\"true\" class=\"blog-nav-next\"><span>Next »</span><strong>$nextTitle</strong></a>";
        }
        $prevNext .= '</div>';

        $backUrl = "?o=Blog$t";
        return <<<HTML
        <article class="blog-single">
            <header class="blog-single-header">
                <h1><a href="$backUrl" hx-get="$backUrl" hx-target="#main" hx-push-url="true" class="back-arrow">«</a> $title</h1>
                <p class="blog-single-meta">By $author &bull; $date</p>
                $catTags
            </header>
            $image
            <div class="prose">$content</div>
        </article>
        $prevNext
        HTML;
    }

    public function create(): string
    {
        return '';
    }

    public function update(): string
    {
        return '';
    }

    public function delete(): string
    {
        return '';
    }
}
