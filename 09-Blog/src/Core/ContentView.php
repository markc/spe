<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

/** The view shared by Blog and Docs. $post->html is trusted: Md produced it. */
abstract class ContentView extends View
{
    private function o(): string
    {
        return $this->ctx->in['o'];
    }

    private function isAdmin(): bool
    {
        return $this->ctx->role()->can(Role::Admin);
    }

    #[\Override]
    public function list(): string
    {
        $o = $this->o();
        $cards = array_map(fn(Post $p) => <<<HTML
<article class="list-item">
    <h3 class="list-item-title"><a href="?o=$o&m=read&i={$this->e($p->id)}">{$this->e($p->title)}</a></h3>
    <p class="list-item-meta text-muted">{$this->e($p->created)}</p>
    <p class="list-item-excerpt">{$this->e($p->excerpt)}</p>
    <p>{$this->tagLinks($p->tags)}</p>
</article>
HTML, $this->data['items']);
        $body = implode('', $cards) ?: '<p>Nothing here yet.</p>';

        $new = $this->isAdmin() ? '<a class="btn" href="?o=' . $o . '&m=create"><i data-lucide="plus"></i> New</a>' : '';
        $filter = $this->data['tag'] !== ''
            ? '<p class="text-muted">Tagged <span class="tag">' . $this->e($this->data['tag']) . '</span> · <a href="?o=' . $o . '">clear</a></p>'
            : '';

        return <<<HTML
<div class="card">
    <div class="list-header"><h2>{$this->e($this->data['title'])}</h2>$new</div>
    $filter
    <div class="list-items">$body</div>
    {$this->pager()}
    {$this->allTags()}
</div>
HTML;
    }

    #[\Override]
    public function read(): string
    {
        if (!isset($this->data['post'])) {
            return $this->card();
        }
        $o = $this->o();
        $post = $this->data['post'];
        $edit = $this->isAdmin() ? '<a class="btn btn-sm" href="?o=' . $o . '&m=update&i=' . $this->e($post->id) . '"><i data-lucide="pencil"></i> Edit</a>' : '';

        return <<<HTML
<article class="card prose">
    <h1>{$this->e($post->title)}</h1>
    <p class="text-muted">{$this->e($post->created)} · {$this->tagLinks($post->tags)}</p>
    {$post->html}
</article>
<nav class="pagination">
    {$this->link('&laquo; Newest', $this->data['last'])}
    {$this->link('&lsaquo; Newer', $this->data['next'])}
    <a class="btn" href="?o=$o">All</a>
    {$this->link('Older &rsaquo;', $this->data['prev'])}
    {$this->link('Oldest &raquo;', $this->data['first'])}
    $edit
</nav>
HTML;
    }

    #[\Override]
    public function create(): string { return $this->form(); }

    #[\Override]
    public function update(): string { return isset($this->data['form']) ? $this->form() : $this->card(); }

    private function form(): string
    {
        $f = $this->data['form'];
        return <<<HTML
<div class="card">
    <h2>{$this->e($this->data['title'])}</h2>
    <form method="post" action="{$this->e($this->data['action'])}">
        {$this->csrf()}
        <div class="form-group"><label for="title">Title</label><input type="text" id="title" name="title" value="{$this->e($f['title'])}" required></div>
        <div class="form-group"><label for="tags">Tags <small class="text-muted">(comma separated)</small></label><input type="text" id="tags" name="tags" value="{$this->e($f['tags'])}"></div>
        <div class="form-group"><label for="body">Body <small class="text-muted">(Markdown)</small></label><textarea id="body" name="body" rows="12">{$this->e($f['body'])}</textarea></div>
        <div class="btn-group"><button type="submit" class="btn">Save</button><a class="btn btn-ghost" href="?o={$this->o()}">Cancel</a></div>
    </form>
</div>
HTML;
    }

    /** @param list<array{id:int,name:string,slug:string}> $tags */
    private function tagLinks(array $tags): string
    {
        $o = $this->o();
        return implode(' ', array_map(fn(array $t) => sprintf(
            '<a class="tag" href="?o=%s&tag=%s">%s</a>', $o, $this->e($t['slug']), $this->e($t['name'])
        ), $tags));
    }

    private function allTags(): string
    {
        if (!$this->data['allTags']) {
            return '';
        }
        $o = $this->o();
        $links = implode(' ', array_map(fn(array $t) => sprintf(
            '<a class="tag" href="?o=%s&tag=%s">%s</a>', $o, $this->e($t['slug']), $this->e($t['name'])
        ), $this->data['allTags']));
        return "<div class=\"mt-4\"><small class=\"text-muted\">Tags:</small> $links</div>";
    }

    private function pager(): string
    {
        [$page, $pages, $o, $tag] = [$this->data['page'], $this->data['pages'], $this->o(), $this->data['tag']];
        if ($pages < 2) {
            return '';
        }
        $q = $tag !== '' ? "&tag=$tag" : '';
        $prev = $page > 1 ? '<a class="btn btn-sm" href="?o=' . $o . '&page=' . ($page - 1) . $q . '">&lsaquo; Newer</a>' : '';
        $next = $page < $pages ? '<a class="btn btn-sm" href="?o=' . $o . '&page=' . ($page + 1) . $q . '">Older &rsaquo;</a>' : '';
        return "<nav class=\"pagination\">$prev<span class=\"text-muted\">Page $page of $pages</span>$next</nav>";
    }

    private function link(string $label, ?int $id): string
    {
        return $id === null ? '' : '<a class="btn btn-sm" href="?o=' . $this->o() . '&m=read&i=' . $id . '">' . $label . '</a>';
    }
}
