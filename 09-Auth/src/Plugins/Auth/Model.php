<?php

declare(strict_types=1);
// Created 20250101 - Updated: 20250202
// Copyright (C) 2015-2020 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Auth\Plugins\Auth;

use SPE\Auth\Core\{Plugin, Util, Db, QueryType, Ctx};

class Model extends Plugin
{
    private const OTP_LENGTH = 10;
    private const REMEMBER_ME_EXP = 604800; // 7 days;

    private ?Db $dbh = null;
    protected string $tbl = 'accounts';
    protected array $in = [
        'id'        => null,
        'acl'       => null,
        'grp'       => null,
        'login'     => '',
        'webpw'     => '',
        'remember'  => '',
        'otp'       => '',
        'passwd1'   => '',
        'passwd2'   => '',
    ];

    public function __construct(Ctx $ctx)
    {
        parent::__construct($ctx);

        if (is_null($this->dbh))
        {
            $this->dbh = new Db([
                'type' => 'sqlite',
                'path' => __DIR__ . '/auth.db',
                'name' => 'auth'
            ]);
        }
    }

    /**
     * Sign Up - Creates a new user account in the system
     * 
     * This method handles the user registration process by creating a new account.
     * It validates the email address, checks for existing accounts, and sends a
     * verification email to complete the registration process.
     * 
     * @return void
     */
    public function create(): void
    {
        Util::elog(__METHOD__);

        $u = (string)$this->in['login'];

        if (Util::is_post())
        {
            if (filter_var($u, FILTER_VALIDATE_EMAIL))
            {
                if ($usr = $this->dbh->read('accounts', 'id,acl', 'login = :login', ['login' => $u], QueryType::One))
                {
                    if ($usr['acl'] != 9)
                    {
                        $newpass = Util::genpw(self::OTP_LENGTH);
                        if ($this->mail_forgotpw($u, $newpass, 'From: ' . $this->ctx->email))
                        {
                            $this->dbh->update('accounts', [
                                'otp' => $newpass,
                                'otpttl' => time()
                            ], 'id = :id', ['id' => $usr['id']]);
                            Util::log('Sent reset password key for "' . $u . '" so please check your mailbox and click on the supplied link.', 'success');
                            $this->ctx->ary = [
                                'status' => 'success',
                                'message' => 'Password reset email sent',
                                'redirect' => $this->ctx->self . '?plugin=Auth&action=list'
                            ];
                            return;
                        }
                        else
                        {
                            Util::log('Problem sending message to ' . $u, 'danger');
                            $this->ctx->ary = [
                                'status' => 'error',
                                'message' => 'Failed to send password reset email'
                            ];
                            return;
                        }
                    }
                    else
                    {
                        $this->ctx->ary = [
                            'status' => 'error',
                            'message' => 'Account is disabled, contact your System Administrator'
                        ];
                        return;
                    }
                }
                else
                {
                    $this->ctx->ary = [
                        'status' => 'error',
                        'message' => 'User does not exist'
                    ];
                    return;
                }
            }
            else
            {
                $this->ctx->ary = [
                    'status' => 'error',
                    'message' => 'You must provide a valid email address'
                ];
                return;
            }
        }

        // Set form view state
        $this->ctx->ary = [
            'status' => 'form',
            'message' => 'Reset password',
            'data' => ['login' => $u]
        ];
    }

