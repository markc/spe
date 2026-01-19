<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Auth;

use SPE\App\Acl;
use SPE\App\Db;
use SPE\App\QueryType;
use SPE\App\Util;
use SPE\Blog\Core\Ctx;

/**
 * Auth plugin - handles authentication flows
 *
 * Methods:
 *   login    - Login form and processing
 *   logout   - Clear session and cookies
 *   forgotpw - Request password reset (sends OTP email)
 *   resetpw  - Reset password via OTP link
 *   profile  - User profile management
 */
final class AuthModel
{
    private Db $db;
    private array $f = [
        'login' => '',
        'webpw' => '',
        'remember' => '',
        'otp' => '',
        'passwd1' => '',
        'passwd2' => '',
        'fname' => '',
        'lname' => '',
        'altemail' => '',
    ];

    public function __construct(
        private Ctx $ctx,
    ) {
        foreach ($this->f as $k => &$v)
            $v = $_REQUEST[$k] ?? $v;
        $this->db = new Db('users');
    }

    // === Login ===

    public function login(): array
    {
        Util::elog(__METHOD__);

        // Already logged in
        if (Util::is_usr()) {
            header('Location: /');
            exit();
        }

        if (!Util::is_post()) {
            return ['action' => 'login'];
        }

        $login = trim($this->f['login']);
        $webpw = Util::decpw($this->f['webpw']);

        if (!$login || !$webpw) {
            Util::log('Please enter email and password');
            return ['action' => 'login', 'login' => $login];
        }

        $usr = $this->db->read('users', '*', 'login = :login', ['login' => $login], QueryType::One);

        if (!$usr || !password_verify($webpw, $usr['webpw'])) {
            Util::log('Invalid email or password');
            return ['action' => 'login', 'login' => $login];
        }

        $acl = Acl::tryFrom((int) $usr['acl']) ?? Acl::Anonymous;

        if ($acl === Acl::Suspended) {
            Util::log('Account is suspended. Contact administrator.');
            return ['action' => 'login', 'login' => $login];
        }

        // Set session
        $_SESSION['usr'] = [
            'id' => $usr['id'],
            'grp' => $usr['grp'],
            'acl' => $usr['acl'],
            'login' => $usr['login'],
            'fname' => $usr['fname'],
            'lname' => $usr['lname'],
        ];

        // Remember me cookie
        if ($this->f['remember']) {
            Util::setRemember($this->db, (int) $usr['id']);
        }

        // Set admin session flag (from HCP pattern)
        if ($acl->can(Acl::Admin)) {
            $_SESSION['adm'] = $usr['id'];
        }

        Util::log(($usr['fname'] ?: $usr['login']) . ' logged in', 'success');
        header('Location: /');
        exit();
    }

    // === Logout ===

    public function logout(): array
    {
        Util::elog(__METHOD__);

        if (Util::is_usr()) {
            $login = $_SESSION['usr']['login'] ?? 'User';
            $id = (int) $_SESSION['usr']['id'];

            // Clear remember cookie
            Util::clearRemember($this->db, $id);

            // Clear admin session
            if (isset($_SESSION['adm'])) {
                unset($_SESSION['adm']);
            }

            unset($_SESSION['usr']);
            session_regenerate_id(true);

            Util::log("$login logged out", 'success');
        }

        header('Location: /');
        exit();
    }

    // === Forgot Password (request reset) ===

    public function forgotpw(): array
    {
        Util::elog(__METHOD__);

        if (Util::is_usr()) {
            header('Location: /');
            exit();
        }

        if (!Util::is_post()) {
            return ['action' => 'forgotpw'];
        }

        $login = trim($this->f['login']);

        if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
            Util::log('Please enter a valid email address');
            return ['action' => 'forgotpw', 'login' => $login];
        }

        $usr = $this->db->read('users', 'id,acl,login', 'login = :login', ['login' => $login], QueryType::One);

        // Always show success message (don't reveal if email exists)
        $successMsg = 'If that email exists, a password reset link has been sent';

        if ($usr) {
            $acl = Acl::tryFrom((int) $usr['acl']) ?? Acl::Anonymous;

            if ($acl !== Acl::Suspended && $acl !== Acl::Anonymous) {
                $otp = Util::genOtp();

                $this->db->update(
                    'users',
                    [
                        'otp' => $otp,
                        'otpttl' => time(),
                        'updated' => date('Y-m-d H:i:s'),
                    ],
                    'id = :id',
                    ['id' => $usr['id']],
                );

                if (Util::mailResetPw($login, $otp, $this->ctx->email)) {
                    Util::elog("Password reset email sent to $login");
                } else {
                    Util::elog("Failed to send password reset email to $login");
                }
            }
        }

