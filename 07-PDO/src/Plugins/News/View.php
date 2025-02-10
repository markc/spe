<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250208
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\PDO\Plugins\News;

use SPE\PDO\Themes\Base;
use SPE\PDO\Core\Util;

final class View extends Base
{
    public function foot(): string
    {
        return '<footer class="text-center">THE ALTERNATE NEWS FOOTER EXAMPLE</footer>';
    }

    private function modalForm(array $data = []): string
    {
        $id = $data['id'] ?? '';
        $title = htmlspecialchars($data['title'] ?? '');
        $content = htmlspecialchars($data['content'] ?? '');
        $action = $id ? "?o=News&m=update&i=$id" : "?o=News&m=create";
        $heading = $id ? 'Edit News Item' : 'Create News Item';

        return '
        <div class="container py-4">
            <div class="card shadow-sm mx-auto" style="max-width: 800px;">
                <div class="card-body">
                    <h2 class="card-title mb-4">' . $heading . '</h2>
                    <form method="post" action="' . $action . '">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title:</label>
                            <input type="text" id="title" name="title" value="' . $title . '" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">Content:</label>
                            <textarea id="content" name="content" required class="form-control" rows="10">' . $content . '</textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">' . ($id ? 'Update' : 'Create') . '</button>
                            <a href="?o=News" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>';
    }

    public function create(): string
    {
        return $this->modalForm();
    }

    public function update(): string
    {
        return $this->modalForm($this->ctx->ary);
    }

    public function list(): string
    {
        Util::elog(__METHOD__);

        extract($this->ctx->ary, EXTR_SKIP);

        $html = '<div class="container py-4">';

        // Add Create New button and search bar
        $html .= '
        <div class="d-flex justify-content-between align-items-center mb-4">
            <form class="d-flex" style="width: 300px;" method="get">
                <input type="hidden" name="o" value="News">
                <div class="input-group">
                    <input type="search" name="q" class="form-control" placeholder="Search articles..." 
                        value="' . htmlspecialchars($_GET['q'] ?? '') . '">
                    <button class="btn btn-outline-secondary" type="submit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                        </svg>
                    </button>' .
            (isset($_GET['q']) && $_GET['q'] !== '' ? '
                    <a href="?o=News" class="btn btn-outline-secondary" title="Clear search">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </a>' : '') . '
                </div>
            </form>
            <a href="?o=News&m=create" class="btn btn-primary">Create New Article</a>
        </div>';

        // Display news items in a 3-column grid
        $html .= '<div class="row row-cols-1 row-cols-md-3 g-4">';
        foreach ($items as $item)
        {
            $html .= '
            <div class="col">
                <article class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h2 class="h5 card-title">
                            <a href="?o=News&m=read&i=' . $item['id'] . '" class="text-decoration-none">' .
                htmlspecialchars($item['title']) . '</a>
                        </h2>
                        <div class="text-muted small mb-3 d-flex flex-column">
                            <span>Published: ' . $item['created'] . '</span>
                            <span>Last updated: ' . $item['updated'] . '</span>
                        </div>
                        <div class="card-text mb-3 flex-grow-1">
                            ' . substr(strip_tags($item['content']), 0, 150) . '...
                        </div>
                        <a href="?o=News&m=read&i=' . $item['id'] . '" class="btn btn-primary btn-sm">Read more</a>
                    </div>
                </article>
            </div>';
        }
        $html .= '</div>';

        // Pagination container
        $html .= '<div class="d-flex justify-content-between align-items-center mt-4">';

        // Post count
        $startId = (($pagination['p'] - 1) * count($items)) + 1;
        $endId = $startId + count($items) - 1;
        $html .= '<div class="text-muted">Showing posts ' . $startId . '-' . $endId . ' of ' . $pagination['total'] . ' posts</div>';

        // Pagination controls
        if ($pagination['pages'] > 1)
        {
            $html .= '<nav aria-label="Page navigation"><ul class="pagination mb-0">';

            // Get search query if exists
            $searchQuery = isset($_GET['q']) ? '&q=' . htmlspecialchars($_GET['q']) : '';

            // Previous page
            if ($pagination['p'] > 1)
            {
                $html .= '<li class="page-item">
                    <a class="page-link" href="?o=News&p=' . ($pagination['p'] - 1) . $searchQuery . '">&laquo; Previous</a>
                </li>';
            }

            // Page numbers
            for ($i = 1; $i <= $pagination['pages']; $i++)
            {
                if ($i == $pagination['p'])
                {
                    $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                }
                else
                {
                    $html .= '<li class="page-item"><a class="page-link" href="?o=News&p=' . $i . $searchQuery . '">' . $i . '</a></li>';
                }
            }

            // Next page
            if ($pagination['p'] < $pagination['pages'])
            {
                $html .= '<li class="page-item">
                    <a class="page-link" href="?o=News&p=' . ($pagination['p'] + 1) . $searchQuery . '">Next &raquo;</a>
                </li>';
            }

            $html .= '</ul></nav>';
        }

        $html .= '</div></div>';
        return $html;
    }

    public function read(): string
    {
        Util::elog(__METHOD__ . ' ' . var_export($this->ctx->out['foot'], true));

        extract($this->ctx->ary, EXTR_SKIP);
        return '
        <div class="container py-4">
            <article class="card shadow-sm mx-auto" style="max-width: 800px;">
                <div class="card-body">
                    <h1 class="card-title h2 mb-3">' . $title . '</h1>
                    <div class="text-muted small mb-4">
                        <span class="me-3">By ' . $author . '</span>
                        <span class="me-3">Published: ' . $created . '</span>
                        <span>Last updated: ' . $updated . '</span>
                    </div>
                    <div class="card-text mb-4">
                        ' . Util::nlbr($content) . '
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-4 border-top">
                        <a href="?o=News&m=list" class="btn btn-primary">Return to List</a>
                        <small class="text-muted">Post ID: ' . $id . '</small>
                        <a href="?o=News&m=update&i=' . $id . '" class="btn btn-primary">Edit Post</a>
                    </div>
                </div>
            </article>
        </div>';
    }
}