    /**
     * Sign In - Authenticates a user and creates a session
     * 
     * This method handles the user login process. It verifies the provided credentials,
     * manages remember-me functionality, and creates the user session upon successful
     * authentication. It also handles admin authentication and account status checks.
     * 
     * @return void
     */
    public function list(): void
    {
        Util::elog(__METHOD__);

        $u = (string)$this->in['login'];
        $p = (string)$this->in['webpw'];

        if ($u)
        {
            if ($usr = $this->dbh->read('accounts', 'id,grp,acl,login,fname,lname,webpw,cookie', 'login = :login', ['login' => $u], QueryType::One))
            {
                $id = (int)$usr['id'];
                $acl = (int)$usr['acl'];
                $login = (string)$usr['login'];
                $webpw = (string)$usr['webpw'];

                if ($acl !== 9)
                {
                    if (password_verify(html_entity_decode($p, ENT_QUOTES, 'UTF-8'), $webpw))
                    {
                        if ($this->in['remember'])
                        {
                            $uniq = Util::random_token(32);
                            $this->dbh->update('accounts', ['cookie' => $uniq], 'id = :id', ['id' => $id]);
                            Util::put_cookie('remember', $uniq, self::REMEMBER_ME_EXP);
                        }
                        $_SESSION['usr'] = $usr;
                        Util::log($login . ' is now logged in', 'success');
                        if ($acl === 0) $_SESSION['adm'] = $id;
                        $_SESSION['m'] = 'list';
                        // Only redirect if we're not already on the home page
                        if ($_SERVER['REQUEST_URI'] !== $this->ctx->self)
                        {
                            Util::redirect($this->ctx->self);
                        }
                        $this->ctx->ary = [
                            'status' => 'success',
                            'message' => 'Logged in successfully',
                            'redirect' => true
                        ];
                        return;
                    }
                    else
                    {
                        Util::log('Invalid Email Or Password');
                        $this->ctx->ary = [
                            'status' => 'error',
                            'message' => 'Invalid Email Or Password'
                        ];
                        return;
                    }
                }
                else
                {
                    Util::log('Account is disabled, contact your System Administrator');
                    $this->ctx->ary = [
                        'status' => 'error',
                        'message' => 'Account is disabled'
                    ];
                    return;
                }
            }
            else
            {
                Util::log('Invalid Email Or Password');
                $this->ctx->ary = [
                    'status' => 'error',
                    'message' => 'Invalid Email Or Password'
                ];
                return;
            }
        }

        $this->ctx->ary = [
            'status' => 'form',
            'message' => 'Login',
            'data' => ['login' => $u]
        ];
    }

    /**
     * Update Password - Changes the user's password
     * 
     * This method handles password updates for authenticated users or users with valid
     * reset tokens. It validates the new password, verifies the user's session or
     * reset token, and updates the password in the database.
     * 
     * @return void
     */
    public function update(): void
    {
        Util::elog(__METHOD__);

        if (!(Util::is_usr() || isset($_SESSION['resetpw'])))
        {
            Util::log('Session expired! Please login and try again.');
            $this->ctx->ary = [
                'status' => 'error',
                'message' => 'Session expired',
                'redirect' => $this->ctx->self . '?plugin=Auth'
            ];
            return;
        }

        $i = (Util::is_usr()) ? (int)$_SESSION['usr']['id'] : (int)$_SESSION['resetpw']['usr']['id'];
        $u = (Util::is_usr()) ? (string)$_SESSION['usr']['login'] : (string)$_SESSION['resetpw']['usr']['login'];

        if (Util::is_post())
        {
            if ($usr = $this->dbh->read('accounts', 'login,acl,otpttl', 'id = :id', ['id' => $i], QueryType::One))
            {
                $p1 = html_entity_decode($this->in['passwd1'], ENT_QUOTES, 'UTF-8');
                $p2 = html_entity_decode($this->in['passwd2'], ENT_QUOTES, 'UTF-8');
                if (Util::chkpw($p1, $p2))
                {
                    if (Util::is_usr() || ($usr['otpttl'] && ((int)$usr['otpttl'] + 3600) > time()))
                    {
                        if (!is_null($usr['acl']))
                        {
                            if ($this->dbh->update('accounts', [
                                'webpw'   => password_hash($p1, PASSWORD_DEFAULT),
                                'otp'     => '',
                                'otpttl'  => 0,
                                'updated' => date('Y-m-d H:i:s'),
                            ], 'id = :id', ['id' => $i]))
                            {
                                Util::log('Password reset for ' . $usr['login'], 'success');
                                if (Util::is_usr())
                                {
                                    $this->ctx->ary = [
                                        'status' => 'success',
                                        'message' => 'Password updated successfully',
                                        'redirect' => $this->ctx->self
                                    ];
                                    return;
                                }
                                else
                                {
                                    unset($_SESSION['resetpw']);
                                    $this->ctx->ary = [
                                        'status' => 'success',
                                        'message' => 'Password reset successfully',
                                        'redirect' => $this->ctx->self . '?plugin=Auth'
                                    ];
                                    return;
                                }
                            }
                            else
                            {
                                Util::log('Problem updating database');
                                $this->ctx->ary = [
                                    'status' => 'error',
                                    'message' => 'Database update failed'
                                ];
                                return;
                            }
                        }
                        else
                        {
                            Util::log($usr['login'] . ' is not allowed access');
                            $this->ctx->ary = [
                                'status' => 'error',
                                'message' => 'Access denied'
                            ];
                            return;
                        }
                    }
                    else
                    {
                        Util::log('Your one time password key has expired');
                        $this->ctx->ary = [
                            'status' => 'error',
                            'message' => 'Password reset key expired'
                        ];
                        return;
                    }
                }
                $this->ctx->ary = [
                    'status' => 'error',
                    'message' => 'Password validation failed'
                ];
                return;
            }
            else
            {
                Util::log('User does not exist');
                $this->ctx->ary = [
                    'status' => 'error',
                    'message' => 'User not found'
                ];
                return;
            }
        }

        $this->ctx->ary = [
            'status' => 'form',
            'message' => 'Reset password',
            'data' => ['id' => $i, 'login' => $u]
        ];
    }

