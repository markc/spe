<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Auth\Plugins\Users;

use SPE\Auth\Core\View;

final class UsersView extends View
{
    #[\Override]
    public function list(): string
    {
        $rows = array_map(fn(array $u) => <<<HTML
<tr>
    <td><a href="?o=Users&m=read&i={$this->e($u['id'])}">{$this->e($u['name'])}</a></td>
    <td>{$this->e($u['email'])}</td>
    <td><span class="tag">{$this->e($u['role'])}</span></td>
    <td class="text-right">
        <a class="btn btn-sm" href="?o=Users&m=update&i={$this->e($u['id'])}"><i data-lucide="pencil"></i></a>
        <form method="post" action="?o=Users&m=delete&i={$this->e($u['id'])}" style="display:inline" onsubmit="return confirm('Delete this user?')">{$this->csrf()}<button type="submit" class="btn btn-sm btn-danger"><i data-lucide="trash-2"></i></button></form>
    </td>
</tr>
HTML, $this->data['items']);
        $body = implode('', $rows);

        return <<<HTML
<div class="card">
    <div class="list-header"><h2>Users</h2><a class="btn" href="?o=Users&m=create"><i data-lucide="plus"></i> New user</a></div>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th></th></tr></thead>
        <tbody>$body</tbody>
    </table>
</div>
HTML;
    }

    #[\Override]
    public function read(): string
    {
        if (!isset($this->data['role'])) {
            return $this->card();
        }
        return <<<HTML
<article class="card">
    <h2>{$this->e($this->data['name'])}</h2>
    <p class="text-muted">{$this->e($this->data['email'])} · <span class="tag">{$this->e($this->data['role'])}</span> · joined {$this->e($this->data['created'])}</p>
    <div class="btn-group mt-4"><a class="btn" href="?o=Users"><i data-lucide="arrow-left"></i> Back</a><a class="btn" href="?o=Users&m=update&i={$this->e($this->data['id'])}"><i data-lucide="pencil"></i> Edit</a></div>
</article>
HTML;
    }

    #[\Override]
    public function create(): string { return $this->form(); }

    #[\Override]
    public function update(): string { return isset($this->data['user']) ? $this->form() : $this->card(); }

    private function form(): string
    {
        $u = $this->data['user'];
        $options = implode('', array_map(fn(string $r) => sprintf(
            '<option value="%s"%s>%s</option>', $this->e($r), $r === ($u['role'] ?? 'User') ? ' selected' : '', $this->e($r)
        ), $this->data['roles']));

        return <<<HTML
<div class="card">
    <h2>{$this->e($this->data['title'])}</h2>
    <form method="post" action="{$this->e($this->data['action'])}">
        {$this->csrf()}
        <div class="form-group"><label for="name">Name</label><input type="text" id="name" name="name" value="{$this->e($u['name'])}" required></div>
        <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" value="{$this->e($u['email'])}" required></div>
        <div class="form-group"><label for="role">Role</label><select id="role" name="role">$options</select></div>
        <div class="form-group"><label for="password">Password <small class="text-muted">(leave blank to keep)</small></label><input type="password" id="password" name="password"></div>
        <div class="btn-group"><button type="submit" class="btn">Save</button><a class="btn btn-ghost" href="?o=Users">Cancel</a></div>
    </form>
</div>
HTML;
    }
}
