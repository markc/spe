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
        <div class="news-form">
            <h2>' . $heading . '</h2>
            <form method="post" action="' . $action . '">
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" value="' . $title . '" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="content">Content:</label>
                    <textarea id="content" name="content" required class="form-control" rows="10">' . $content . '</textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">' . ($id ? 'Update' : 'Create') . '</button>
                    <a href="?o=News" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>';
    }

    public function create(): string
    {
        return $this->css2() . $this->modalForm();
    }

    public function update(): string
    {
        return $this->css2() . $this->modalForm($this->ctx->ary);
    }

    public function css2(): string
    {
        Util::elog(__METHOD__);

        return '
            <style>
                .actions {
                    max-width: 800px;
                    margin: 2em auto;
                    padding: 1em;
                    text-align: right;
                }
                .news-form {
                    max-width: 800px;
                    margin: 2em auto;
                    padding: 2em;
                    background: #fff;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .news-form h2 {
                    margin-bottom: 1em;
                    color: #333;
                }
                .form-group {
                    margin-bottom: 1.5em;
                }
                .form-group label {
                    display: block;
                    margin-bottom: 0.5em;
                    color: #555;
                }
                .form-control {
                    width: 100%;
                    padding: 0.5em;
                    border: 1px solid #ddd;
                    border-radius: 3px;
                    font-size: 1em;
                }
                .form-control:focus {
                    outline: none;
                    border-color: #0066cc;
                    box-shadow: 0 0 3px rgba(0,102,204,0.2);
                }
                .form-actions {
                    margin-top: 2em;
                }
                .btn {
                    display: inline-block;
                    padding: 0.5em 1em;
                    border: none;
                    border-radius: 3px;
                    font-size: 1em;
                    cursor: pointer;
                    text-decoration: none;
                }
                .btn-primary {
                    background: #0066cc;
                    color: white;
                    margin-right: 1em;
                }
                .btn-secondary {
                    background: #666;
                    color: white;
                }
                .btn:hover {
                    opacity: 0.9;
                }
                .news-post {
                    max-width: 800px;
                    margin: 2em auto;
                    padding: 1em;
                    background: #fff;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .news-post header {
                    margin-bottom: 1.5em;
                }
                .news-post h1 {
                    color: #333;
                    margin: 0 0 0.5em 0;
                }
                .news-post h1 a {
                    color: #333;
                    text-decoration: none;
                }
                .news-post h1 a:hover {
                    color: #0066cc;
                }
                .news-post .meta {
                    color: #666;
                    font-size: 0.9em;
                }
                .news-post .meta span {
                    margin-right: 1em;
                }
                .news-post .content {
                    line-height: 1.6;
                    margin: 1.5em 0;
                }
                .news-post footer {
                    margin-top: 2em;
                    padding-top: 1em;
                    border-top: 1px solid #eee;
                    color: #999;
                }
                .pagination-container {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin: 2em 0;
                }
                .post-count {
                    color: #666;
                }
                .pagination {
                    margin-left: auto;
                }
                .pagination .page-link {
                    display: inline-block;
                    padding: 0.5em 1em;
                    margin: 0 0.2em;
                    border: 1px solid #ddd;
                    color: #0066cc;
                    text-decoration: none;
                    border-radius: 3px;
                }
                .pagination .current-page {
                    display: inline-block;
                    padding: 0.5em 1em;
                    margin: 0 0.2em;
                    border: 1px solid #0066cc;
                    background: #0066cc;
                    color: white;
                    border-radius: 3px;
                }
                .pagination .page-link:hover {
                    background: #f5f5f5;
                }
            </style>';
    }

    public function list(): string
    {
        Util::elog(__METHOD__);

        extract($this->ctx->ary, EXTR_SKIP);

        $html = '<div class="news-list">' . $this->css2();

        // Add Create New button
        $html .= '
        <div class="actions">
            <a href="?o=News&m=create" class="btn btn-primary">Create New Article</a>
        </div>';

        // Display news items
        foreach ($items as $item)
        {
            $html .= '
            <article class="news-post">
                <header>
                    <h1><a href="?o=News&m=read&i=' . $item['id'] . '">' . htmlspecialchars($item['title']) . '</a></h1>
                    <div class="meta">
                        <span class="date">Published: ' . $item['created'] . '</span>
                        <span class="updated">Last updated: ' . $item['updated'] . '</span>
                    </div>
                </header>
                <div class="content">
                    ' . substr(strip_tags($item['content']), 0, 200) . '... 
                    <a href="?o=News&m=read&i=' . $item['id'] . '">Read more</a>
                </div>
            </article>';
        }

        // Pagination container with post count and controls
        $html .= '<div class="pagination-container">';

        // Post count on LHS with start/end IDs
        $startId = (($pagination['p'] - 1) * count($items)) + 1;
        $endId = $startId + count($items) - 1;
        $html .= '<div class="post-count">Showing posts ' . $startId . '-' . $endId . ' of ' . $pagination['total'] . ' posts</div>';

        // Pagination controls on RHS
        if ($pagination['pages'] > 1)
        {
            $html .= '<div class="pagination">';

            // Previous page
            if ($pagination['p'] > 1)
            {
                $html .= '<a href="?o=News&p=' . ($pagination['p'] - 1) . '" class="page-link">&laquo; Previous</a> ';
            }

            // Page numbers
            for ($i = 1; $i <= $pagination['pages']; $i++)
            {
                if ($i == $pagination['p'])
                {
                    $html .= '<span class="current-page">' . $i . '</span> ';
                }
                else
                {
                    $html .= '<a href="?o=News&p=' . $i . '" class="page-link">' . $i . '</a> ';
                }
            }

            // Next page
            if ($pagination['p'] < $pagination['pages'])
            {
                $html .= '<a href="?o=News&p=' . ($pagination['p'] + 1) . '" class="page-link">Next &raquo;</a>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    public function read(): string
    {
        Util::elog(__METHOD__ . ' ' . var_export($this->ctx->out['foot'], true));

        extract($this->ctx->ary, EXTR_SKIP);
        return '
        <article class="news-post">
            <header>
                <h1>' . $title . '</h1>
                <div class="meta">
                    <span class="author">By ' . $author . '</span>
                    <span class="date">Published: ' . $created . '</span>
                    <span class="updated">Last updated: ' . $updated . '</span>
                </div>
            </header> ' . $this->css2() . '
            <div class="content">
                ' . Util::nlbr($content) . '
            </div>
            <footer style="display: flex; justify-content: space-between; align-items: center;">
                <a href="?o=News&m=list" class="btn btn-primary">Return to List</a>
                <small class="post-id">Post ID: ' . $id . '</small>
                <a href="?o=News&m=update&i=' . $id . '" class="btn btn-primary">Edit Post</a>
            </footer>
        </article>';
    }
}
