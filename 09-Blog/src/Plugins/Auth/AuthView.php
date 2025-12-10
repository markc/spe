<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Auth;

use SPE\Blog\Core\{Ctx, Theme};

final class AuthView extends Theme {

    public function list(): string {
        $login = htmlspecialchars($this->ctx->ary['login'] ?? '');
        $t = '&t=' . $this->ctx->in['t'];
        return <<<HTML
        <div class="card" style="max-width:400px;margin:2rem auto">
            <h2>Sign In</h2>
            <form method="post" action="?o=Auth&m=list$t">
                <div class="form-group">
                    <label for="login">Email</label>
                    <input type="email" id="login" name="login" value="$login" required autofocus>
                </div>
                <div class="form-group">
                    <label for="webpw">Password</label>
                    <input type="password" id="webpw" name="webpw" autocomplete="current-password" required>
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:0.5rem">
                    <input type="checkbox" id="remember" name="remember" style="width:auto">
                    <label for="remember" style="margin:0;font-weight:normal">Remember me</label>
                </div>
                <div class="flex" style="justify-content:space-between;align-items:center">
                    <a href="?o=Auth&m=create$t">Forgot password?</a>
                    <button type="submit" class="btn">Sign In</button>
                </div>
            </form>
        </div>
        HTML;
    }

    public function create(): string {
        $login = htmlspecialchars($this->ctx->ary['login'] ?? '');
        $t = '&t=' . $this->ctx->in['t'];
        return <<<HTML
        <div class="card" style="max-width:400px;margin:2rem auto">
            <h2>Forgot Password</h2>
            <form method="post" action="?o=Auth&m=create$t">
                <div class="form-group">
                    <label for="login">Email</label>
                    <input type="email" id="login" name="login" value="$login" required autofocus>
                </div>
                <p class="text-muted" style="font-size:0.9rem;margin:1rem 0">
                    Enter your email address and we'll send you a link to reset your password.
                </p>
                <div class="flex" style="justify-content:space-between;align-items:center">
                    <a href="?o=Auth$t">Back to Sign In</a>
                    <button type="submit" class="btn">Send Reset Link</button>
                </div>
            </form>
        </div>
        HTML;
    }

    public function update(): string {
        $login = htmlspecialchars($this->ctx->ary['login'] ?? '');
        $t = '&t=' . $this->ctx->in['t'];
        return <<<HTML
        <div class="card" style="max-width:400px;margin:2rem auto">
            <h2>Update Password</h2>
            <p class="text-center mb-2"><strong>$login</strong></p>
            <form method="post" action="?o=Auth&m=update$t">
                <div class="form-group">
                    <label for="passwd1">New Password</label>
                    <input type="password" id="passwd1" name="passwd1" autocomplete="new-password" required>
                </div>
                <div class="form-group">
                    <label for="passwd2">Confirm Password</label>
                    <input type="password" id="passwd2" name="passwd2" autocomplete="new-password" required>
                </div>
                <p class="text-muted" style="font-size:0.85rem">Minimum 8 characters</p>
                <div class="text-right">
                    <button type="submit" class="btn">Update Password</button>
                </div>
            </form>
        </div>
        HTML;
    }

    public function delete(): string {
        return '';
    }

    public function read(): string {
        return '';
    }
}
