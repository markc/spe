<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Home;

use SPE\Blog\Core\View;

/**
 * The one "marketing" page in the tutorial: the finished application's front door, built
 * from the DCS site.css components (hero, section header, service cards, CTA buttons) that
 * every other chapter leaves unused. Same Model/View contract as any other plugin — the
 * model hands over data, this renders it.
 */
final class HomeView extends View
{
    #[\Override]
    public function list(): string
    {
        $title = $this->e($this->data['title']);
        $body = $this->e($this->data['body']);

        return <<<HTML
<section class="hero hero-bg">
    <div class="hero-content">
        <div class="hero-badge"><i data-lucide="layers" style="width:16px;height:16px"></i><span>Chapter 09 — the destination</span></div>
        <h1><span class="shimmer-text">Simple</span><br>PHP Engine</h1>
        <p>{$body}</p>
        <div class="cta-group">
            <a href="?o=Blog" class="cta-btn primary"><i data-lucide="newspaper"></i> Read the Blog</a>
            <a href="?o=Docs" class="cta-btn secondary"><i data-lucide="book-open"></i> Browse the Docs</a>
        </div>
    </div>
</section>

<section class="content-section">
    <div class="section-header">
        <span class="section-tag">What you are looking at</span>
        <h2 class="section-title">{$title}</h2>
        <p class="section-subtitle">Nine chapters, one idea each, and this is what they add up to.</p>
    </div>
    <div class="services-grid">
        <div class="service-card">
            <span class="service-icon"><i data-lucide="database"></i></span>
            <h3>One table, several kinds</h3>
            <ul>
                <li><code>Type</code> enum: post or doc</li>
                <li><code>Content</code> holds the whole CRUDL</li>
                <li>Blog and Docs are two lines each</li>
                <li>SQLite via PDO, created on first run</li>
            </ul>
        </div>
        <div class="service-card">
            <span class="service-icon"><i data-lucide="file-text"></i></span>
            <h3>Markdown bodies</h3>
            <ul>
                <li>Safe renderer in <code>Md</code></li>
                <li>Tags and pagination</li>
                <li>Prev/next and oldest/newest</li>
                <li>Docs are this tutorial, served by itself</li>
            </ul>
        </div>
        <div class="service-card">
            <span class="service-icon"><i data-lucide="shield-check"></i></span>
            <h3>Identity</h3>
            <ul>
                <li>Password hashing, sessions, CSRF</li>
                <li>Roles: reads public, writes admin</li>
                <li><code>admin@example.com</code> / <code>admin</code></li>
                <li>Guarded once, in <code>Content::guard()</code></li>
            </ul>
        </div>
        <div class="service-card">
            <span class="service-icon"><i data-lucide="panels-left-right"></i></span>
            <h3>No framework</h3>
            <ul>
                <li>PHP 8.5, Composer autoload, PDO</li>
                <li>Model returns data, View returns HTML</li>
                <li>Escape at output, validate once in <code>Ctx</code></li>
                <li>Shell + styling: <a href="https://dcs.spa">DCS</a>, vendored</li>
            </ul>
        </div>
    </div>
</section>
HTML;
    }
}
