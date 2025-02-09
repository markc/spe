<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250208
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\PDO\Plugins\News;

use SPE\PDO\Themes\Base;
use SPE\PDO\Core\Util;

final class View extends Base
{
    public function read(): string
    {
        Util::elog(__METHOD__ . ' ' . var_export($this->ctx->ary, true));
        extract($this->ctx->ary);
        return '
        <article class="news-post">
            <header>
                <h1>' . $title . '</h1>
                <div class="meta">
                    <span class="author">By ' . $author . '</span>
                    <span class="date">Published: ' . $created . '</span>
                    <span class="updated">Last updated: ' . $updated . '</span>
                </div>
            </header>
            <div class="content">
                ' . $content . '
            </div>
            <footer>
                <small class="post-id">Post ID: ' . $id . '</small>
            </footer>
            <style>
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
            </style>
        </article>';
    }
}
