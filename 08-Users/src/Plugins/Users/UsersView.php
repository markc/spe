<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Users\Plugins\Users;

use SPE\App\Util;
use SPE\Users\Core\Ctx;

final class UsersView
{
    public function __construct(
        private Ctx $ctx,
        private array $a,
    ) {}

    // Auth views
    public function login(): string
    {
        $login = htmlspecialchars($this->a['login'] ?? '');
        $csrf = Util::csrfField();
        return <<<HTML
        <div class="card" style="max-width:400px;margin:2rem auto">
            <h2>🔒 Sign In</h2>
            <form method="post" action="?o=Users&m=login">
                $csrf
                <div class="form-group">
                    <label for="login">Email</label>
                    <input type="email" id="login" name="login" value="$login" required autofocus>
                </div>
                <div class="form-group">
                    <label for="webpw">Password</label>
                    <input type="password" id="webpw" name="webpw" autocomplete="current-password" required>
                </div>
                <div class="flex justify-between">
                    <label><input type="checkbox" name="remember" value="1"> Remember me</label>
                    <button type="submit" class="btn">Sign In</button>
                </div>
            </form>
        </div>
        HTML;
    }

    public function logout(): string
    {
        return '';
    }

    public function profile(): string
    {
        $a = $this->a;
        if (!$a)
            return '<div class="card mt-4"><p>Profile not found.</p></div>';

        $fname = htmlspecialchars($a['fname'] ?? '');
        $lname = htmlspecialchars($a['lname'] ?? '');
        $login = htmlspecialchars($a['login'] ?? '');
        $altemail = htmlspecialchars($a['altemail'] ?? '');

        $csrf = Util::csrfField();
        return <<<HTML
        <div class="card" style="max-width:500px;margin:2rem auto">
            <h2>👤 My Profile</h2>
            <form method="post" action="?o=Users&m=profile">
                $csrf
                <div class="form-group">
                    <label>Email (login)</label>
                    <input type="email" value="$login" disabled>
                </div>
                <div class="flex">
                    <div class="form-group flex-1">
                        <label for="fname">First Name</label>
                        <input type="text" id="fname" name="fname" value="$fname">
                    </div>
                    <div class="form-group flex-1">
                        <label for="lname">Last Name</label>
                        <input type="text" id="lname" name="lname" value="$lname">
                    </div>
                </div>
                <div class="form-group">
                    <label for="altemail">Alternate Email</label>
                    <input type="email" id="altemail" name="altemail" value="$altemail">
                </div>
                <div class="form-group">
                    <label for="webpw">New Password (leave blank to keep current)</label>
                    <input type="password" id="webpw" name="webpw" autocomplete="new-password">
                </div>
                <div class="flex justify-between">
                    <a href="/" class="btn btn-muted">Cancel</a>
                    <button type="submit" class="btn">Update Profile</button>
                </div>
            </form>
        </div>
        HTML;
    }

