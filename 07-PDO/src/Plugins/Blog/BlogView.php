<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Plugins\Blog;

use SPE\App\Util;
use SPE\PDO\Core\Ctx;

final class BlogView
{
    private const array ICO = [
        '' => 'None',
        'home' => 'Home',
        'book-open' => 'About',
        'mail' => 'Contact',
        'newspaper' => 'Blog',
        'edit' => 'Post',
        'file-text' => 'Page',
        'star' => 'Star',
        'flame' => 'Fire',
        'lightbulb' => 'Idea',
        'target' => 'Target',
        'rocket' => 'Launch',
        'laptop' => 'Tech',
        'camera' => 'Photo',
        'palette' => 'Art',
        'music' => 'Music',
        'library' => 'Docs',
        'wrench' => 'Tools',
        'sparkles' => 'Highlight',
        'message-circle' => 'Chat',
        'lock' => 'Private',
        'heart' => 'Love',
        'check-circle' => 'Done',
        'alert-triangle' => 'Alert',
        'party-popper' => 'News',
        'user' => 'User',
        'calendar' => 'Event',
    ];

    public function __construct(private Ctx $ctx, private array $a) {}

    private function icon(string $name): string
    {
        return $name ? "<i data-lucide=\"{$name}\"></i> " : '';
    }

    public function create(): string
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST' ? '' : $this->form();
    }

    public function update(): string
    {
        if (!$this->a)
            return '<div class="card mt-4"><p class=text-muted>Post not found.</p><a href="?o=Blog&m=list&edit" class=btn><i data-lucide="arrow-left"></i> Back</a></div>';
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
            return '<div class="card mt-4"><p class=text-muted>Post not found.</p><a href="?o=Blog" class=btn><i data-lucide="arrow-left"></i> Back</a></div>';
        $ico = $this->icon($a['icon'] ?? '');
        return <<<HTML
<div class='card mt-4'>
    <h2>{$ico}{$a['title']}</h2>
    <p class=text-muted><small><i data-lucide="user" class="inline-icon"></i> {$a['author']} | <i data-lucide="calendar" class="inline-icon"></i> {$a['created']}</small></p>
    <div class='prose mt-2'>{$this->md($a['content'])}</div>
    <div class='flex mt-3 gap-sm justify-end'>
        <a href='?o=Blog' class=btn><i data-lucide="arrow-left"></i> Back</a>
        <a href='?o=Blog&m=update&id={$a['id']}' class=btn><i data-lucide="edit"></i> Edit</a>
        <a href='?o=Blog&m=delete&id={$a['id']}' class='btn btn-danger' onclick='return confirm("Delete?")'><i data-lucide="trash-2"></i></a>
    </div>
</div>
HTML;
    }

    public function page(): string
    {
        $a = $this->a;
        if (!$a)
            return '<div class="card mt-4"><p class=text-muted>Page not found.</p></div>';
        $ico = $this->icon($a['icon'] ?? '');
        return <<<HTML
<div class='card mt-4'>
    <h2>{$ico}{$a['title']}</h2>
    <div class=prose>{$this->md($a['content'] ?? '')}</div>
    <div class='mt-2 text-right'><a href='?o=Blog&m=update&id={$a['id']}' class=btn><i data-lucide="edit"></i> Edit</a></div>
</div>
HTML;
    }

    private function md(string $content): string
    {
        return Util::md($content);
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
            $ico = $this->icon($i['icon'] ?? '');
            $ti = $ico . htmlspecialchars($i['title']);
            $ex = Util::excerpt($i['content'], 200);
            $d = date('M j, Y', strtotime($i['updated']));
            $h .= <<<HTML
<article class='card mb-2'>
    <h2 class=m-0><a href='?o=Blog&m=read&id={$i['id']}' class=no-underline>{$ti}</a></h2>
    <p class='text-muted m-0'><small><i data-lucide="calendar" class="inline-icon"></i> {$d} · <i data-lucide="user" class="inline-icon"></i> {$i['author']}</small></p>
    <p class=m-0>{$ex}</p>
    <div class=text-right><a href='?o=Blog&m=read&id={$i['id']}' class=btn>Read More <i data-lucide="arrow-right"></i></a></div>
</article>
HTML;
        }
        $h .= $this->pg($a['pagination'], '');
        return $h . "<div class='text-right mt-2'><a href='?o=Blog&edit' class=btn><i data-lucide=\"settings\"></i> Manage Posts</a></div></div>";
    }

    private function le(array $a): string
    {
        $q = htmlspecialchars($_GET['q'] ?? '');
        $cl = $q ? "<a href='?o=Blog&edit' class=btn><i data-lucide=\"x\"></i></a>" : '';
        $h = <<<HTML
<div class='card mt-4'>
    <div class='flex justify-between mb-2'>
        <form class='flex gap-sm'>
            <input type=hidden name=o value=Blog>
            <input type=hidden name=edit value=1>
            <input type=search name=q placeholder=Search... value='{$q}' class=w-200>
            <button type=submit class=btn><i data-lucide="search"></i></button>{$cl}
        </form>
        <div class='flex gap-sm'>
            <a href='?o=Blog' class=btn><i data-lucide="newspaper"></i> View Blog</a>
            <a href='?o=Blog&m=create' class=btn><i data-lucide="plus"></i> Create New</a>
        </div>
    </div>
    <table class=table>
        <thead><tr class=tr-header>
            <th class=th>Title</th>
            <th class=th>Type</th>
            <th class=th>Updated</th>
            <th class=th-right>Actions</th>
        </tr></thead>
        <tbody>
HTML;
        foreach ($a['items'] as $i) {
            $ti = htmlspecialchars($i['title']);
            $tp = $i['type'] ?? 'post';
            $ico = match ($tp) { 'page' => 'file-text', 'doc' => 'library', default => 'edit' };
            $h .= <<<HTML
<tr class=tr>
    <td class=td><a href='?o=Blog&m=read&id={$i['id']}'>{$ti}</a></td>
    <td class=td><small><i data-lucide="{$ico}"></i> {$tp}</small></td>
    <td class=td><small>{$i['updated']}</small></td>
    <td class=td-right>
        <a href='?o=Blog&m=update&id={$i['id']}'><i data-lucide="edit"></i></a>
        <a href='?o=Blog&m=delete&id={$i['id']}' onclick='return confirm("Delete?")'><i data-lucide="trash-2"></i></a>
    </td>
</tr>
HTML;
        }
        return $h . '</tbody></table>' . $this->pg($a['pagination'], '&edit') . '</div>';
    }

    private function pg(array $p, string $x): string
    {
        if ($p['pages'] <= 1) return '';
        $q = htmlspecialchars($_GET['q'] ?? '');
        $sq = $q ? "&q=$q" : '';
        $h = "<div class='flex mt-2 justify-center gap-sm'>";
        if ($p['page'] > 1)
            $h .= "<a href='?o=Blog&page=" . ($p['page'] - 1) . "$x$sq' class=btn><i data-lucide=\"chevron-left\"></i> Prev</a>";
        $h .= "<span class=td>Page {$p['page']} of {$p['pages']}</span>";
        if ($p['page'] < $p['pages'])
            $h .= "<a href='?o=Blog&page=" . ($p['page'] + 1) . "$x$sq' class=btn>Next <i data-lucide=\"chevron-right\"></i></a>";
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
            $io .= "<option value='{$e}' {$s}>{$l}</option>";
        }
        $act = $id ? "?o=Blog&m=update&id=$id" : '?o=Blog&m=create';
        $hd = $id ? '<i data-lucide="edit"></i> Edit' : '<i data-lucide="plus"></i> Create';
        $bt = $id ? 'Update' : 'Create';
        $ps = $ty === 'post' ? 'selected' : '';
        $pgs = $ty === 'page' ? 'selected' : '';
        return <<<HTML
