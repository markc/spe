<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Auth;

use SPE\App\{Db, QueryType, Util};
use SPE\Blog\Core\{Ctx, Plugin};

final class AuthModel extends Plugin {
    private const int OTP_LENGTH = 10;
    private const int REMEMBER_EXP = 604800; // 7 days
    private ?Db $dbh = null;

    public function __construct(protected Ctx $ctx) {
        parent::__construct($ctx);
        $this->dbh = new Db('users');
    }

    // Login form and processing
    #[\Override] public function list(): array {
        if (Util::is_usr()) {
            Util::redirect('?o=Home');
        }

        if (!Util::is_post()) {
            return ['action' => 'login'];
        }

        $login = trim($_POST['login'] ?? '');
        $webpw = $_POST['webpw'] ?? '';

        if (!$login || !$webpw) {
            Util::log('Please enter email and password');
            return ['action' => 'login', 'login' => $login];
        }

        $usr = $this->dbh->read('users', '*', 'login = :login', ['login' => $login], QueryType::One);

        if (!$usr || !password_verify($webpw, $usr['webpw'])) {
            Util::log('Invalid email or password');
            return ['action' => 'login', 'login' => $login];
        }

        if ((int)$usr['acl'] === 9) {
            Util::log('Account is disabled');
            return ['action' => 'login', 'login' => $login];
        }

        // Remember me cookie
        if (isset($_POST['remember'])) {
            $cookie = Util::random_token(32);
            $this->dbh->update('users', ['cookie' => $cookie], 'id = :id', ['id' => $usr['id']]);
            Util::set_cookie('remember', $cookie, self::REMEMBER_EXP);
        }

        $_SESSION['usr'] = [
            'id' => $usr['id'], 'login' => $usr['login'], 'fname' => $usr['fname'],
            'lname' => $usr['lname'], 'acl' => $usr['acl'], 'grp' => $usr['grp']
        ];

        Util::log("{$usr['fname']} logged in", 'success');
        Util::redirect('?o=Home');
    }

    // Forgot password form
    #[\Override] public function create(): array {
        if (Util::is_usr()) {
            Util::redirect('?o=Home');
        }

        if (!Util::is_post()) {
            return ['action' => 'forgot'];
        }

        $login = trim($_POST['login'] ?? '');

        if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
            Util::log('Please enter a valid email address');
            return ['action' => 'forgot', 'login' => $login];
        }

        $usr = $this->dbh->read('users', 'id,acl,login', 'login = :login', ['login' => $login], QueryType::One);

        if (!$usr) {
            Util::log('If that email exists, a reset link has been sent', 'success');
            return ['action' => 'forgot'];
        }

        if ((int)$usr['acl'] === 9) {
            Util::log('Account is disabled');
            return ['action' => 'forgot'];
        }

        $otp = Util::random_token(self::OTP_LENGTH);
        $this->dbh->update('users', ['otp' => $otp, 'otpttl' => time()], 'id = :id', ['id' => $usr['id']]);

        // Send email (simplified - in production use proper mail library)
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $link = "{$scheme}://{$host}{$path}?o=Auth&m=read&otp={$otp}";
        $subject = "Password Reset - {$host}";
        $message = "Click the link below to reset your password (valid for 1 hour):\n\n{$link}\n\nIf you didn't request this, ignore this email.";
        @mail($login, $subject, $message, "From: {$this->ctx->email}");

        Util::log('If that email exists, a reset link has been sent', 'success');
        return ['action' => 'forgot'];
    }

    // Reset password (via OTP link)
    #[\Override] public function read(): array {
        $otp = trim($_GET['otp'] ?? '');

        if (strlen($otp) !== self::OTP_LENGTH * 2) { // hex encoded
            Util::log('Invalid reset link');
            Util::redirect('?o=Auth');
        }

        $usr = $this->dbh->read('users', 'id,login,otpttl', 'otp = :otp', ['otp' => $otp], QueryType::One);

        if (!$usr) {
            Util::log('Invalid or expired reset link');
            Util::redirect('?o=Auth');
        }

        if ((int)$usr['otpttl'] + 3600 < time()) {
            Util::log('Reset link has expired');
            Util::redirect('?o=Auth');
        }

        $_SESSION['resetpw'] = ['id' => $usr['id'], 'login' => $usr['login']];
        Util::redirect('?o=Auth&m=update');
    }

    // Update password form
    #[\Override] public function update(): array {
        $fromReset = isset($_SESSION['resetpw']);
        $fromUser = Util::is_usr();

        if (!$fromReset && !$fromUser) {
            Util::log('Please login first');
            Util::redirect('?o=Auth');
        }

        $id = $fromReset ? $_SESSION['resetpw']['id'] : $_SESSION['usr']['id'];
        $login = $fromReset ? $_SESSION['resetpw']['login'] : $_SESSION['usr']['login'];

        if (!Util::is_post()) {
            return ['action' => 'update', 'login' => $login];
        }

        $p1 = $_POST['passwd1'] ?? '';
        $p2 = $_POST['passwd2'] ?? '';

        if (strlen($p1) < 8) {
            Util::log('Password must be at least 8 characters');
            return ['action' => 'update', 'login' => $login];
        }

        if ($p1 !== $p2) {
            Util::log('Passwords do not match');
            return ['action' => 'update', 'login' => $login];
        }

        $this->dbh->update('users', [
            'webpw' => password_hash($p1, PASSWORD_DEFAULT),
            'otp' => '', 'otpttl' => 0, 'updated' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $id]);

        if ($fromReset) {
            unset($_SESSION['resetpw']);
            Util::log('Password updated. Please login.', 'success');
            Util::redirect('?o=Auth');
        }

        Util::log('Password updated', 'success');
        Util::redirect('?o=Home');
    }

    // Logout
    #[\Override] public function delete(): array {
        if (!Util::is_usr()) {
            Util::redirect('?o=Auth');
        }

        $login = $_SESSION['usr']['login'];
        $id = $_SESSION['usr']['id'];

        // Clear remember cookie
        if (isset($_COOKIE['remember'])) {
            $this->dbh->update('users', ['cookie' => ''], 'id = :id', ['id' => $id]);
            Util::set_cookie('remember', '', -3600);
        }

        unset($_SESSION['usr']);
        session_regenerate_id(true);

        Util::log('Logged out', 'success');
        Util::redirect('?o=Auth');
    }

    // Check remember cookie on init
    public function checkRemember(): void {
        if (Util::is_usr() || !isset($_COOKIE['remember'])) {
            return;
        }

        $cookie = $_COOKIE['remember'];
        $usr = $this->dbh->read('users', '*', 'cookie = :cookie AND cookie != ""', ['cookie' => $cookie], QueryType::One);

        if ($usr && (int)$usr['acl'] !== 9) {
            $_SESSION['usr'] = [
                'id' => $usr['id'], 'login' => $usr['login'], 'fname' => $usr['fname'],
                'lname' => $usr['lname'], 'acl' => $usr['acl'], 'grp' => $usr['grp']
            ];
        }
    }
}
