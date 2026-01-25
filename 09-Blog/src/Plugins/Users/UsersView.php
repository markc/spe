<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Users;

use SPE\App\Util;
use SPE\Blog\Core\Ctx;

final class UsersView
{
    public function __construct(
        private Ctx $ctx,
        private array $a,
    ) {}

    private function t(): string
    {
        return '&t=' . $this->ctx->in['t'];
    }

    public function create(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
            return '';
        return $this->form();
    }

    public function read(): string
    {
        $a = $this->a;
        if (empty($a))
            return (
                '<div class="card"><p>User not found.</p><a href="?o=Users'
                . $this->t()
                . '" class="btn"><i data-lucide="chevron-left" class="inline-icon"></i> Back</a></div>'
            );
        $t = $this->t();
        $anote = Util::nlbr($a['anote'] ?? '');
        return <<<HTML
        <div class="card">
            <h2><i data-lucide="user" class="inline-icon"></i> {$a['login']}</h2>
            <div class="mt-2">
                <p><strong>First Name:</strong> {$a['fname']}</p>
                <p><strong>Last Name:</strong> {$a['lname']}</p>
                <p><strong>Alt Email:</strong> {$a['altemail']}</p>
                <p><strong>Group:</strong> {$a['grp']}</p>
                <p><strong>ACL:</strong> {$a['acl']}</p>
                <p><strong>Created:</strong> {$a['created']}</p>
                <p><strong>Updated:</strong> {$a['updated']}</p>
                <p><strong>Admin Note:</strong> $anote</p>
            </div>
            <div class="btn-group mt-3">
                <a href="?o=Users$t" class="btn"><i data-lucide="chevron-left" class="inline-icon"></i> Back</a>
                <a href="?o=Users&m=update&id={$a['id']}$t" class="btn">Edit</a>
                <a href="?o=Users&m=delete&id={$a['id']}$t" class="btn btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
            </div>
        </div>
        HTML;
    }

    public function update(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
            return '';
        return $this->form($this->a);
    }

    public function delete(): string
    {
        return '';
    }

    public function list(): string
    {
        $a = $this->a;
        $t = $this->t();
        $q = htmlspecialchars($_GET['q'] ?? '');
        $clear = $q ? "<a href=\"?o=Users$t\" class=\"btn\">Clear</a>" : '';

        $html = <<<HTML
        <div class="card">
            <div class="list-header">
                <form class="search-form">
                    <input type="hidden" name="o" value="Users">
                    <input type="hidden" name="t" value="{$this->ctx->in['t']}">
                    <input type="search" name="q" placeholder="Search..." value="$q" class="search-input">
                    <button type="submit" class="btn">Search</button>
                    $clear
                </form>
                <a href="?o=Users&m=create$t" class="btn">+ Add User</a>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Login</th>
                        <th>Name</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
        HTML;

        foreach ($a['items'] as $item) {
            $login = htmlspecialchars($item['login']);
            $name = htmlspecialchars($item['fname'] . ' ' . $item['lname']);
            $html .= <<<HTML
                <tr>
                    <td><a href="?o=Users&m=read&id={$item['id']}$t">$login</a></td>
                    <td>$name</td>
                    <td><small>{$item['created']}</small></td>
                    <td><small>{$item['updated']}</small></td>
                    <td class="text-right">
                        <a href="?o=Users&m=update&id={$item['id']}$t" title="Edit" class="icon"><i data-lucide="edit" class="inline-icon"></i></a>
                        <a href="?o=Users&m=delete&id={$item['id']}$t" title="Delete" class="icon" onclick="return confirm('Delete this user?')"><i data-lucide="trash-2" class="inline-icon"></i></a>
                    </td>
                </tr>
            HTML;
        }

        $html .= '</tbody></table>';

        // Pagination
        $p = $a['pagination'];
        if ($p['pages'] > 1) {
            $sq = $q ? "&q=$q" : '';
            $html .= '<div class="btn-group-center mt-4">';
            if ($p['page'] > 1)
                $html .= "<a href=\"?o=Users&page=" . ($p['page'] - 1) . "$sq$t\" class=\"btn\"><i data-lucide=\"chevron-left\" class=\"inline-icon\"></i> Prev</a>";
            $html .= "<span class=\"p-2\">Page {$p['page']} of {$p['pages']}</span>";
            if ($p['page'] < $p['pages'])
                $html .= "<a href=\"?o=Users&page=" . ($p['page'] + 1) . "$sq$t\" class=\"btn\">Next <i data-lucide=\"chevron-right\" class=\"inline-icon\"></i></a>";
            $html .= '</div>';
        }

        return $html . '</div>';
    }

    private function form(array $data = []): string
    {
        $id = $data['id'] ?? 0;
        $t = $this->t();
        $login = htmlspecialchars($data['login'] ?? '');
        $fname = htmlspecialchars($data['fname'] ?? '');
        $lname = htmlspecialchars($data['lname'] ?? '');
        $altemail = htmlspecialchars($data['altemail'] ?? '');
        $grp = (int) ($data['grp'] ?? 0);
        $acl = (int) ($data['acl'] ?? 0);
        $anote = htmlspecialchars($data['anote'] ?? '');
        $action = $id ? "?o=Users&m=update&id=$id$t" : "?o=Users&m=create$t";
        $heading = $id ? 'Edit User' : 'Create User';
        $btnText = $id ? 'Update' : 'Create';

        return <<<HTML
        <div class="card">
            <h2>$heading</h2>
            <form method="post" action="$action">
                <input type="hidden" name="id" value="$id">
                <div class="form-group">
                    <label for="login">Login (Email)</label>
                    <input type="email" id="login" name="login" value="$login" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="fname">First Name</label>
                        <input type="text" id="fname" name="fname" value="$fname">
                    </div>
                    <div class="form-group">
                        <label for="lname">Last Name</label>
                        <input type="text" id="lname" name="lname" value="$lname">
                    </div>
                </div>
                <div class="form-group">
                    <label for="altemail">Alternate Email</label>
                    <input type="email" id="altemail" name="altemail" value="$altemail">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="grp">Group</label>
                        <input type="number" id="grp" name="grp" value="$grp">
                    </div>
                    <div class="form-group">
                        <label for="acl">ACL</label>
                        <input type="number" id="acl" name="acl" value="$acl">
                    </div>
                </div>
                <div class="form-group">
                    <label for="webpw">Password (leave blank to keep current)</label>
                    <input type="password" id="webpw" name="webpw" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="anote">Admin Note</label>
                    <textarea id="anote" name="anote" rows="3">$anote</textarea>
                </div>
                <div class="text-right">
                    <a href="?o=Users$t" class="btn btn-muted">Cancel</a>
                    <button type="submit" class="btn">$btnText</button>
                </div>
            </form>
        </div>
        HTML;
    }
}