<div class='card mt-4'>
    <h2>{$hd}</h2>
    <form method=post action='{$act}'>
        <input type=hidden name=id value={$id}>
        <div class=flex>
            <div class='form-group flex-2'>
                <label for=title>Title</label>
                <input type=text id=title name=title value='{$ti}' required>
            </div>
            <div class='form-group flex-1'>
                <label for=slug>Slug</label>
                <input type=text id=slug name=slug value='{$sl}' placeholder=auto>
            </div>
            <div class=form-group>
                <label for=icon>Icon</label>
                <select id=icon name=icon>{$io}</select>
            </div>
            <div class=form-group>
                <label for=type>Type</label>
                <select id=type name=type>
                    <option value=post {$ps}><i data-lucide="edit"></i> Post</option>
                    <option value=page {$pgs}><i data-lucide="file-text"></i> Page</option>
                </select>
            </div>
        </div>
        <div class=form-group>
            <label for=content>Content (Markdown)</label>
            <textarea id=content name=content rows=12 required>{$co}</textarea>
        </div>
        <div class='flex justify-between'>
            <a href='?o=Blog&m=list&edit' class='btn btn-muted'>Cancel</a>
            <button type=submit class=btn>{$bt}</button>
        </div>
    </form>
</div>
HTML;
    }
}