    /**
     * Delete Session - Logs out the user and ends their session
     * 
     * This method handles the user logout process. It cleans up the session data,
     * removes remember-me cookies if present, and redirects to the home page.
     * Also handles special cleanup for admin sessions.
     * 
     * @return void
     */
    public function delete(): void
    {
        Util::elog(__METHOD__);

        if (Util::is_usr())
        {
            $u = (string)$_SESSION['usr']['login'];
            $id = (int)$_SESSION['usr']['id'];
            if (isset($_SESSION['adm']) && $_SESSION['usr']['id'] === $_SESSION['adm'])
            {
                unset($_SESSION['adm']);
            }
            unset($_SESSION['usr']);
            if (isset($_COOKIE['remember']))
            {
                $this->dbh->update('accounts', ['cookie' => ''], 'id = :id', ['id' => $id]);
                setcookie('remember', '', strtotime('-1 hour', 0));
            }
            Util::log($u . ' is now logged out', 'success');
            $this->ctx->ary = [
                'status' => 'success',
                'message' => 'Logged out successfully',
                'redirect' => $this->ctx->self
            ];
            return;
        }

        $this->ctx->ary = [
            'status' => 'error',
            'message' => 'Not logged in',
            'redirect' => $this->ctx->self
        ];
    }

    /**
     * Forgot Password - Processes password reset requests
     * 
     * This method handles the password reset workflow. It validates the one-time
     * password (OTP) token, checks its expiration, and sets up the session for
     * password reset if the token is valid.
     * 
     * @return void
     */
    public function resetpw(): void
    {
        Util::elog(__METHOD__);

        $otp = html_entity_decode((string)$this->in['otp']);
        if (strlen($otp) === self::OTP_LENGTH)
        {
            if ($usr = $this->dbh->read('accounts', 'id,acl,login,otp,otpttl', 'otp = :otp', ['otp' => $otp], QueryType::One))
            {
                $id = (int)$usr['id'];
                $acl = (int)$usr['acl'];
                $login = (string)$usr['login'];
                $otpttl = (int)$usr['otpttl'];

                if ($otpttl && (($otpttl + 3600) > time()))
                {
                    if ($acl != 3)
                    { // suspended
                        $_SESSION['resetpw'] = ['usr' => $usr];
                        $this->ctx->ary = [
                            'status' => 'form',
                            'message' => 'Reset password',
                            'data' => ['id' => $id, 'login' => $login]
                        ];
                        return;
                    }
                    else
                    {
                        Util::log($login . ' is not allowed access');
                        $this->ctx->ary = [
                            'status' => 'error',
                            'message' => 'Access denied',
                            'redirect' => $this->ctx->self
                        ];
                        return;
                    }
                }
                else
                {
                    Util::log('Your one time password key has expired');
                    $this->ctx->ary = [
                        'status' => 'error',
                        'message' => 'Password reset key expired',
                        'redirect' => $this->ctx->self
                    ];
                    return;
                }
            }
            else
            {
                Util::log('Your one time password key no longer exists');
                $this->ctx->ary = [
                    'status' => 'error',
                    'message' => 'Invalid reset key',
                    'redirect' => $this->ctx->self
                ];
                return;
            }
        }
        else
        {
            Util::log('Incorrect one time password key');
            $this->ctx->ary = [
                'status' => 'error',
                'message' => 'Invalid reset key',
                'redirect' => $this->ctx->self
            ];
            return;
        }
    }

    private function mail_forgotpw(string $email, string $newpass, string $headers = ''): bool
    {
        Util::elog(__METHOD__);

        $host = $_SERVER['REQUEST_SCHEME'] . '://'
            . $this->ctx->host
            . $this->ctx->self;
        return mail(
            $email,
            'Reset password for ' . $this->ctx->host,
            'Here is your new OTP (one time password) key that is valid for one hour.

Please click on the link below and continue with reseting your password.

If you did not request this action then please ignore this message.

' . $host . '?o=Auth&m=resetpw&otp=' . $newpass,
            $headers
        );
    }
}
