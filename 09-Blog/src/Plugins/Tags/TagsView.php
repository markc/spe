<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Tags;

use SPE\Blog\Core\View;

final class TagsView extends View
{
    #[\Override]
    public function list(): string
    {
        $rows = array_map(fn(array $t) => <<<HTML
<tr>
    <td><a href="?o=Blog&tag={$this->e($t['slug'])}">{$this->e($t['name'])}</a></td>
    <td><code>{$this->e($t['slug'])}</code></td>
    <td>{$this->e($t['posts'])}</td>
    <td class="text-right">
        <a class="btn btn-sm" href="?o=Tags&m=update&i={$this->e($t['id'])}"><i data-lucide="pencil"></i></a>
        <form method="post" action="?o=Tags&m=delete&i={$this->e($t['id'])}" style="display:inline" onsubmit="return confirm('Delete this tag?')">{$this->csrf()}<button type="submit" class="btn btn-sm btn-danger"><i data-lucide="trash-2"></i></button></form>
    </td>
</tr>
HTML, $this->data['items']);
        $body = implode('', $rows) ?: '<tr><td colspan="4">No tags yet.</td></tr>';

        return <<<HTML
<div class="card">
    <h2>Tags</h2>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th></th></tr></thead>
        <tbody>$body</tbody>
    </table>
</div>
HTML;
    }

    #[\Override]
    public function update(): string
    {
        if (!isset($this->data['tag'])) {
            return $this->card();
        }
        return <<<HTML
<div class="card">
    <h2>{$this->e($this->data['title'])}</h2>
    <form method="post" action="{$this->e($this->data['action'])}">
        {$this->csrf()}
        <div class="form-group"><label for="name">Name</label><input type="text" id="name" name="name" value="{$this->e($this->data['tag']['name'])}" required></div>
        <div class="btn-group"><button type="submit" class="btn">Save</button><a class="btn btn-ghost" href="?o=Tags">Cancel</a></div>
    </form>
</div>
HTML;
    }
}
