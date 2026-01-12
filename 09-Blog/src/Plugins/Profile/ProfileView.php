<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Profile;

use SPE\Blog\Core\Theme;

final class ProfileView extends Theme
{
    public function list(): string
    {
        $usr = $this->ctx->ary['usr'] ?? [];
        $login = htmlspecialchars($usr['login'] ?? '');
        $fname = htmlspecialchars($usr['fname'] ?? '');
        $lname = htmlspecialchars($usr['lname'] ?? '');
        $altemail = htmlspecialchars($usr['altemail'] ?? '');
        $created = $usr['created'] ?? '';
        $updated = $usr['updated'] ?? '';
        $t = '&t=' . $this->ctx->in['t'];

        $role = match ((int) ($usr['acl'] ?? 1)) {
            0 => 'Administrator',
            9 => 'Disabled',
            default => 'User',
        };

        return <<<HTML
        <div class="card" style="max-width:700px;margin:2rem auto">
            <h2>👤 My Profile</h2>
            <form method="post" action="?o=Profile$t">
                <div class="grid-2col">
                    <div class="form-group">
                        <label for="login">Email (login)</label>
                        <input type="email" id="login" value="$login" disabled>
                        <small class="text-muted">Contact admin to change</small>
                    </div>
                    <div class="form-group">
                        <label for="altemail">Alternate Email</label>
                        <input type="email" id="altemail" name="altemail" value="$altemail">
                    </div>
                    <div class="form-group">
                        <label for="fname">First Name</label>
                        <input type="text" id="fname" name="fname" value="$fname">
                    </div>
                    <div class="form-group">
                        <label for="lname">Last Name</label>
                        <input type="text" id="lname" name="lname" value="$lname">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="$role" disabled>
                    </div>
                    <div class="form-group">
                        <label>Member Since</label>
                        <input type="text" value="$created" disabled>
                    </div>
                </div>
                <div class="flex" style="justify-content:space-between;align-items:center;margin-top:1.5rem">
                    <a href="?o=Auth&m=update$t">🔑 Change Password</a>
                    <button type="submit" class="btn">Save Profile</button>
                </div>
            </form>
        </div>
        HTML;
    }
}