    // Admin views
    public function create(): string
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST' ? '' : $this->form();
    }

    public function update(): string
    {
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
            return '<div class="card mt-4"><p class=text-muted>User not found.</p><a href="?o=Users" class=btn>« Back</a></div>';
        $anote = Util::nlbr($a['anote'] ?? '');
        return "<div class='card mt-4'><h2>👤 {$a['login']}</h2>
            <div class=mt-2>
            <p><strong>First Name:</strong> {$a['fname']}</p>
            <p><strong>Last Name:</strong> {$a['lname']}</p>
            <p><strong>Alt Email:</strong> {$a['altemail']}</p>
            <p><strong>Group:</strong> {$a['grp']}</p>
            <p><strong>ACL:</strong> {$a['acl']}</p>
            <p><strong>Created:</strong> {$a['created']}</p>
            <p><strong>Updated:</strong> {$a['updated']}</p>
            <p><strong>Admin Note:</strong> $anote</p></div>
            <div class='flex mt-3 gap-sm justify-end'>
            <a href='?o=Users' class=btn>« Back</a>
            <a href='?o=Users&m=update&i={$a['id']}' class=btn>✏️ Edit</a>
            <a href='?o=Users&m=delete&i={$a['id']}' class='btn btn-danger' onclick='return confirm(\"Delete this user?\")'>🗑️</a></div></div>";
    }

    public function list(): string
    {
        $a = $this->a;
        $q = htmlspecialchars($_GET['q'] ?? '');
        $cl = $q ? "<a href='?o=Users' class=btn>✕</a>" : '';

        $h = "<div class='card mt-4'><div class='flex justify-between mb-2'>
            <form class='flex gap-sm'><input type=hidden name=o value=Users>
            <input type=search name=q placeholder=Search... value='$q' class=w-200>
            <button type=submit class=btn>🔍</button>$cl</form>
            <a href='?o=Users&m=create' class=btn>+ Add User</a></div>
            <table class=table><thead><tr class=tr-header>
            <th class=th>Login</th><th class=th>Name</th>
            <th class=th>Created</th><th class=th>Updated</th><th class=th-right>Actions</th></tr></thead><tbody>";

        foreach ($a['items'] as $i) {
            $login = htmlspecialchars($i['login']);
            $name = htmlspecialchars($i['fname'] . ' ' . $i['lname']);
            $h .= "<tr class=tr><td class=td><a href='?o=Users&m=read&i={$i['id']}'>$login</a></td>
                <td class=td>$name</td>
                <td class=td><small>{$i['created']}</small></td>
                <td class=td><small>{$i['updated']}</small></td>
                <td class=td-right><a href='?o=Users&m=update&i={$i['id']}'>✏️</a>
                <a href='?o=Users&m=delete&i={$i['id']}' onclick='return confirm(\"Delete this user?\")'>🗑️</a></td></tr>";
        }

        $h .= '</tbody></table>' . $this->pg($a['pagination']) . '</div>';
        return $h;
    }

    private function pg(array $p): string
    {
        if ($p['pages'] <= 1)
            return '';
        $q = htmlspecialchars($_GET['q'] ?? '');
        $sq = $q ? "&q=$q" : '';
        $h = "<div class='flex mt-2 justify-center gap-sm'>";
        if ($p['page'] > 1)
            $h .= "<a href='?o=Users&page=" . ($p['page'] - 1) . "$sq' class=btn>« Prev</a>";
        $h .= "<span class=td>Page {$p['page']} of {$p['pages']}</span>";
        if ($p['page'] < $p['pages'])
            $h .= "<a href='?o=Users&page=" . ($p['page'] + 1) . "$sq' class=btn>Next »</a>";
        return $h . '</div>';
    }

    private function form(array $d = []): string
    {
        $id = $d['id'] ?? 0;
        $login = htmlspecialchars($d['login'] ?? '');
        $fname = htmlspecialchars($d['fname'] ?? '');
        $lname = htmlspecialchars($d['lname'] ?? '');
        $altemail = htmlspecialchars($d['altemail'] ?? '');
        $grp = (int) ($d['grp'] ?? 0);
        $acl = (int) ($d['acl'] ?? 0);
        $anote = htmlspecialchars($d['anote'] ?? '');
        $act = $id ? "?o=Users&m=update&i=$id" : '?o=Users&m=create';
        $hd = $id ? '✏️ Edit User' : '+ Create User';
        $bt = $id ? 'Update' : 'Create';

        $csrf = Util::csrfField();
        return "<div class='card mt-4'><h2>$hd</h2><form method=post action='$act'>$csrf<input type=hidden name=i value=$id>
            <div class=form-group><label for=login>Login (Email)</label>
            <input type=email id=login name=login value='$login' required></div>
            <div class=flex><div class='form-group flex-1'><label for=fname>First Name</label>
            <input type=text id=fname name=fname value='$fname'></div>
            <div class='form-group flex-1'><label for=lname>Last Name</label>
            <input type=text id=lname name=lname value='$lname'></div></div>
            <div class=form-group><label for=altemail>Alternate Email</label>
            <input type=email id=altemail name=altemail value='$altemail'></div>
            <div class=flex><div class='form-group flex-1'><label for=grp>Group</label>
            <input type=number id=grp name=grp value='$grp'></div>
            <div class='form-group flex-1'><label for=acl>ACL</label>
            <input type=number id=acl name=acl value='$acl'></div></div>
            <div class=form-group><label for=webpw>Password (leave blank to keep current)</label>
            <input type=password id=webpw name=webpw autocomplete=new-password></div>
            <div class=form-group><label for=anote>Admin Note</label>
            <textarea id=anote name=anote rows=3>$anote</textarea></div>
            <div class='flex justify-between'><a href='?o=Users' class='btn btn-muted'>Cancel</a>
            <button type=submit class=btn>$bt</button></div></form></div>";
    }
}
