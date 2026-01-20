<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Users\Plugins\Blog;

use SPE\App\Util;
use SPE\Users\Core\Ctx;

final class BlogView
{
    private const array ICO = [
        '' => 'None',
        '🏠' => 'Home',
        '📋' => 'About',
        '✉️' => 'Contact',
        '📰' => 'Blog',
        '📝' => 'Post',
        '📄' => 'Page',
        '⭐' => 'Star',
        '🔥' => 'Fire',
        '💡' => 'Idea',
        '🎯' => 'Target',
        '🚀' => 'Launch',
        '💻' => 'Tech',
        '📸' => 'Photo',
        '🎨' => 'Art',
        '🎵' => 'Music',
        '📚' => 'Docs',
        '🔧' => 'Tools',
        '🌟' => 'Highlight',
        '💬' => 'Chat',
        '🔒' => 'Private',
        '❤️' => 'Love',
        '✅' => 'Done',
        '⚠️' => 'Alert',
        '🎉' => 'News',
        '👤' => 'User',
        '📅' => 'Event',
    ];

    public function __construct(
        private Ctx $ctx,
        private array $a,
    ) {}

    public function create(): string
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST' ? '' : $this->form();
    }

    public function update(): string
    {
        if (!$this->a)
            return '<div class="card mt-4"><p class=text-muted>Post not found.</p><a href="?o=Blog&m=list&edit" class=btn>« Back</a></div>';
        return $_SERVER['REQUEST_METHOD'] === 'POST' ? '' : $this->form($this->a);
    }

    public function delete(): string
    {
        return '';
    }

    public function read(): string
    {
        $a = $this->a;
        if (!$a)
            return '<div class="card mt-4"><p class=text-muted>Post not found.</p><a href="/blog" class=btn>« Back</a></div>';
        $ti = $a['icon'] ?? '' ? "{$a['icon']} {$a['title']}" : $a['title'];
        $admin = Util::is_adm()
            ? "<a href='?o=Blog&m=update&i={$a['id']}' class=btn>✏️ Edit</a>
            <a href='?o=Blog&m=delete&i={$a['id']}' class='btn btn-danger' onclick='return confirm(\"Delete?\")'>🗑️</a>" : '';
        return (
            "<div class='card mt-4'><h2>$ti</h2>
            <p class=text-muted><small>By {$a['author']} | {$a['created']} | {$a['updated']}</small></p>
            <div class='prose mt-2'>"
            . Util::md($a['content'])
            . "</div>
            <div class='flex mt-3 gap-sm justify-end'><a href='/blog' class=btn>« Back</a>$admin</div></div>"
        );
    }

    public function page(): string
    {
        $a = $this->a;
        if (!$a)
            return '<div class="card mt-4"><p class=text-muted>Page not found.</p></div>';
        $ti = $a['icon'] ?? '' ? "{$a['icon']} {$a['title']}" : $a['title'];
        $admin = Util::is_adm()
            ? "<div class='mt-2 text-right'><a href='?o=Blog&m=update&i={$a['id']}' class=btn>✏️ Edit</a></div>"
            : '';
        return (
            "<div class='card mt-4'><h2>$ti</h2><div class=prose>"
            . Util::md($a['content'] ?? '')
            . "</div>$admin</div>"
        );
    }

    public function list(): string
    {
        return $this->a['edit'] ?? false ? $this->le($this->a) : $this->lp($this->a);
    }

    private function lp(array $a): string
    {
        if (!$a['items'])
            return '<div class=card><p class=text-muted>No posts yet.</p></div>';
        $h = '<div>';
        foreach ($a['items'] as $i) {
            $ti = trim(($i['icon'] ?? '') . ' ' . htmlspecialchars($i['title']));
            $ex = Util::excerpt($i['content'], 200);
            $d = date('M j, Y', strtotime($i['updated']));
            $slug = htmlspecialchars($i['slug']);
            $h .= "<article class='card mb-2'><h2 class=m-0><a href='/$slug' class=no-underline>$ti</a></h2>
                <p class='text-muted m-0'><small>📅 $d · ✍️ {$i['author']}</small></p><p class=m-0>$ex</p>
                <div class=text-right><a href='/$slug' class=btn>Read More →</a></div></article>";
        }
        $h .= $this->pg($a['pagination'], '');
        $admin = Util::is_adm()
            ? "<div class='text-right mt-2'><a href='?o=Blog&edit' class=btn>✏️ Manage Posts</a></div>"
            : '';
        return $h . "$admin</div>";
    }

    private function le(array $a): string
    {
        $q = htmlspecialchars($_GET['q'] ?? '');
        $cl = $q ? "<a href='?o=Blog&edit' class=btn>✕</a>" : '';
        $h = "<div class='card mt-4'><div class='flex justify-between mb-2'>
            <form class='flex gap-sm'><input type=hidden name=o value=Blog><input type=hidden name=edit value=1>
            <input type=search name=q placeholder=Search... value='$q' class=w-200>
            <button type=submit class=btn>🔍</button>$cl</form>
            <div class='flex gap-sm'><a href='/blog' class=btn>📰 View Blog</a><a href='?o=Blog&m=create' class=btn>+ Create New</a></div></div>
            <table class=table><thead><tr class=tr-header>
            <th class=th>Title</th><th class=th>Type</th>
            <th class=th>Updated</th><th class=th-right>Actions</th></tr></thead><tbody>";
        foreach ($a['items'] as $i) {
            $ti = htmlspecialchars($i['title']);
            $tp = $i['type'] ?? 'post';
            $ic = match ($tp) {
                'page' => '📄',
                'doc' => '📚',
                default => '📝',
            };
            $slug = htmlspecialchars($i['slug']);
            $h .= "<tr class=tr><td class=td><a href='/$slug'>$ti</a></td>
                <td class=td><small>$ic $tp</small></td><td class=td><small>{$i['updated']}</small></td>
                <td class=td-right><a href='?o=Blog&m=update&i={$i['id']}'>✏️</a>
                <a href='?o=Blog&m=delete&i={$i['id']}' onclick='return confirm(\"Delete?\")'>🗑️</a></td></tr>";
        }
        return $h . '</tbody></table>' . $this->pg($a['pagination'], '&edit') . '</div>';
    }

    private function pg(array $p, string $x): string
    {
        if ($p['pages'] <= 1)
            return '';
        $q = htmlspecialchars($_GET['q'] ?? '');
        $sq = $q ? "&q=$q" : '';
        // Use clean URL for public view, query string for edit view
        $base = $x ? "?o=Blog$x" : '/blog?';
        $sep = $x ? '&' : '';
        $h = "<div class='flex mt-2 justify-center gap-sm'>";
        if ($p['page'] > 1)
            $h .= "<a href='{$base}{$sep}page=" . ($p['page'] - 1) . "$sq' class=btn>« Prev</a>";
        $h .= "<span class=td>Page {$p['page']} of {$p['pages']}</span>";
        if ($p['page'] < $p['pages'])
            $h .= "<a href='{$base}{$sep}page=" . ($p['page'] + 1) . "$sq' class=btn>Next »</a>";
        return $h . '</div>';
    }

    private function form(array $d = []): string
    {
        $id = $d['id'] ?? 0;
        $ti = htmlspecialchars($d['title'] ?? '');
        $sl = htmlspecialchars($d['slug'] ?? '');
        $co = htmlspecialchars($d['content'] ?? '');
        $ty = $d['type'] ?? 'post';
        $ic = $d['icon'] ?? '';
        $io = '';
        foreach (self::ICO as $e => $l) {
            $s = $ic === $e ? 'selected' : '';
            $dp = $e ? "$e $l" : $l;
            $io .= "<option value='$e' $s>$dp</option>";
        }
        $act = $id ? "?o=Blog&m=update&i=$id" : '?o=Blog&m=create';
        $hd = $id ? '✏️ Edit' : '+ Create';
        $bt = $id ? 'Update' : 'Create';
        $ps = $ty === 'post' ? 'selected' : '';
        $pgs = $ty === 'page' ? 'selected' : '';
        $csrf = Util::csrfField();
        return "<div class='card mt-4'><h2>$hd</h2><form method=post action='$act'>$csrf<input type=hidden name=i value=$id>
            <div class=flex><div class='form-group flex-2'><label for=title>Title</label>
            <input type=text id=title name=title value='$ti' required></div>
            <div class='form-group flex-1'><label for=slug>Slug</label><input type=text id=slug name=slug value='$sl' placeholder=auto></div>
            <div class=form-group><label for=icon>Icon</label><select id=icon name=icon>$io</select></div>
            <div class=form-group><label for=type>Type</label><select id=type name=type>
            <option value=post $ps>📝 Post</option><option value=page $pgs>📄 Page</option></select></div></div>
            <div class=form-group><label for=content>Content (Markdown)</label><textarea id=content name=content rows=12 required>$co</textarea></div>
            <div class='flex justify-between'><a href='?o=Blog&m=list&edit' class='btn btn-muted'>Cancel</a>
            <button type=submit class=btn>$bt</button></div></form></div>";
    }
}