        Util::log($successMsg, 'success');
        header('Location: ?o=Auth&m=login');
        exit();
    }

    // === Reset Password (via OTP link) ===

    public function resetpw(): array
    {
        Util::elog(__METHOD__);

        // Check for OTP in URL
        $otp = trim($_GET['otp'] ?? '');

        if ($otp) {
            // Validate OTP from URL
            $usr = $this->db->read('users', '*', 'otp = :otp', ['otp' => $otp], QueryType::One);

            if (!$usr) {
                Util::log('Invalid or expired reset link');
                header('Location: ?o=Auth&m=login');
                exit();
            }

            if (!Util::chkOtp((int) $usr['otpttl'])) {
                // Clear expired OTP
                $this->db->update('users', ['otp' => '', 'otpttl' => 0], 'id = :id', ['id' => $usr['id']]);
                header('Location: ?o=Auth&m=login');
                exit();
            }

            // Store in session for form submission
            $_SESSION['resetpw'] = [
                'id' => $usr['id'],
                'login' => $usr['login'],
            ];

            return ['action' => 'resetpw', 'login' => $usr['login']];
        }

        // Handle form submission
        if (!isset($_SESSION['resetpw'])) {
            Util::log('Session expired. Please request a new reset link.');
            header('Location: ?o=Auth&m=forgotpw');
            exit();
        }

        if (!Util::is_post()) {
            return ['action' => 'resetpw', 'login' => $_SESSION['resetpw']['login']];
        }

        $id = (int) $_SESSION['resetpw']['id'];
        $p1 = Util::decpw($this->f['passwd1']);
        $p2 = Util::decpw($this->f['passwd2']);

        if (!Util::chkpw($p1, $p2)) {
            return ['action' => 'resetpw', 'login' => $_SESSION['resetpw']['login']];
        }

        // Verify OTP hasn't expired during form fill
        $usr = $this->db->read('users', 'otpttl', 'id = :id', ['id' => $id], QueryType::One);

        if (!$usr || !Util::chkOtp((int) $usr['otpttl'])) {
            unset($_SESSION['resetpw']);
            Util::log('Reset link expired. Please request a new one.');
            header('Location: ?o=Auth&m=forgotpw');
            exit();
        }

        // Update password and clear OTP
        $this->db->update(
            'users',
            [
                'webpw' => password_hash($p1, PASSWORD_DEFAULT),
                'otp' => '',
                'otpttl' => 0,
                'updated' => date('Y-m-d H:i:s'),
            ],
            'id = :id',
            ['id' => $id],
        );

        unset($_SESSION['resetpw']);
        Util::log('Password reset successfully. Please login.', 'success');
        header('Location: ?o=Auth&m=login');
        exit();
    }

    // === Change Password (logged-in user) ===

    public function changepw(): array
    {
        Util::elog(__METHOD__);

        if (!Util::is_usr()) {
            header('Location: ?o=Auth&m=login');
            exit();
        }

        $id = (int) $_SESSION['usr']['id'];
        $login = $_SESSION['usr']['login'];

        if (!Util::is_post()) {
            return ['action' => 'changepw', 'login' => $login];
        }

        // Verify current password
        $currentPw = Util::decpw($this->f['webpw']);
        $usr = $this->db->read('users', 'webpw', 'id = :id', ['id' => $id], QueryType::One);

        if (!$usr || !password_verify($currentPw, $usr['webpw'])) {
            Util::log('Current password is incorrect');
            return ['action' => 'changepw', 'login' => $login];
        }

        $p1 = Util::decpw($this->f['passwd1']);
        $p2 = Util::decpw($this->f['passwd2']);

        if (!Util::chkpw($p1, $p2)) {
            return ['action' => 'changepw', 'login' => $login];
        }

        $this->db->update(
            'users',
            [
                'webpw' => password_hash($p1, PASSWORD_DEFAULT),
                'updated' => date('Y-m-d H:i:s'),
            ],
            'id = :id',
            ['id' => $id],
        );

        Util::log('Password changed successfully', 'success');
        header('Location: ?o=Auth&m=profile');
        exit();
    }

    // === User Profile ===

    public function profile(): array
    {
        Util::elog(__METHOD__);

        if (!Util::is_usr()) {
            header('Location: ?o=Auth&m=login');
            exit();
        }

        $id = (int) $_SESSION['usr']['id'];

        if (Util::is_post()) {
            $data = [
                'fname' => trim($this->f['fname']),
                'lname' => trim($this->f['lname']),
                'altemail' => trim($this->f['altemail']),
                'updated' => date('Y-m-d H:i:s'),
            ];

            $this->db->update('users', $data, 'id = :id', ['id' => $id]);

            // Update session
            $_SESSION['usr']['fname'] = $data['fname'];
            $_SESSION['usr']['lname'] = $data['lname'];

            Util::log('Profile updated', 'success');
            header('Location: ?o=Auth&m=profile');
            exit();
        }

        return $this->db->read('users', '*', 'id = :id', ['id' => $id], QueryType::One) ?: [];
    }
}
