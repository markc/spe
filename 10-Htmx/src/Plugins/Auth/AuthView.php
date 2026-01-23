<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Htmx\Plugins\Auth;

use SPE\App\Util;
use SPE\Htmx\Core\Ctx;

/**
 * Auth views - login, forgot password, reset password, change password, profile
 * Enhanced with htmx for SPA-like form submissions
 */
final class AuthView
{
    public function __construct(
        private Ctx $ctx,
        private array $a,
    ) {}

    public function login(): string
    {
        $login = htmlspecialchars($this->a['login'] ?? '');
        $csrf = Util::csrfField();
        $action = '?o=Auth&m=login';
        $forgotUrl = '?o=Auth&m=forgotpw';
        return <<<HTML
        <div class="card card-sm">
            <h2>🔒 Sign In</h2>
            <form method="post" action="$action" hx-post="$action" hx-target="#main">
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
                <div class="text-center mt-2">
                    <a href="$forgotUrl" hx-get="$forgotUrl" hx-target="#main" hx-push-url="true">Forgot password?</a>
                </div>
            </form>
        </div>
        HTML;
    }

    public function logout(): string
    {
        return ''; // Redirects immediately
    }

    public function forgotpw(): string
    {
        $login = htmlspecialchars($this->a['login'] ?? '');
        $csrf = Util::csrfField();
        $action = '?o=Auth&m=forgotpw';
        $backUrl = '?o=Auth&m=login';
        return <<<HTML
        <div class="card card-sm">
            <h2>🔑 Forgot Password</h2>
            <p class="text-muted">Enter your email and we'll send you a reset link.</p>
            <form method="post" action="$action" hx-post="$action" hx-target="#main">
                $csrf
                <div class="form-group">
                    <label for="login">Email</label>
                    <input type="email" id="login" name="login" value="$login" required autofocus>
                </div>
                <div class="flex justify-between">
                    <a href="$backUrl" hx-get="$backUrl" hx-target="#main" hx-push-url="true" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn">Send Reset Link</button>
                </div>
            </form>
        </div>
        HTML;
    }

    public function resetpw(): string
    {
        $login = htmlspecialchars($this->a['login'] ?? '');
        $csrf = Util::csrfField();
        $action = '?o=Auth&m=resetpw';
        return <<<HTML
        <div class="card card-sm">
            <h2>🔐 Reset Password</h2>
            <p class="text-center"><strong>$login</strong></p>
            <form method="post" action="$action" hx-post="$action" hx-target="#main">
                $csrf
                <div class="form-group">
                    <label for="passwd1">New Password</label>
                    <input type="password" id="passwd1" name="passwd1" autocomplete="new-password" required autofocus>
                </div>
                <div class="form-group">
                    <label for="passwd2">Confirm Password</label>
                    <input type="password" id="passwd2" name="passwd2" autocomplete="new-password" required>
                </div>
                <p class="text-muted text-sm">At least 12 characters with uppercase, lowercase, and number.</p>
                <div class="text-right">
                    <button type="submit" class="btn">Reset Password</button>
                </div>
            </form>
        </div>
        HTML;
    }

    public function changepw(): string
    {
        $login = htmlspecialchars($this->a['login'] ?? '');
        $csrf = Util::csrfField();
        $action = '?o=Auth&m=changepw';
        $cancelUrl = '?o=Auth&m=profile';
        return <<<HTML
        <div class="card card-sm">
            <h2>🔒 Change Password</h2>
            <p class="text-center"><strong>$login</strong></p>
            <form method="post" action="$action" hx-post="$action" hx-target="#main" hx-push-url="$cancelUrl">
                $csrf
                <div class="form-group">
                    <label for="webpw">Current Password</label>
                    <input type="password" id="webpw" name="webpw" autocomplete="current-password" required autofocus>
                </div>
                <div class="form-group">
                    <label for="passwd1">New Password</label>
                    <input type="password" id="passwd1" name="passwd1" autocomplete="new-password" required>
                </div>
                <div class="form-group">
                    <label for="passwd2">Confirm New Password</label>
                    <input type="password" id="passwd2" name="passwd2" autocomplete="new-password" required>
                </div>
                <p class="text-muted text-sm">At least 12 characters with uppercase, lowercase, and number.</p>
                <div class="flex justify-between">
                    <a href="$cancelUrl" hx-get="$cancelUrl" hx-target="#main" hx-push-url="true" class="btn btn-muted">Cancel</a>
                    <button type="submit" class="btn">Change Password</button>
                </div>
            </form>
        </div>
        HTML;
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
        $action = '?o=Auth&m=profile';
        $changePwUrl = '?o=Auth&m=changepw';

        return <<<HTML
        <div class="card card-md">
            <h2>👤 My Profile</h2>
            <form method="post" action="$action" hx-post="$action" hx-target="#main">
                $csrf
                <div class="form-group">
                    <label>Email (login)</label>
                    <input type="email" value="$login" disabled>
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
                <div class="flex justify-between">
                    <a href="$changePwUrl" hx-get="$changePwUrl" hx-target="#main" hx-push-url="true" class="btn btn-muted">🔒 Change Password</a>
                    <button type="submit" class="btn">Update Profile</button>
                </div>
            </form>
        </div>
        HTML;
    }
}
