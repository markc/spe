<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Blog;

use SPE\App\Util;
use SPE\Blog\Core\Ctx;

final class BlogView
{
    public function __construct(
        private Ctx $ctx,
        private array $a,
    ) {}

    private function t(): string
    {
        return '&t=' . $this->ctx->in['t'];
    }

    // Public blog index - 3x3 card grid
    public function list(): string
    {
        $a = $this->a;
        $t = $this->t();

        $html = '<div class="blog-list">';

        foreach ($a['items'] as $post) {
            $title = htmlspecialchars($post['title']);
            $excerpt = htmlspecialchars($post['excerpt']);
            $author = htmlspecialchars($post['author']);
            $date = date('M j, Y', strtotime($post['created']));
            $image = $post['featured_image'] ?: 'https://picsum.photos/seed/' . $post['id'] . '/400/200';
            $url = "?o=Blog&m=read&id={$post['id']}$t";

            $html .= <<<HTML
            <article class="card-hover blog-item">
                <a href="$url" class="blog-item-image">
                    <img src="$image" alt="$title" loading="lazy">
                </a>
                <div class="blog-item-content">
                    <h3><a href="$url">$title</a></h3>
                    <p class="blog-item-meta">$author &bull; $date</p>
                    <p class="blog-item-excerpt">$excerpt</p>
                </div>
            </article>
            HTML;
        }

        $html .= '</div>';

        // Pagination (similar style to single page prev/next)
        $p = $a['pagination'];
        if ($p['pages'] > 1) {
            $html .= '<div class="blog-nav">';
            if ($p['page'] > 1) {
                $html .= "<a href=\"?o=Blog&page=" . ($p['page'] - 1) . "$t\" class=\"blog-nav-prev\">« Newer Posts</a>";
            } else {
                $html .= '<span></span>';
            }
            $html .= "<span class=\"blog-nav-page\">Page {$p['page']} of {$p['pages']}</span>";
            if ($p['page'] < $p['pages']) {
                $html .= "<a href=\"?o=Blog&page=" . ($p['page'] + 1) . "$t\" class=\"blog-nav-next\">Older Posts »</a>";
            }
            $html .= '</div>';
        }

        return $html;
    }

    // Single post view with prev/next navigation
    public function read(): string
    {
        $a = $this->a;
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

        // Prev/Next navigation
        $prevNext = '<div class="blog-nav">';
        if ($a['prev']) {
            $prevTitle = htmlspecialchars($a['prev']['title']);
            $prevNext .= "<a href=\"?o=Blog&m=read&id={$a['prev']['id']}$t\" class=\"blog-nav-prev\">« $prevTitle</a>";
        } else {
            $prevNext .= '<span></span>';
        }
        if ($a['next']) {
            $nextTitle = htmlspecialchars($a['next']['title']);
            $prevNext .= "<a href=\"?o=Blog&m=read&id={$a['next']['id']}$t\" class=\"blog-nav-next\">$nextTitle »</a>";
        }
        $prevNext .= '</div>';

        return <<<HTML
        $prevNext
        <article class="blog-single">
            <header class="blog-single-header">
                <h1><a href="?o=Blog$t" class="back-arrow">«</a> $title</h1>
                <p class="blog-single-meta">By $author &bull; $date</p>
                $catTags
            </header>
            <div class="prose">$image$content</div>
        </article>
        $prevNext
        HTML;
    }

    // Static page view
    public function page(): string
    {
        $a = $this->a;
        if (empty($a))
            return '<div class="card"><p>Page not found.</p></div>';

        $title = htmlspecialchars($a['title']);
        $content = Util::md($a['content'] ?? '');
        $icon = $a['icon'] ?? '';
        $heading = $icon ? "$icon $title" : $title;

        return <<<HTML
        <div class="card">
            <h2>$heading</h2>
            <div class="prose">$content</div>
        </div>
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
